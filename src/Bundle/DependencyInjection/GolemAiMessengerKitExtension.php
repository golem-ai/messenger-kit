<?php

declare(strict_types=1);

namespace GolemAi\MessengerKit\Bundle\DependencyInjection;

use GolemAi\MessengerKit\Command\SimulatorCommand;
use GolemAi\MessengerKit\Simulator\MessengerSimulator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class GolemAiMessengerKitExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->register(MessengerSimulator::class, MessengerSimulator::class);

        $container
            ->register(SimulatorCommand::class, SimulatorCommand::class)
            ->setArguments([
                new Reference('messenger.retry_strategy_locator'),
                new Reference(MessengerSimulator::class),
            ])
            ->addTag('console.command')
        ;
    }
}
