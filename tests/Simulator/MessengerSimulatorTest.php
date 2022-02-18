<?php

declare(strict_types=1);

namespace GolemAi\MessengerKit\Tests\Simulator;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use GolemAi\MessengerKit\Retry\FixedStrategy;
use GolemAi\MessengerKit\Simulator\Event;
use GolemAi\MessengerKit\Simulator\MessengerSimulator;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @covers \GolemAi\MessengerKit\Simulator\MessengerSimulator
 *
 * @uses \GolemAi\MessengerKit\Simulator\Event
 * @uses \GolemAi\MessengerKit\Retry\FixedStrategy
 *
 * @internal
 */
final class MessengerSimulatorTest extends TestCase
{
    use ProphecyTrait;

    public function testLinearRetries(): void
    {
        $strategy = new FixedStrategy(2, 10000);

        $start = CarbonImmutable::now();

        $failureDuration = CarbonInterval::hours(2);
        $consumerDuration = CarbonInterval::seconds(3);

        $simulator = new MessengerSimulator();
        $events = $simulator->simulate($strategy, $start, $failureDuration, $consumerDuration);

        $expectedEvents = [
            new Event($start, Event::KIND_CONSUMING, 0),
            new Event($start->addSeconds(3), Event::KIND_FAILURE, 0),
            new Event($start->addSeconds(3), Event::KIND_RETRYING_IN, 0, CarbonInterval::seconds(10)),
            new Event($start->addSeconds(3 + 10), Event::KIND_CONSUMING, 1),
            new Event($start->addSeconds(3 + 10 + 3), Event::KIND_FAILURE, 1),
            new Event($start->addSeconds(3 + 10 + 3), Event::KIND_RETRYING_IN, 1, CarbonInterval::seconds(10)),
            new Event($start->addSeconds(3 + 10 + 3 + 10), Event::KIND_CONSUMING, 2),
            new Event($start->addSeconds(3 + 10 + 3 + 10 + 3), Event::KIND_FAILURE, 2),
            new Event($start->addSeconds(3 + 10 + 3 + 10 + 3), Event::KIND_NOT_RETRYABLE, 2),
        ];

        $count = 0;
        foreach ($events as $index => $event) {
            $expected = $expectedEvents[$index];

            static::assertTrue(
                $expectedEvents[$index]->equals($event),
                var_export([
                    'index' => $index,
                    'expected' => $expected,
                    'actual' => $event,
                ], true)
            );

            ++$count;
        }

        static::assertSame(\count($expectedEvents), $count);
    }
}
