<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sbooker\DomainEvents\ActorAwarePublisher;
use Sbooker\DomainEvents\ActorStorage;
use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\Publisher;

class ActorAwarePublisherTest extends TestCase
{
    /**
     * @dataProvider actorExamples
     */
    public function test(?Actor $actor): void
    {
        $event = new Event(Uuid::uuid4());
        $publisher = new ActorAwarePublisher($this->getPublisher($actor), $this->getActorStorage($actor));

        $publisher->publish($event);

        $this->assertEquals($actor, $event->getActor());
    }

    public function actorExamples(): array
    {
        return [
            [ new Actor(Uuid::uuid4()) ],
            [ null ],
        ];
    }

    private function getActorStorage(?Actor $actor): ActorStorage
    {
        $mock = $this->createMock(ActorStorage::class);
        $mock->expects($this->once())->method('getCurrentActor')->willReturn($actor);

        return $mock;
    }

    private function getPublisher(?Actor $actor): Publisher
    {
        $mock = $this->createMock(Publisher::class);
        $mock->expects($this->once())->method('publish')->with($this->callback(
            fn(DomainEvent $event): bool => $event->getActor() === $actor
        ));

        return $mock;
    }
}

final class Actor extends \Sbooker\DomainEvents\Actor { }

final class Event extends DomainEvent { }