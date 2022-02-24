<?php

declare(strict_types=1);

namespace GolemAi\MessengerKit\Command;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use GolemAi\MessengerKit\Simulator\Event;
use GolemAi\MessengerKit\Simulator\MessengerSimulator;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

class SimulatorCommand extends Command
{
    protected static $defaultName = 'golem-ai:messenger-kit:simulator';

    private ServiceProviderInterface $retryStrategyLocator;

    private MessengerSimulator $messengerSimulator;

    public function __construct(
        ServiceProviderInterface $retryStrategyLocator,
        MessengerSimulator $messengerSimulator
    ) {
        parent::__construct();

        $this->retryStrategyLocator = $retryStrategyLocator;
        $this->messengerSimulator = $messengerSimulator;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('transport', InputArgument::REQUIRED)
            ->addOption('fail-for', null, InputOption::VALUE_OPTIONAL, 'How long should the consumer fail', '10 years')
            ->addOption(
                'consumer-duration',
                null,
                InputOption::VALUE_OPTIONAL,
                'How long should the consumer take before failing',
                '0 seconds'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $transport = $input->getArgument('transport');
        $failureDuration = $input->getOption('fail-for');
        $consumerDuration = $input->getOption('consumer-duration');

        \assert(\is_string($transport));
        \assert(\is_string($failureDuration));
        \assert(\is_string($consumerDuration));

        $failureDuration = CarbonInterval::fromString($failureDuration);
        $consumerDuration = CarbonInterval::fromString($consumerDuration);

        try {
            $strategy = $this->retryStrategyLocator->get($transport);
        } catch (NotFoundExceptionInterface $e) {
            $output->writeln(sprintf('<error>Transport strategy "%s" not found.</error>', $transport));

            return 1;
        }

        \assert($strategy instanceof RetryStrategyInterface);

        $start = CarbonImmutable::now();

        $simulation = $this->messengerSimulator->simulate($strategy, $start, $failureDuration, $consumerDuration);

        $this->renderEvents($output, $simulation, $start);

        return 0;
    }

    /**
     * @param iterable<Event> $events
     */
    protected function renderEvents(OutputInterface $output, $events, CarbonImmutable $start): void
    {
        $table = new Table($output);
        $table->setHeaders(['Time Elapsed', 'Event']);
        $table->setStyle('box');

        $timeColumnStyle = new TableStyle();
        $timeColumnStyle->setPadType(STR_PAD_LEFT);
        $table->setColumnStyle(0, $timeColumnStyle);

        foreach ($events as $event) {
            switch ($event->getKind()) {
                case Event::KIND_CONSUMING:
                    $text = sprintf('Consuming message - %d', $event->getRetryCount());
                    break;
                case Event::KIND_SUCCESS:
                    $text = '<fg=green>Success</>';
                    break;
                case Event::KIND_FAILURE:
                    $text = '<fg=yellow>Failure</>';
                    break;
                case Event::KIND_NOT_RETRYABLE:
                    $text = '<fg=red>The message is not retryable</>';
                    break;
                case Event::KIND_RETRYING_IN:
                    $waitingTime = $event->getWaitingTime() ?? CarbonInterval::seconds(0);

                    $text = sprintf('<fg=cyan>Retrying in %s</>', $waitingTime->forHumans());
                    break;
                default:
                    throw new \Exception(sprintf('Unknown event %s', $event->getKind()));
            }

            $table->addRow([$event->getTime()->shortAbsoluteDiffForHumans($start, 3), $text]);
        }

        $table->render();
    }
}
