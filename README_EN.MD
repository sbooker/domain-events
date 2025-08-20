[Read in Russian (Читать на русском)](README.MD)

# Domain Events Library (`sbooker/domain-events`)

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]
[![Build Status](https://travis-ci.org/sbooker/domain-events.svg?branch=2.x)](https://travis-ci.org/sbooker/domain-events)
[![codecov](https://codecov.io/gh/sbooker/domain-events/branch/2.x/graph/badge.svg?token=3uCI9t0M2Q)](https://codecov.io/gh/sbooker/domain-events)


A simple yet powerful library for implementing the "Domain Events" pattern, with a focus on clean architecture and DDD principles. A core library in the `sbooker/domain` ecosystem.

Its main feature is a built-in mechanism to automatically track who performed an action (the `Actor`) without polluting the domain model. The `sbooker/domain` ecosystem provides reliable methods for their asynchronous processing—both with and without a message broker.

## Key Features

*   **Easy Integration:** Use the `DomainEventCollector` trait in your entities.
*   **Automatic Event Enrichment:** The built-in `ActorAwarePublisher` decorator automatically adds information about the current user (`Actor`) to the event.
*   **Zero Dependencies:** The library does not depend on other libraries or any framework.
*   **Flexibility:** Based on simple interfaces (`Publisher`, `ActorStorage`, `DomainEventSubscriber`) that you can implement in your own infrastructure or use libraries from the `sbooker/domain` ecosystem.

## Installation

```bash
composer require sbooker/domain-events
```

## Architecture and Components

*   `DomainEvent`: An abstract class that all your domain events should extend. It already contains the entity ID, the time of occurrence, and optionally, the `Actor`.
*   `DomainEventCollector`: A trait you add to your entities (Aggregates). It provides methods to collect (`publish`) and later dispatch (`dispatchEvents`) events.
*   `Publisher`: An interface for the event dispatcher. You must provide your own implementation or use the [sbooker/domain-events-persistence](https://github.com/sbooker/domain-events-persistence) library.
*   `Actor`: A simple object identifying the user who initiated the action.
*   `ActorStorage`: An interface for retrieving the current `Actor` from the application's context (e.g., from a session or security token).
*   `ActorAwarePublisher`: A decorator for your `Publisher` that automatically injects the `Actor` into an event before it's published.

## Quick Start

### 1. Prepare Your Entity and Event

Use the `DomainEventCollector` trait in your aggregate and define an event class.

```php
// src/Product.php
use Sbooker\DomainEvents\DomainEventCollector;
use Sbooker\DomainEvents\DomainEntity;

class Product implements DomainEntity
{
    use DomainEventCollector;

    public function __construct(UuidInterface $id, string $name)
    {
        // ...
        // Record what happened
        $this->publish(new ProductCreated($id, $name));
    }
}

// src/ProductCreated.php
use Sbooker\DomainEvents\DomainEvent;

final class ProductCreated extends DomainEvent {
    // ... your event code
}
```

### 2. Implement Event Persistence (Transactional Outbox)

It is recommended to save events to a dedicated table (`event`) in the same database and within the same transaction as the entity.

For this, use the solution from [sbooker/domain-events-persistence](https://github.com/sbooker/domain-events-persistence) and [sbooker/transaction-manager](https://github.com/sbooker/transaction-manager). Or write your own:

```php
// src/Infrastructure/OutboxPublisher.php
use Sbooker\DomainEvents\Publisher;
use Sbooker\DomainEvents\DomainEvent;

// This Publisher saves events to a repository (e.g., in a DB)
final class OutboxPublisher implements Publisher
{
    private OutboxEventRepository $repository;

    public function __construct(OutboxEventRepository $repository)
    {
        $this->repository = $repository;
    }

    public function publish(DomainEvent $event): void
    {
        $outboxEvent = new OutboxEvent($event);
        $this->repository->add($outboxEvent);
    }
}
```

In your application service layer, use the `OutboxPublisher`. The entire persistence process occurs within a single transaction.

```php
// src/UseCase/CreateProduct/Handler.php
use Sbooker\DomainEvents\ActorAwarePublisher;

// 1. Create a Publisher that saves events to the DB
$publisher = new ActorAwarePublisher(
    new OutboxPublisher($outboxEventRepository), // <-- Using our new Publisher
    new SymfonyActorStorage($security)
);

// 2. Execute business logic
$product = new Product(Uuid::uuid4(), 'Laptop');
$productRepository->add($product);

// 3. Pass events to the Publisher, which will also save them to the DB
$product->dispatchEvents($publisher);

// 4. Commit the transaction
// Doctrine EntityManager or your Unit of Work will save both the product AND the events in one transaction
$entityManager->flush();
```

### 3. Process Events Asynchronously

A background process (worker) that reads events from the table is used for processing. The `sbooker/domain` ecosystem supports two main approaches:

#### Approach 1: Direct Processing (without a message broker)

This approach is ideal when you want to avoid infrastructure complexity (no RabbitMQ, Kafka, etc.) while ensuring reliable and **parallel event processing**.

The [sbooker/domain-events-persistence](https://github.com/sbooker/domain-events-persistence) library provides ready-to-use tools to run multiple workers that won't interfere with each other, thanks to a pessimistic locking mechanism.

**Example of a processing worker:**

```php
// src/Command/ProcessOutboxEventsCommand.php
class ProcessOutboxEventsCommand extends Command
{
    public function execute(): int
    {
        // findUnprocessed() from sbooker/domain-events-persistence can lock
        // events for safe parallel processing.
        $eventsToProcess = $this->outboxRepo->findUnprocessed();
        
        foreach ($eventsToProcess as $outboxEvent) {
            try {
                // Directly call the required handler (Subscriber)
                $this->eventSubscriber->handle($outboxEvent->getDomainEvent());
                
                $this->outboxRepo->markAsProcessed($outboxEvent);
            } catch (\Exception $e) {
                // Log the error; the event will be re-processed later.
                $this->logger->error('Failed to process event', ['id' => $outboxEvent->getId(), 'error' => $e]);
            }
        }
        $this->entityManager->flush();
        
        return Command::SUCCESS;
    }
}
```

#### Approach 2: Relaying to a Message Broker (classic Outbox)

A classic pattern for microservice architectures or when integration with external systems via a message broker is required.

In this case, the background process is solely responsible for reliably delivering the event from the database table to RabbitMQ (or another broker).

**Example of a relaying worker:**

```php
// src/Command/RelayOutboxEventsCommand.php
class RelayOutboxEventsCommand extends Command
{
    public function execute(): int
    {
        $eventsToRelay = $this->outboxRepo->findUnprocessed();
        
        foreach ($eventsToRelay as $outboxEvent) {
            try {
                // Send the event to an external message bus
                $this->realMessageBroker->publish($outboxEvent->getDomainEvent());
                
                $this->outboxRepo->markAsProcessed($outboxEvent);
            } catch (\Exception $e) {
                // Log the error; the relay will be retried on the next run.
                $this->logger->error('Failed to relay event', ['id' => $outboxEvent->getId(), 'error' => $e]);
            }
        }
        $this->entityManager->flush();
        
        return Command::SUCCESS;
    }
}
```

## License
See [LICENSE][license] file.

[badge-release]: https://img.shields.io/packagist/v/sbooker/domain-events.svg?style=flat-square
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/sbooker/domain-events.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/sbooker/domain-events.svg?style=flat-square

[release]: https://packagist.org/packages/sbooker/domain-events
[license]: https://github.com/sbooker/domain-events/blob/master/LICENSE
[php]: https://php.net
[downloads]: https://packagist.org/packages/sbooker/domain-events

[composer]: https://getcomposer.org