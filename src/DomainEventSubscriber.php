<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents;

interface DomainEventSubscriber
{
    /**
     * @return string[] Classes of event's.
     */
    public function getListenedEventClasses(): array;

    /**
     * @throws \Exception
     */
    public function handleEvent(DomainEvent $event): void;
}