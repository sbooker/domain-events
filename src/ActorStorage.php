<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents;

interface ActorStorage
{
    public function getCurrentActor(): ?Actor;
}