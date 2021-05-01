<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\DomainEntity;
use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\DominEventCollector;
use Sbooker\DomainEvents\Publisher;

final class DomainEventCollectorTest extends TestCase
{
    /**
     * @dataProvider examples
     */
    public function test(string $entityId, array $events): void
    {
        $entity = new Entity(Uuid::fromString($entityId));

        $entity->doSomething(...$events);

        $entity->dispatchEvents($this->createPublisher($events));
    }

    public function examples(): array
    {
        return [
            [ Uuid::NIL, [ new SomethingHappen(Uuid::fromString(Uuid::NIL)), ] ],
            [ Uuid::NIL, [ new SomethingHappen(Uuid::fromString(Uuid::NIL)), new SomethingHappen(Uuid::fromString(Uuid::NIL)), ] ],
            [ Uuid::NIL, [ new SomethingHappen(Uuid::fromString(Uuid::NIL)), new SomethingOtherHappen(Uuid::fromString(Uuid::NIL)), ] ],
        ];
    }

    private function createPublisher(array $expectedEvents): Publisher
    {
        $mock = $this->createMock(Publisher::class);
        $mock->expects($this->exactly(count($expectedEvents)))
            ->method('publish')
            ->withConsecutive(
                ...array_map(fn(DomainEvent $event):array => [$event], $expectedEvents)
            );

        return $mock;
    }
}

final class Entity implements DomainEntity
{
    use DominEventCollector;

    private UuidInterface $id;

    public function __construct(UuidInterface $id)
    {
        $this->id = $id;
    }

    public function doSomething(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->publish($event);
        }
    }
}

final class SomethingHappen extends DomainEvent {}

final class SomethingOtherHappen extends DomainEvent {}