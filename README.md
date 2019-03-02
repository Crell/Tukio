# Tukio

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]


Tukio is a complete and robust implementation of the [PSR-14](http://www.php-fig.org/psr/psr-14/) Event Dispatcher specification.

PSR-14 is still in draft.  This library will be updated accordingly as PSR-14 evolves.  A stable release will be made once PSR-14 is complete.

"Tukio" is the Swahili word for "Event".

## Install

Via Composer

``` bash
$ composer require crell/tukio
```

## PSR-14 Usage

PSR-14 consists of two key components: A Dispatcher and a Provider.  A Dispatcher is what a client library will compose and then pass Events objects to.  A Provider is what a framework will give to a Dispatcher to match the Event to Listeners.  Tukio includes multiple implementations of both.

The general structure of using any PSR-14-compliant implementation is:

```php
$provider = new SomeProvider();
// Do some sort of setup on $provider so that it knows what Listeners it should match to what Events.
// This could be pretty much anything depending on the implementation.

$dispatcher = new SomeDispatcher($provider);

// Pass $dispatcher to some client code somewhere, and then:

$thingHappened = new ThingHappened($thing);

$dispatcher->dispatch($thingHappened);
``` 

Note that `dispatch()` will return `$thingHappened` as well, so if Listeners are expected to add data to it then you can chain a method call on it if desired:

```php
$reactions = $dispatcher->dispatch(new ThingHappened($thing))->getReactions();
```

In practice most of that will be handled through a Dependency Injection Container most of the time, but there's no requirement that it do so.

## Dispatchers

Tukio includes two Dispatchers: `Dispatcher` is the standard implementation, which should be used 95% of the time.  It can optionally take a [PSR-3 Logger](http://www.php-fig.org/psr/psr-14/), in which case it will log a warning on any exception thrown from a Listener.

The second is `DebugEventDispatcher`, which like the names says is useful for debugging.  It logs every Event that is passed and then delegates to a composed dispatcher (such as `Dispatcher`) to actually dispatch the Event.

For example:

```php
use Crell\Tukio\Dispatcher;
use Crell\Tukio\DebugEventDispatcher;

$provider = new SomeProvider();

$logger = new SomeLogger();

$dispatcher = new Dispatcher($provider, $logger);

$debugDispatcher = new DebugEventDispatcher($dispatcher, $logger);

// Now pass $debugDispatcher around and use it like any other dispatcher.
```

## Providers

Tukio includes multiple options for building Providers, and it's encouraged that you combine them with the generic ones included in the [`fig/event-dispatcher-util`](https://github.com/php-fig/event-dispatcher-util) library provided by the FIG.

Which Provider or Providers you use depends on your use case.  All of them are valid for certain use cases.

### `OrderedListenerProvider`

As the name implies, `OrderedListenerProvider` is all about ordering.  Users can explicitly register Listeners on it that will be matched against an Event based on its type.

If no order is specified, then the order that Listeners will be returned is undefined (although in practice order-added is what it will most likely be).  That makes the degenerate case super-easy:

```php
use Crell\Tukio\OrderedListenerProvider;

class StuffHappened {}

class SpecificStuffHappened extends StuffHappened {}

function handleStuff(StuffHappened $stuff) { ... }


$provider = new OrderedListenerProvider();

$provider->addListener(function(SpecificStuffHappened) {
    // ...  
});

$provider->addListener('handleStuff');
```

That adds two Listeners to the Provider; one anonymous function and one named function.  The anonymous function will be called for any `SpecificStuffHappened` event.  The named function will be called for any `StuffHappened` *or* `SpecificStuffHappened` event.  And the user doesn't really care which one happens first (which is the typical case).

#### Ordering listeners

However, the user can also be picky about the order in which Listeners will fire.  The most common approach for that today is via an integral "priority", and Tukio offers that:

```php
use Crell\Tukio\OrderedListenerProvider;

$provider = new OrderedListenerProvider();

$provider->addListener(function(SpecificStuffHappened) {
    // ...  
}, 10);

$provider->addListener('handleStuff', 20);
```

Now, the named function Listener will get called before the anonymous function does.  (Higher priority number comes first, and negative numbers are totally legal.) If two listeners have the same priority then their order relative to each other is undefined.

Sometimes, though, you may not know the priority of another Listener but it's important that your Listener happen before or after it.  For that we need to add a new concept: IDs.  Every Listener has an ID, which can be provided when the Listener is added or will be auto-generated if not.  The auto-generated value is predictable (the name of a function, the class-and-method of an object method, etc.), so in most cases it's not necessary to read the return value of `addListener()` although that is slightly more robust.

```php
use Crell\Tukio\OrderedListenerProvider;

$provider = new OrderedListenerProvider();

// The ID will be "handleStuff", unless there is such an ID already,
//in which case it would be "handleStuff_1" or similar.
$id = $provider->addListener('handleStuff');

// This Listener will get called before handleStuff does. If you want to specify an ID
// you can, since anonymous functions just get a random string as their generated ID.
$provider->addListenerBefore($id, function(SpecificStuffHappened) {
    // ...  
}, 'my_specifics');
```

Here, the priority of `handleStuff` is undefined; the user doesn't care when it gets called.  However, the anonymous function, if it should get called at all, will always get called after `handleStuff` does.  It's possible that some other Listener could also be called in between the two, but one will always happen after the other.

The full API for those three methods is:

```php
public function addListener(callable $listener, $priority = 0, string $id = null, string $type = null): string;

public function addListenerBefore(string $pivotId, callable $listener, string $id = null, string $type = null): string;

public function addListenerAfter(string $pivotId, callable $listener, string $id = null, string $type = null): string;
```

All three can register any callable as a Listener and return an ID.  If desired the `$type` parameter allows a user to specify the Event type that the Listener is for if different than the type declaration in the function.  For example, if the Listener doesn't have a type declaration or should only apply to some parent class of what it's type declaration is.  (That's a rare edge case, which is why it's the last parameter.)

#### Service Listeners

Often, though, Listeners are themselves methods of objects that should not be instantiated until and unless needed.  That's exactly what a Dependency Injection Container allows, and `OrderedListenerProvider` fully supports those, called "Service Listeners".  They work almost exactly the same, except you specify a service and method name:

```php
public function addListenerService(string $serviceName, string $methodName, string $type, $priority = 0, string $id = null): string;

public function addListenerServiceBefore(string $pivotId, string $serviceName, string $methodName, string $type, string $id = null): string;

public function addListenerServiceAfter(string $pivotId, string $serviceName, string $methodName, string $type, string $id = null) : string;
```

All three take a service name and method name to identify a Listener.  However, since the callable itself doesn't exist yet it's not possible to derive the Event it should listen to through reflection.  It must be specified explicitly.  All three also return an ID, so the same "priority-or-before/after" functionality is supported.

The services themselves can be from any [PSR-11](http://www.php-fig.org/psr/psr-11/)-compatible Container.

```php
use Crell\Tukio\OrderedListenerProvider;

$container = new SomePsr11Container();
// Configure the container somehow.

$provider = new OrderedListenerProvider($container);

$provider->addListenerService('some_service', 'methodA', ThingHappened::class);

$id = $provider->addListenerServiceBefore('some_service-methodA', 'some_service', 'methodB', SpecificThingHappened::class);

$provider->addListenerServiceAfter($id, 'some_other_service', 'methodC', SpecificThingHappened::class);
```

In this example, we assume that `$container` has two services defined: `some_service` and `some_other_service`.  (Creative, I know.)  We then register three Listners: Two of them are methods on `some_service`, the other on `some_other_service`.  Both services will be requested from the container as needed, so won't be instantiated until the Listener method is about to be called.

Of note, the `methodB` Listener is referencing the `methodA` listener by an explict ID.  The generated ID is as noted predicatable, so in most cases you don't need to use the return value.  The return value is the more robust and reliable option, though, as if the requested ID is already taken a new one will be generated. 


### Registering listeners

There are two different ListenerProviders included.  Either one can be used.  The first is the `RegisterableListenerProvider`, which lets you register listeners by calling methods on it:

```php
$provider = new RegisterableListnerProvider():

$provider->addListener(function (ImportantTask $task) {
    // ...
});
```

That's it!  You can register any legal PHP callable: An anonymous function (as shown above), a function name, an object/method array, or a class/method array for a static method.

There's no need to specify what type of event the listener is for.  That will be derived automatically from the function itself, as long as you type-hint the parameter.  In the example above, the provider will register this callable for any event of type `ImportantTask`, or any subclass of `ImportantTask`.  Any event that is an `instanceof ImportantTask` will get passed to this listener.

That means you can listen on an interface, too!

```php
$provider->addListener(function (LifecyleEventInterface $task) {
  // ...
});
```

That listener will now be called for any event that implements `LifecycleEventInterface`.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email larry@garfieldtech.com instead of using the issue tracker.

## Credits

- [Larry Garfield][link-author]
- [All Contributors][link-contributors]

## License

The Lesser GPL version 3 or later. Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/Crell/Tukio.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/License-LGPLv3-green.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/Crell/Tukio/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/Crell/Tukio.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/Crell/Tukio.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/Crell/Tukio.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/Crell/Tukio
[link-travis]: https://travis-ci.org/Crell/Tukio
[link-scrutinizer]: https://scrutinizer-ci.com/g/Crell/Tukio/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/Crell/Tukio
[link-downloads]: https://packagist.org/packages/Crell/Tukio
[link-author]: https://github.com/Crell
[link-contributors]: ../../contributors
