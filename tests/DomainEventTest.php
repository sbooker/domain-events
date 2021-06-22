<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\Actor;
use Sbooker\DomainEvents\DomainEvent;

class DomainEventTest extends TestCase
{
    /**
     * @dataProvider createExamples
     */
    public function testCreate(UuidInterface $entityId, ?TestActor $actor): void
    {
        $before = new \DateTimeImmutable();
        $event = new TestEvent($entityId, $actor);
        $after = new \DateTimeImmutable();

        $this->assertTrue($entityId->equals($event->getEntityId()));
        $this->assertGreaterThanOrEqual($before, $event->getOccurredAt());
        $this->assertLessThanOrEqual($after, $event->getOccurredAt());
        $this->assertEquals($actor, $event->getActor());
    }

    public function createExamples(): array
    {
        return [
            [ Uuid::uuid4(), null ],
            [ Uuid::uuid4(), new TestActor(Uuid::uuid4()) ],
        ];
    }

    public function testSetActor(): void
    {
        $event = new TestEvent(Uuid::uuid4());
        $actor = new TestActor(Uuid::uuid4());

        $event->injectActor($actor);

        $this->assertEquals($actor, $event->getActor());
    }

    public function testSecondSetActor(): void
    {
        $event = new TestEvent(Uuid::uuid4());
        $actor = new TestActor(Uuid::uuid4());
        $event->injectActor($actor);
        $otherActor = new TestActor(Uuid::uuid4());

        $event->injectActor($otherActor);

        $this->assertEquals($actor, $event->getActor());
    }

    public function testSetActorInCreatedWithActor(): void
    {
        $actor = new TestActor(Uuid::uuid4());
        $event = new TestEvent(Uuid::uuid4(), $actor);
        $otherActor = new TestActor(Uuid::uuid4());

        $event->injectActor($otherActor);

        $this->assertEquals($actor, $event->getActor());
    }
}

final class TestEvent extends DomainEvent { }

final class TestActor extends Actor { }