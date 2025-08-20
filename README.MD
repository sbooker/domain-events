[Read in English](README_EN.MD)

# Domain Events Library (`sbooker/domain-events`)

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]
[![Build Status](https://travis-ci.org/sbooker/domain-events.svg?branch=2.x)](https://travis-ci.org/sbooker/domain-events)
[![codecov](https://codecov.io/gh/sbooker/domain-events/branch/2.x/graph/badge.svg?token=3uCI9t0M2Q)](https://codecov.io/gh/sbooker/domain-events)


Простая, но мощная библиотека для реализации паттерна "События предметной области" (Domain Events) с упором на чистую архитектуру и принципы DDD.
Базовая библиотека в экосистеме `sbooker/domain`

Главная особенность — встроенный механизм для автоматического отслеживания, кто совершил действие (Actor), без загрязнения доменной модели.
Экосистема `sbooker/domain` предоставляет надежные способы их асинхронной обработки — как с использованием брокера сообщений, так и без него.

## Ключевые особенности

*   **Простота интеграции:** Используйте трейт `DomainEventCollector` в ваших сущностях.
*   **Автоматическое обогащение событий:** Встроенный декоратор `ActorAwarePublisher` автоматически добавляет к событию информацию о текущем пользователе (`Actor`).
*   **Нулевые зависимости:** Библиотека не зависит от других библиотек или какого-либо фреймворка.
*   **Гибкость:** Основана на простых интерфейсах (`Publisher`, `ActorStorage`, `DomainEventSubscriber`), которые вы реализуете в своей инфраструктуре или используете библиотеки из экосистемы `sbooker/domain`.

## Установка

```bash
composer require sbooker/domain-events
```

## Архитектура и компоненты

*   `DomainEvent`: Абстрактный класс, от которого наследуются все ваши события. Уже содержит ID сущности, время возникновения и опционально — `Actor`.
*   `DomainEventCollector`: Трейт, который вы добавляете в свои сущности (Агрегаты). Он предоставляет метод для сбора (`publish`) и последующей отправки (`dispatchEvents`) событий.
*   `Publisher`: Интерфейс для диспетчера событий. Вы должны предоставить его реализацию или использовать библиотеку [sbooker/domain-events-persistence](https://github.com/sbooker/domain-events-persistence).
*   `Actor`: Простой объект, идентифицирующий пользователя, который инициировал действие.
*   `ActorStorage`: Интерфейс для получения текущего `Actor` из контекста приложения (например, из сессии или токена безопасности).
*   `ActorAwarePublisher`: Декоратор для вашего `Publisher`, который автоматически "внедряет" `Actor` в событие перед его публикацией.

## Быстрый старт

### 1. Подготовьте вашу сущность и событие

Используйте трейт `DomainEventCollector` в вашем агрегате и определите класс события.

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
        // Записываем событие о том, что произошло
        $this->publish(new ProductCreated($id, $name));
    }
}

// src/ProductCreated.php
use Sbooker\DomainEvents\DomainEvent;

final class ProductCreated extends DomainEvent {
    // ... ваш код события
}
```

### 2. Реализуйте сохранение событий (Transactional Outbox)

Рекомендуется сохранять события в специальную таблицу (`event`) в той же базе данных и в той же транзакции, что и сущность.

Для этого используйте решение из [sbooker/domain-events-persistence](https://github.com/sbooker/domain-events-persistence) и
[sbooker/transaction-manager](https://github.com/sbooker/transaction-manager). Или напишите собственное:


```php
// src/Infrastructure/OutboxPublisher.php
use Sbooker\DomainEvents\Publisher;
use Sbooker\DomainEvents\DomainEvent;

// Этот Publisher сохраняет события в репозиторий (например, в БД)
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

В сервисном слое вы используете `OutboxPublisher`. Весь процесс сохранения происходит внутри одной транзакции.

```php
// src/UseCase/CreateProduct/Handler.php
use Sbooker\DomainEvents\ActorAwarePublisher;

// 1. Создаем Publisher, который сохраняет события в БД
$publisher = new ActorAwarePublisher(
    new OutboxPublisher($outboxEventRepository), // <-- Используем наш новый Publisher
    new SymfonyActorStorage($security)
);

// 2. Выполняем бизнес-логику
$product = new Product(Uuid::uuid4(), 'Ноутбук');
$productRepository->add($product);

// 3. Передаем события в Publisher, который тоже сохранит их в БД
$product->dispatchEvents($publisher);

// 4. Коммитим транзакцию
// Doctrine EntityManager или ваш Unit of Work сохранит И продукт, И события в одной транзакции
$entityManager->flush();
```

### 3. Асинхронно обработайте события

Для обработки событий используется фоновый процесс (воркер), который читает события из таблицы. 
Экосистема `sbooker/domain` поддерживает два основных подхода:

#### Подход 1: Прямая обработка (без брокера сообщений)

Этот подход идеален, когда вы хотите избежать усложнения инфраструктуры (без RabbitMQ, Kafka и т.д.), но при этом обеспечить надежную **параллельную обработку событий**.

Библиотека [sbooker/domain-events-persistence](https://github.com/sbooker/domain-events-persistence) предоставляет готовые инструменты для запуска нескольких воркеров, которые не будут мешать друг другу, благодаря механизму пессимистичных блокировок.

**Пример воркера-обработчика:**

```php
// src/Command/ProcessOutboxEventsCommand.php
class ProcessOutboxEventsCommand extends Command
{
    public function execute(): int
    {
        // findUnprocessed() из sbooker/domain-events-persistence может блокировать
        // события для безопасной параллельной обработки.
        $eventsToProcess = $this->outboxRepo->findUnprocessed();
        
        foreach ($eventsToProcess as $outboxEvent) {
            try {
                // Напрямую вызываем нужный обработчик (Subscriber)
                $this->eventSubscriber->handle($outboxEvent->getDomainEvent());
                
                $this->outboxRepo->markAsProcessed($outboxEvent);
            } catch (\Exception $e) {
                // Логируем ошибку, событие будет обработано повторно.
                $this->logger->error('Failed to process event', ['id' => $outboxEvent->getId(), 'error' => $e]);
            }
        }
        $this->entityManager->flush();
        
        return Command::SUCCESS;
    }
}
```

#### Подход 2: Ретрансляция в брокер сообщений (классический Outbox)

Классический паттерн для микросервисной архитектуры или когда требуется интеграция с внешними системами через брокер сообщений.

В этом случае задача фонового процесса — всего лишь гарантированно доставить событие из таблицы в RabbitMQ (или другой брокер).

**Пример воркера-ретранслятора:**

```php
// src/Command/RelayOutboxEventsCommand.php
class RelayOutboxEventsCommand extends Command
{
    public function execute(): int
    {
        $eventsToRelay = $this->outboxRepo->findUnprocessed();
        
        foreach ($eventsToRelay as $outboxEvent) {
            try {
                // Отправляем событие во внешнюю шину
                $this->realMessageBroker->publish($outboxEvent->getDomainEvent());
                
                $this->outboxRepo->markAsProcessed($outboxEvent);
            } catch (\Exception $e) {
                // Логируем ошибку, повторим отправку при следующем запуске.
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