<?php

declare(strict_types=1);

namespace GolemAi\MessengerKit\Simulator;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;

class Event
{
    public const KIND_CONSUMING = 'consuming';

    public const KIND_SUCCESS = 'success';

    public const KIND_FAILURE = 'failure';

    public const KIND_NOT_RETRYABLE = 'not_retryable';

    public const KIND_RETRYING_IN = 'retrying_in';

    public const KINDS = [
        self::KIND_CONSUMING,
        self::KIND_SUCCESS,
        self::KIND_FAILURE,
        self::KIND_NOT_RETRYABLE,
        self::KIND_RETRYING_IN,
    ];

    private CarbonImmutable $time;

    private string $kind;

    private int $retryCount;

    private ?CarbonInterval $waitingTime;

    public function __construct(
        CarbonImmutable $time,
        string $kind,
        int $retryCount,
        ?CarbonInterval $waitingTime = null
    ) {
        if (! \in_array($kind, self::KINDS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid kind "%s", possible values: %s.',
                $kind,
                implode(', ', self::KINDS)
            ));
        }

        $this->time = $time;
        $this->kind = $kind;
        $this->retryCount = $retryCount;
        $this->waitingTime = $waitingTime;
    }

    public function getTime(): CarbonImmutable
    {
        return $this->time;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function getWaitingTime(): ?CarbonInterval
    {
        return $this->waitingTime;
    }

    public function equals(self $other): bool
    {
        return
            $this->time->eq($other->getTime())
            && $this->kind === $other->getKind()
            && $this->retryCount === $other->getRetryCount()
            && (
                (
                    $this->waitingTime !== null
                    && $other->getWaitingTime() !== null
                    && $this->waitingTime->eq($other->getWaitingTime())
                )
                || (
                    $this->waitingTime === null
                    && $this->waitingTime === $other->getWaitingTime()
                )
            )
        ;
    }
}
