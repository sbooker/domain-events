# sbooker/domain-events

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]

## Installation
Install via [Composer][composer]:
```bash
composer require sbooker/domain-events
```

### Configuration
in PHP
```php
<?php

use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\Publisher;
use Sbooker\DomainEvents\PublisherContainer;

class ConcretePublisher implements Publisher
{
    public function publish(DomainEvent $event) : void
    {
        // do publish event    
    } 
}

// ...

PublisherContainer::instance()->setPublisher(new ConcretePublisher());

// ...

```
in Symfony
```yaml
  Sbooker\DomainEvents\PublisherContainer:
    class: Sbooker\DomainEvents\PublisherContainer
    factory: [ Sbooker\DomainEvents\PublisherContainer, 'instance' ]
    calls:
      - method: setPublisher
        arguments:
          - '@ConcretePublisher'
```

### Usage
```php
<?php

namespace Domain;

use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\EventPublisherContainer;

class SomethingOccurred extends  DomainEvent {}

class SomeAggregateRoot
{
    use EventPublisherContainer;
    
    public function doSomethingOfDomainLogic()
    {
        // do
        $this->publish(new SomethingOccurred());
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