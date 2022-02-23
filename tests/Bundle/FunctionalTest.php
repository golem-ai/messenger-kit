<?php

declare(strict_types=1);

namespace GolemAi\MessengerKit\Tests\Bundle;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
final class FunctionalTest extends KernelTestCase
{
    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public function getTestData(): iterable
    {
        yield [
            'foo',
            <<<'CODE_SAMPLE'
┌──────────────┬──────────────────────────────┐
│ Time Elapsed │ Event                        │
├──────────────┼──────────────────────────────┤
│           1s │ Consuming message - 0        │
│           1s │ Failure                      │
│           1s │ Retrying in 1 second         │
│           1s │ Consuming message - 1        │
│           1s │ Failure                      │
│           1s │ Retrying in 1 second         │
│           6s │ Consuming message - 2        │
│           6s │ Failure                      │
│           6s │ Retrying in 1 second         │
│          31s │ Consuming message - 3        │
│          31s │ Failure                      │
│          31s │ The message is not retryable │
└──────────────┴──────────────────────────────┘

CODE_SAMPLE
        ];

        yield [
            'bar',
            <<<'CODE_SAMPLE'
┌──────────────┬──────────────────────────────┐
│ Time Elapsed │ Event                        │
├──────────────┼──────────────────────────────┤
│           1s │ Consuming message - 0        │
│           1s │ Failure                      │
│           1s │ Retrying in 1 second         │
│          10s │ Consuming message - 1        │
│          10s │ Failure                      │
│          10s │ Retrying in 1 second         │
│       4m 20s │ Consuming message - 2        │
│       4m 20s │ Failure                      │
│       4m 20s │ Retrying in 1 second         │
│   1h 48m 30s │ Consuming message - 3        │
│   1h 48m 30s │ Failure                      │
│   1h 48m 30s │ Retrying in 1 second         │
│   1d 21h 12m │ Consuming message - 4        │
│   1d 21h 12m │ Failure                      │
│   1d 21h 12m │ Retrying in 1 second         │
│    1mo 2w 2d │ Consuming message - 5        │
│    1mo 2w 2d │ Failure                      │
│    1mo 2w 2d │ The message is not retryable │
└──────────────┴──────────────────────────────┘

CODE_SAMPLE
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function test(string $transport, string $expectedOutput): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('golem:messenger-kit:simulator');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'transport' => $transport,
        ]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();

        static::assertSame($expectedOutput, $output);
    }

    protected static function getKernelClass()
    {
        return TestKernel::class;
    }
}
