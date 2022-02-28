<?php

declare(strict_types=1);

namespace GolemAi\MessengerKit\Simulator;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

class MessengerSimulator
{
    /**
     * @return iterable<Event>
     */
    public function simulate(
        RetryStrategyInterface $strategy,
        CarbonImmutable $start,
        CarbonInterval $failureDuration,
        CarbonInterval $consumerDuration
    ): iterable {
        $envelope = new Envelope(new \stdClass());
        $simulatedTime = $start;

        while (true) {
            $retryCount = RedeliveryStamp::getRetryCountFromEnvelope($envelope);

            yield new Event($simulatedTime, Event::KIND_CONSUMING, $retryCount);

            $simulatedTime = $simulatedTime->add($consumerDuration);

            if ($start->add($failureDuration) < $simulatedTime) {
                yield new Event($simulatedTime, Event::KIND_SUCCESS, $retryCount);

                return;
            }

            yield new Event($simulatedTime, Event::KIND_FAILURE, $retryCount);

            $isRetryable = $strategy->isRetryable($envelope);

            if (! $isRetryable) {
                yield new Event($simulatedTime, Event::KIND_NOT_RETRYABLE, $retryCount);

                return;
            }

            $waitingTime = CarbonInterval::milliseconds($strategy->getWaitingTime($envelope))->cascade();

            yield new Event($simulatedTime, Event::KIND_RETRYING_IN, $retryCount, $waitingTime);

            $envelope = $envelope->with(new RedeliveryStamp($retryCount + 1));

            $simulatedTime = $simulatedTime->add($waitingTime);
        }
    }
}
