# Tukio

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]


Tukio is a complete and robust implementation of the [PSR-14](http://www.php-fig.org/psr/psr-14/) Event Dispatcher specification.  It supports normal and debug Event Dispatchers, both runtime and compiled Providers, complex ordering of Listeners, and attribute-based registration on PHP 8.

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

In practice most of that will be handled through a Dependency Injection Container, but there's no requirement that it do so.

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

Sometimes, though, you may not know the priority of another Listener, but it's important your Listener happen before or after it.  For that we need to add a new concept: IDs.  Every Listener has an ID, which can be provided when the Listener is added or will be auto-generated if not.  The auto-generated value is predictable (the name of a function, the class-and-method of an object method, etc.), so in most cases it's not necessary to read the return value of `addListener()` although that is slightly more robust.

```php
use Crell\Tukio\OrderedListenerProvider;

$provider = new OrderedListenerProvider();

// The ID will be "handleStuff", unless there is such an ID already,
//in which case it would be "handleStuff-1" or similar.
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

public function addListenerBefore(string $before, callable $listener, string $id = null, string $type = null): string;

public function addListenerAfter(string $after, callable $listener, string $id = null, string $type = null): string;
```

All three can register any callable as a Listener and return an ID.  If desired the `$type` parameter allows a user to specify the Event type that the Listener is for if different than the type declaration in the function.  For example, if the Listener doesn't have a type declaration or should only apply to some parent class of what it's type declaration is.  (That's a rare edge case, which is why it's the last parameter.)

#### Service Listeners

Often, though, Listeners are themselves methods of objects that should not be instantiated until and unless needed.  That's exactly what a Dependency Injection Container allows, and `OrderedListenerProvider` fully supports those, called "Service Listeners".  They work almost exactly the same, except you specify a service and method name:

```php
public function addListenerService(string $serviceName, string $methodName, string $type, $priority = 0, string $id = null): string;

public function addListenerServiceBefore(string $before, string $serviceName, string $methodName, string $type, string $id = null): string;

public function addListenerServiceAfter(string $after, string $serviceName, string $methodName, string $type, string $id = null) : string;
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

In this example, we assume that `$container` has two services defined: `some_service` and `some_other_service`.  (Creative, I know.)  We then register three Listeners: Two of them are methods on `some_service`, the other on `some_other_service`.  Both services will be requested from the container as needed, so won't be instantiated until the Listener method is about to be called.

Of note, the `methodB` Listener is referencing the `methodA` listener by an explict ID.  The generated ID is as noted predictable, so in most cases you don't need to use the return value.  The return value is the more robust and reliable option, though, as if the requested ID is already in-use a new one will be generated.

#### Subscribers

As in the last example, it's quite common to have multiple Listeners in a single service.  In fact, it's common to have a service that is nothing but listeners.  Tukio calls those "Subscribers" (a name openly and unashamedly borrowed from Symfony), and has specific support for them.

The basic case works like this:

```php
use Crell\Tukio\OrderedListenerProvider;

class Subscriber
{
    public function onThingsHappening(ThingHappened $event) : void { ... }

    public function onSpecialEvent(SpecificThingHappened $event) : void { ... }
}

$container = new SomePsr11Container();
// Configure the container so that the service 'listeners' is an instance of Subscriber above.

$provider = new OrderedListenerProvider($container);


$provider->addSubscriber(Subscriber::class, 'listeners');
```

That's it!  Because we don't know what the class of the service is it will need to be specified explicitly, but the rest is automatic.  Any public method whose name begins with "on" will be registered as a listener, and the Event type it is for will be derived from reflection, while the rest of the class is ignored.  There is no limit to how many listeners can be added this way.  The method names can be anything that makes sense in context, so make it descriptive.  And because the service is pulled on-demand from the container it will only be instantiated once, and not before it's needed.  That's the ideal.

Sometimes, though, you want to order the Listeners in the Subscriber, or need to use a different naming convention for the Listener method.  For that case, your Subscriber class can implement an extra interface that allows for manual registration:

```php
use Crell\Tukio\OrderedListenerProvider;
use Crell\Tukio\SubscriberInterface;

class Subscriber implements SubscriberInterface
{
    public function onThingsHappening(ThingHappened $event) : void { ... }

    public function onSpecialEvent(SpecificThingHappened $event) : void { ... }

    public function somethingElse(ThingHappened $event) : void { ... }

    public static function registerListeners(ListenerProxy $proxy) : void
    {
        $id = $proxy->addListener('somethingElse', 10);
        $proxy->addListenerAfter($id, 'onSpecialEvent');
    }
}

$container = new SomePsr11Container();
// Configure the container so that the service 'listeners' is an instance of Subscriber above.

$provider = new OrderedListenerProvider($container);

$provider->addSubscriber(Subscriber::class, 'listeners');
```

As before, `onThingsHappen()` will be registered automatically.  However, `somethingElse()` will also be registered as a Listener with a priority of 10, and `onSpecialEvent()` will be registered to fire after it.

In practice, using Subscribers is the most robust way to register Listeners in a production system and so is the recommended approach.  However, all approaches have their uses and can be used as desired.

If you are using PHP 8.0 or later, you can use attributes to register your subscriber methods instead.  That is preferred, in fact.  See the section below.

### Compiled Provider

All of that registration and ordering logic is powerful, and it's surprisingly fast in practice.  What's even faster, though, is not having to re-register on every request.  For that, Tukio offers a compiled provider option.

The compiled provider comes in three parts: `ProviderBuilder`, `ProviderCompiler`, and a generated provider class.  `ProviderBuilder`, as the name implies, is an object that allows you to build up a set of Listeners that will make up a Provider.  They work exactly the same as on `OrderedListenerProvider`, and in fact it exposes the same `OrderedProviderInterface`.

`ProviderCompiler` then takes a builder object and writes a new PHP class to a provided stream (presumably a file on disk) that matches the definitions in the builder.  That built Provider is fixed; it cannot be modified and no new Listeners can be added to it, but all of the ordering and sorting has already been done, making it notably faster (to say nothing of skipping the registration process itself).

Let's see it in action:

```php
use Crell\Tukio\ProviderBuilder;
use Crell\Tukio\ProviderCompiler;

$builder = new ProviderBuilder();

$builder->addListener('listenerA', 100);
$builder->addListenerAfter('listenerA', 'listenerB');
$builder->addListener([Listen::class, 'listen']);
$builder->addListenerService('listeners', 'listen', CollectingEvent::class);
$builder->addSubscriber('subscriber', Subscriber::class);

$compiler = new ProviderCompiler();

// Write the generated compiler out to a file.
$filename = 'MyCompiledProvider.php';
$out = fopen($filename, 'w');

// Here's the magic:
$compiler->compile($builder, $out, 'MyCompiledProvider', '\\Name\\Space\\Of\\My\\App');

fclose($out);
```

`$builder` can do anything that `OrderedListenerProvider` can do, except that it only supports statically-defined Listeners.  That means it does not support anonymous functions or methods of an object, but it will still handle functions, static methods, services, and subscribers just fine.  In practice when using a compiled container you will most likely want to use almost entirely service listeners and subscribers, since you'll most likely be using it with a container.

That gives you a file on disk named `MyCompiledProvider.php`, which contains `Name\Space\Of\My\App\MyCompiledProvider`.  (Name it something logical for you.)  At runtime, then, do this:

```php
// Configure the container such that it has a service "listeners"
// and another named "subscriber".

$container = new Psr11Container();
$container->addService('D', new ListenService());

include('MyCompiledProvider.php');

$provider = new Name\Space\Of\My\App\MyCompiledProvider($container);
```

And boom!  `$provider` is now a fully functional Provider you can pass to a Dispatcher.  It will work just like any other, but faster.


But what if you want to have most of your listeners pre-registered, but have some that you add conditionally at runtime?  Have a look at the FIG's [`AggregateProvider`](https://github.com/php-fig/event-dispatcher-util/blob/master/src/AggregateProvider.php), and combine your compiled Provider with an instance of `OrderedListenerProvider`.

### Attribute-based registration

If you are using PHP 8.0 or later, Tukio fully supports attributes as a means of registration for both `OrderedListenerProvider` and `ProviderBuilder`.  There are four relevant attributes: `Listener`, `ListenerPriority`, `ListenerBefore`, and `ListenerAfter`.  All can be used with sequential parameters or named parameters.  In most cases, named parameters will be more self-documenting.  All attributes are valid only on functions and methods.

* `Listener` declares a callable a listener and optionally sets the `id` and `type`: `#[Listener(id: 'a_listener', type: `SomeClass`)].
* `ListenerPriority` has a required `priority` parameter, and optional `id` and `type: `#[ListenerPriority(5)]` or `#[ListeenPriority(priority: 3, id: "a_listener")]`.
* `ListenerBefore` has a required `before` parameter, and optional `id` and `type: `#[ListenerBefore('other_listener')]` or `#[ListenerBefore(before: 'other_listener', id: "a_listener")]`.
* `ListenerAfter` has a required `after` parameter, and optional `id` and `type: `#[ListenerAfter('other_listener')]` or `#[ListenerAfter(after: 'other_listener', id: "a_listener")]`.

Each attribute matches a corresponding method, and the values in the attribute will be used as if they were passed directly to the `addListener*()` method.  If a value is passed directly to `addListener()` and specified in the attribute, the directly-passed value takes precedence.

If you call `addListenerBefore()`/`addListenerAfter()`, the `before`/`after`/`priority` attribute parameter is ignored in favor of the method's standard behavior.  That is, `addListenerBefore()`, if called with a function that has a `#[ListenerAfter]` attribute, will still add the listener "before" the specified other listener.

There are two common use cases for attributes: One, using just `#[Listener]` to define a custom `id` or `type` and then calling the appropriate `add*` method as normal.  The other is on Subscribers, where attributes completely eliminate the need for the `registerListeners()` method.  If you're on PHP 8.0, please use attributes instead of `registerListeners()`.  It's not deprecated yet, but it may end up deprecated sometime in the future.

For example:

```php
class SomeSubscriber
{
    // Registers, with a custom ID.
    #[Listener(id: 'a')]
    public function onA(CollectingEvent $event) : void
    {
        $event->add('A');
    }

    // Registers, with a custom priority.
    #[ListenerPriority(priority: 5)]
    public function onB(CollectingEvent $event) : void
    {
        $event->add('B');
    }

    // Registers, before listener "a" above.
    #[ListenerBefore(before: 'a')]
    public function onC(CollectingEvent $event) : void
    {
        $event->add('C');
    }

    // Registers, after listener "a" above.
    #[ListenerAfter(after: 'a')]
    public function onD(CollectingEvent $event) : void
    {
        $event->add('D');
    }

    // This still self-registers because of the name.
    public function onE(CollectingEvent $event) : void
    {
        $event->add('E');
    }

    // Registers, with a given priority despite its non-standard name.
    #[ListenerPriority(priority: -5)]
    public function notNormalName(CollectingEvent $event) : void
    {
        $event->add('F');
    }

    // No attribute, non-standard name, this method is not registered.
    public function ignoredMethodThatDoesNothing() : void
    {
        throw new \Exception('What are you doing here?');
    }
}
```


### `CallbackProvider`

The third option Tukio provides is a `CallbackProvider`, which takes an entirely different approach.  In this case, the Provider works only on events that have a `CallbackEventInterface`.  The use case is for Events that are carrying some other object, which itself has methods on it that should be called at certain times.  Think lifecycle callbacks for a domain object, for example.

To see it in action, we'll use an example straight out of Tukio's test suite:

```php
use Crell\Tukio\CallbackEventInterface;
use Crell\Tukio\CallbackProvider;

class LifecycleEvent implements CallbackEventInterface
{
    protected $entity;

    public function __construct(FakeEntity $entity)
    {
        $this->entity = $entity;
    }

    public function getSubject() : object
    {
        return $this->entity;
    }
}

class LoadEvent extends LifecycleEvent {}

class SaveEvent extends LifecycleEvent {}

class FakeEntity
{

    public function load(LoadEvent $event) : void { ... }

    public function save(SaveEvent $event) : void { ... }

    public function stuff(StuffEvent $event) : void { ... }

    public function all(LifecycleEvent $event) : void { ... }
}

$provider = new CallbackProvider();

$entity = new FakeEntity();

$provider->addCallbackMethod(LoadEvent::class, 'load');
$provider->addCallbackMethod(SaveEvent::class, 'save');
$provider->addCallbackMethod(LifecycleEvent::class, 'all');

$event = new LoadEvent($entity);

$provider->getListenersForEvent($event);
```

In this example, the provider is configured not with Listeners but with method names that correspond to Events.  Those methods are methods on the "subject" object.  The Provider will now return callables for `[$entity, 'load']` and `[$entity, 'all']` when called with a `LoadEvent`.  That allows a domain object itself to have Listeners on it that will get called at the appropriate time.

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
[ico-downloads]: https://img.shields.io/packagist/dt/Crell/Tukio.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/Crell/Tukio
[link-scrutinizer]: https://scrutinizer-ci.com/g/Crell/Tukio/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/Crell/Tukio
[link-downloads]: https://packagist.org/packages/Crell/Tukio
[link-author]: https://github.com/Crell
[link-contributors]: ../../contributors
