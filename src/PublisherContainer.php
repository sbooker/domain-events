<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents;

final class PublisherContainer implements Publisher
{
    private static $instance;

    private Publisher $publisher;

    private function __construct() {}

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setPublisher(Publisher $publisher): void
    {
        if (null !== $this->publisher) {
            throw new \BadMethodCallException('Publisher already sets');
        }

        $this->publisher = $publisher;
    }

    /**
     * @param DomainEvent $event
     */
    public function publish(DomainEvent $event): void
    {
        if (!$this->publisher) {
            throw new \BadMethodCallException('Publisher must be initialized first!');
        }

        $this->publisher->publish($event);
    }

    final public function __clone()
    {
        throw new \BadMethodCallException('Cloning is restricted for enumerable types');
    }

    final public function __sleep()
    {
        throw new \BadMethodCallException('Serialization is restricted for enumerable types');
    }

    final public function __wakeup()
    {
        throw new \BadMethodCallException('Serialization is restricted for enumerable types');
    }
}