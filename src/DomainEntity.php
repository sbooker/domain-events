<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents;

interface DomainEntity
{
    public function dispatchEvents(Publisher $publisher): void;
}