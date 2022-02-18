<?php

declare(strict_types=1);

namespace GolemAi\MessengerKit\Tests\Command;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use GolemAi\MessengerKit\Command\SimulatorCommand;
use GolemAi\MessengerKit\Simulator\Event;
use GolemAi\MessengerKit\Simulator\MessengerSimulator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @covers \GolemAi\MessengerKit\Command\SimulatorCommand
 *
 * @uses \GolemAi\MessengerKit\Simulator\Event
 *
 * @internal
 */
final class SimulatorCommandTest extends TestCase
{
    use ProphecyTrait;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
    }

    public function test(): void
    {
        CarbonImmutable::setTestNow($now = new CarbonImmutable('2022-02-23 11:26:21'));

        $retryStrategyLocator = $this->prophesize(ServiceProviderInterface::class);
        $messengerSimulator = $this->prophesize(MessengerSimulator::class);

        $testTransport = 'foobar';

        $retryStrategy = $this->prophesize(RetryStrategyInterface::class);

        $retryStrategyLocator->get($testTransport)
            ->willReturn($retryStrategy->reveal())
        ;

        $messengerSimulator
            ->simulate(
                $retryStrategy,
                Argument::type(\DateTimeImmutable::class),
                Argument::type(CarbonInterval::class),
                Argument::type(CarbonInterval::class)
            )
            ->willYield([
                new Event($now = $now->addSeconds(1), Event::KIND_CONSUMING, 2),
                new Event($now = $now->addSeconds(2), Event::KIND_FAILURE, 1),
                new Event($now = $now->addSeconds(3), Event::KIND_RETRYING_IN, 2, CarbonInterval::hours(7)),
                new Event($now = $now->addSeconds(4), Event::KIND_SUCCESS, 3),
                new Event($now = $now->addSeconds(5), Event::KIND_NOT_RETRYABLE, 4),
            ])
        ;

        $command = new SimulatorCommand($retryStrategyLocator->reveal(), $messengerSimulator->reveal());

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'transport' => $testTransport,
        ]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();

        static::assertSame(
            <<<'CODE_SAMPLE'
┌──────────────┬──────────────────────────────┐
│ Time Elapsed │ Event                        │
├──────────────┼──────────────────────────────┤
│           1s │ Consuming message - 2        │
│           3s │ Failure                      │
│           6s │ Retrying in 7 hours          │
│          10s │ Success                      │
│          15s │ The message is not retryable │
└──────────────┴──────────────────────────────┘

CODE_SAMPLE

            ,
            $output
        );
    }
}
