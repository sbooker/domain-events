<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents;

final class ActorAwarePublisher implements Publisher
{
    private Publisher $publisher;

    private ActorStorage $actorStorage;

    public function __construct(Publisher $publisher, ActorStorage $actorStorage)
    {
        $this->publisher = $publisher;
        $this->actorStorage = $actorStorage;
    }

    public function publish(DomainEvent $event): void
    {
        $actor = $this->actorStorage->getCurrentActor();
        if (null !== $actor) {
            $event->injectActor($actor);
        }

        $this->publisher->publish($event);
    }
}