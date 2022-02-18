<?php

declare(strict_types=1);

namespace GolemAi\MessengerKit\Retry;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

class FixedStrategy implements RetryStrategyInterface
{
    private int $maxRetry;

    private int $waitingTime;

    public function __construct(int $maxRetry, int $waitingTime)
    {
        $this->maxRetry = $maxRetry;
        $this->waitingTime = $waitingTime;
    }

    public function isRetryable(Envelope $message): bool
    {
        return RedeliveryStamp::getRetryCountFromEnvelope($message) < $this->maxRetry;
    }

    public function getWaitingTime(Envelope $message): int
    {
        return $this->waitingTime;
    }
}
