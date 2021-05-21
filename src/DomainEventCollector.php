<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents;

trait DomainEventCollector
{
    /** @var array<DomainEvent> */
    private array $events = [];

    /* final */ protected function publish(DomainEvent $event): void
    {
        $this->events[] = $event;
    }

    /* final */ public function dispatchEvents(Publisher $publisher): void
    {
        [$events, $this->events] = [$this->events, []];

        foreach ($events as $key => $event) {
            $publisher->publish($event);
        }
    }
}