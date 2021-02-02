<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents;

interface Publisher
{
    public function publish(DomainEvent $event): void;
}