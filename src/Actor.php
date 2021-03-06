<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents;

use Ramsey\Uuid\UuidInterface;

class Actor
{
    private UuidInterface $id;

    public function __construct(UuidInterface $id)
    {
        $this->id = $id;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }
}