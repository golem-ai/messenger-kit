<?php

declare(strict_types=1);

namespace GolemAi\MessengerKit\Tests\Bundle;

use GolemAi\MessengerKit\Bundle\GolemAiMessengerKitBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new GolemAiMessengerKitBundle()];
    }

    public function getCacheDir(): string
    {
        return sprintf('%s/test-%s', sys_get_temp_dir(), $this->getEnvironment());
    }

    /**
     * @phpstan-ignore-next-line
     */
    protected function configureContainer($c): void
    {
        $frameworkConfig = [
            'messenger' => [
                'transports' => [
                    'foo' => [
                        'dsn' => '',
                        'retry_strategy' => [
                            'max_retries' => 3,
                            'delay' => 1000,
                            'multiplier' => 5,
                        ],
                    ],
                    'bar' => [
                        'dsn' => '',
                        'retry_strategy' => [
                            'max_retries' => 5,
                            'delay' => 10_000,
                            'multiplier' => 25,
                        ],
                    ],
                ],
            ],
        ];

        if ($c instanceof ContainerBuilder) {
            $c->loadFromExtension('framework', $frameworkConfig);

            return;
        }

        \assert($c instanceof ContainerConfigurator);

        $c->extension('framework', $frameworkConfig);
    }

    protected function configureRoutes(): void
    {
    }
}
