<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

abstract class DomainEvent
{
    private UuidInterface $entityId;

    private DateTimeImmutable $occurredAt;

    private ?Actor $actor;

    public function __construct(UuidInterface $entityId, ?Actor $actor = null)
    {
        $this->entityId = $entityId;
        $this->occurredAt = new DateTimeImmutable();
        $this->actor = $actor;
    }

    public function injectActor(Actor $actor): void
    {
        if (null !== $this->getActor()) {
            return;
        }

        $this->actor = $actor;
    }

    public function getEntityId(): UuidInterface
    {
        return $this->entityId;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getActor(): ?Actor
    {
        return $this->actor;
    }
}