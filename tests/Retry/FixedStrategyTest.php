<?php

declare(strict_types=1);

namespace GolemAi\MessengerKit\Tests\Retry;

use GolemAi\MessengerKit\Retry\FixedStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

/**
 * @internal
 */
final class FixedStrategyTest extends TestCase
{
    /**
     * @return iterable<array{0: FixedStrategy, 1: int, 2: bool, 3?: int}>
     */
    public function getTestData(): iterable
    {
        yield [new FixedStrategy(3, 10), 0, true, 10];
        yield [new FixedStrategy(3, 10), 2, true, 10];
        yield [new FixedStrategy(3, 10), 3, false];
        yield [new FixedStrategy(3, 100), 2, true, 100];
    }

    /**
     * @dataProvider getTestData
     */
    public function test(
        FixedStrategy $strategy,
        int $retryCount,
        bool $expectedIsRetryable,
        ?int $expectedWaitingTime = null
    ): void {
        $envelope = new Envelope(new \stdClass());
        if ($retryCount > 0) {
            $envelope = $envelope->with(new RedeliveryStamp($retryCount));
        }

        static::assertSame($expectedIsRetryable, $strategy->isRetryable($envelope));

        if (! $expectedIsRetryable) {
            return;
        }

        static::assertSame($expectedWaitingTime, $strategy->getWaitingTime($envelope));
    }
}
