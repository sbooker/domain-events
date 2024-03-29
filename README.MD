# sbooker/domain-events

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]
[![Build Status](https://travis-ci.org/sbooker/domain-events.svg?branch=2.x)](https://travis-ci.org/sbooker/domain-events)
[![codecov](https://codecov.io/gh/sbooker/domain-events/branch/2.x/graph/badge.svg?token=3uCI9t0M2Q)](https://codecov.io/gh/sbooker/domain-events)


## Installation
Install via [Composer][composer]:
```bash
composer require sbooker/domain-events
```

### Usage
#### Publish events
```php
<?php

namespace Domain;

use Sbooker\DomainEvents\DomainEntity;
use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\DomainEventCollector;

class SomethingOccurred extends  DomainEvent {}

class SomeAggregateRoot implements DomainEntity
{
    use DomainEventCollector;
    
    public function doSomethingOfDomainLogic()
    {
        // do
        $this->publish(new SomethingOccurred());
    }
}
```
#### Dispatch events
For dispatching events use Sbooker\Domain\Events\Publihser implementation
```php
class SomeAggregateRoot implements DomainEntity
{
    use DomainEventCollector;
    
    ...
}

$publisher = new class implements Publihser { ... }

$aggregateRoot = new SomeAggregateRoot();
$aggregateRoot->dispatchEvents($publisher)

```
In more complex cases where Aggregate Root has one or more Domain Entities 
you must override _DomainEventCollector::dispatchEvents_ as shown bellow:
```php
class Entity implements DomainEntity { ... }

class SomeAggregateRoot implements DomainEntity
{
    use DomainEventCollector { dispatchEvents as doDispatchEvents; }
    
    private Entity $entity;
   
    ...
    
    public function dispatchEvents(Publisher $publisher): void
    {
        $this->doDispatchEvents($publisher);
        $this->entity->dispatchEvents($publisher);
    }
}
```

## License
See [LICENSE][license] file.

[badge-release]: https://img.shields.io/packagist/v/sbooker/domain-events.svg?style=flat-square
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/sbooker/domain-events.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/sbooker/domain-events.svg?style=flat-square

[release]: https://img.shields.io/packagist/v/sbooker/domain-events
[license]: https://github.com/sbooker/domain-events/blob/master/LICENSE
[php]: https://php.net
[downloads]: https://packagist.org/packages/sbooker/domain-events

[composer]: https://getcomposer.org