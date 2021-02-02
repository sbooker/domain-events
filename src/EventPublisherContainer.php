<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents;

trait EventPublisherContainer
{
    /* final */ protected function publish(DomainEvent $event): void
    {
        PublisherContainer::instance()->publish($event);
    }
}