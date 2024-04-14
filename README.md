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

If no order is specified, then the order that Listeners will be returned is undefined, and in practice users should expect the order to be stable, but not predictable.  That makes the degenerate case super-easy:

```php
use Crell\Tukio\OrderedListenerProvider;

class StuffHappened {}

class SpecificStuffHappened extends StuffHappened {}

function handleStuff(StuffHappened $stuff) { ... }


$provider = new OrderedListenerProvider();

$provider->listener(function(SpecificStuffHappened) {
    // ...  
});

$provider->listener('handleStuff');
```

That adds two Listeners to the Provider; one anonymous function and one named function.  The anonymous function will be called for any `SpecificStuffHappened` event.  The named function will be called for any `StuffHappened` *or* `SpecificStuffHappened` event.  And the user doesn't really care which one happens first (which is the typical case).

#### Ordering listeners

However, the user can also be picky about the order in which Listeners will fire.  Tukio supports two ordering mechanisms: Priority order, and Topological sorting (before/after markers).  Internally, Tukio will convert priority ordering into topological ordering.

```php
use Crell\Tukio\OrderedListenerProvider;

$provider = new OrderedListenerProvider();

$provider->listener(function(SpecificStuffHappened) {
    // ...  
}, priority: 10);

$provider->listener('handleStuff', priority: 20);
```

Now, the named function Listener will get called before the anonymous function does.  (Higher priority number comes first, and negative numbers are totally legal.) If two listeners have the same priority then their order relative to each other is undefined.

Sometimes, though, you may not know the priority of another Listener, but it's important your Listener happen before or after it.  For that we need to add a new concept: IDs.  Every Listener has an ID, which can be provided when the Listener is added or will be auto-generated if not.  The auto-generated value is predictable (the name of a function, the class-and-method of an object method, etc.), so in most cases it's not necessary to read the return value of `listener()` although that is slightly more robust.

```php
use Crell\Tukio\OrderedListenerProvider;

$provider = new OrderedListenerProvider();

// The ID will be "handleStuff", unless there is such an ID already,
//in which case it would be "handleStuff-1" or similar.
$id = $provider->listener('handleStuff');

// This Listener will get called before handleStuff does. If you want to specify an ID
// you can, since anonymous functions just get a random string as their generated ID.
$provider->listener($id, function(SpecificStuffHappened) {
    // ...  
}, before: ['my_specifics']);
```

Here, the priority of `handleStuff` is undefined; the user doesn't care when it gets called.  However, the anonymous function, if it should get called at all, will always get called after `handleStuff` does.  It's possible that some other Listener could also be called in between the two, but one will always happen after the other.

The `listener()` method is used for all registration, and can accept a priority, a list of listener IDs the new listener must come before, and a list of listener IDs the new listener must come after.  It also supports specifying a custom ID, and a custom `$type`.

Because that's a not-small number of options, it is *strongly recommended* that you use named arguments for all arguments other than the listener callable itself.

```php
public function listener(
    callable $listener,
    ?int $priority = null,
    array $before = [],
    array $after = [],
    ?string $id = null,
    ?string $type = null
): string;
```

The `listener()` method will always return the ID that was used for that listener.  If desired the `$type` parameter allows a user to specify the Event type that the Listener is for if different than the type declaration in the function.  For example, if the Listener doesn't have a type declaration or should only apply to some parent class of what it's type declaration is.  (That's a rare edge case, which is why it's the last parameter.)

#### Service Listeners

Often, though, Listeners are themselves methods of objects that should not be instantiated until and unless needed.  That's exactly what a Dependency Injection Container allows, and `OrderedListenerProvider` fully supports those, called "Service Listeners."  They work almost exactly the same, except you specify a service and method name:

```php
public function listenerService(
    string $service,
    ?string $method = null,
    ?string $type = null,
    ?int $priority = null,
    array $before = [],
    array $after = [],
    ?string $id = null
): string;
```

The `$type`, `$priority`, `$before`, `$after`, and `$id` parameters work the same way as for `listener()`.  `$service` is any service name that will be retrieved from a container on-demand, and `$method` is the method on the object.

If the service name is the same as that of a found class (which is typical in most modern conventions), then Tukio can attempt to derive the method and type from the class.  If the service name is not the same as a defined class, it cannot do so and both `$method` and `$type` are required and will throw an exception if missing.

If no `$method` is specified and the service name matches a class, Tukio will attempt to derive the method for you.  If the class has only one method, that method will be automatically selected.  Otherwise, if there is a `__invoke()` method, that will be automatically selected.  Otherwise, the auto-detection fails and an exception is thrown.

The services themselves can be from any [PSR-11](http://www.php-fig.org/psr/psr-11/)-compatible Container.

```php
use Crell\Tukio\OrderedListenerProvider;

class SomeService
{
    public function methodA(ThingHappened $event): void { ... }

    public function methodB(SpecificThingHappened $event): void { ... }
}

class MyListeners
{
    public function methodC(WhatHappened $event): void { ... }
    
    public function somethingElse(string $beep): string { ... }
}

class EasyListening
{
    public function __invoke(SpecificThingHappened $event): void { ... } 
}

$container = new SomePsr11Container();
// Configure the container somehow.
$container->register('some_service', SomeService::class);
$container->register(MyListeners::class, MyListeners::class);
$container->register(EasyListening::class, EasyListening::class);

$provider = new OrderedListenerProvider($container);

// Manually register two methods on the same service.
$idA = $provider->listenerService('some_service', 'methodA', ThingHappened::class);
$idB = $provider->listenerService('some_service', 'methodB', SpecificThingHappened::class);

// Register a specific method on a derivable service class.
// The type (WhatHappened) will be derived automatically.
$idC = $provider->listenerService(MyListeners::class, 'methodC', after: 'some_service-methodB');

// Auto-everything!  This is the easiest option.
$provider->listenerService(EasyListening::class, before: $idC);
```

In this example, we have listener methods defined in three different classes, all of which are registered with a PSR-11 container.  In the first code block, we register two listeners out of a class whose service name does not match its class name.  In the second, we register a method on a class whose service name does match its class name, so we can derive the event type by reflection.  In the third block, we use a single-method listener class, which allows everything to be derived!

Of note, the `methodB` Listener is referencing the `methodA` listener by an explict ID.  The generated ID is as noted predictable, so in most cases you don't need to use the return value.  The return value is the more robust and reliable option, though, as if the requested ID is already in-use a new one will be generated.

#### Attribute-based registration

The preferred way to configure Tukio, however, is via attributes.  There are four relevant attributes: `Listener`, `ListenerPriority`, `ListenerBefore`, and `ListenerAfter`.  All can be used with sequential parameters or named parameters.  In most cases, named parameters will be more self-documenting.  All attributes are valid only on functions and methods.

* `Listener` declares a callable a listener and optionally sets the `id` and `type`: `#[Listener(id: 'a_listener', type: 'SomeClass')].
* `ListenerPriority` has a required `priority` parameter, and optional `id` and `type: `#[ListenerPriority(5)]` or `#[ListenerPriority(priority: 3, id: "a_listener")]`.
* `ListenerBefore` has a required `before` parameter, and optional `id` and `type: `#[ListenerBefore('other_listener')]` or `#[ListenerBefore(before: 'other_listener', id: "a_listener")]`.
* `ListenerAfter` has a required `after` parameter, and optional `id` and `type: `#[ListenerAfter('other_listener')]` or `#[ListenerAfter(after: ['other_listener'], id: "a_listener")]`.

The `$before` and `$after` parameters will accept either a single string, or an array of strings.

As multiple attributes may be included in a single block, that allows for compact syntax like so:

```php
#[Listener(id: 'a_listener'), ListenerBefore('other'), ListenerAfter('something', 'else')]
function my_listener(SomeEvent $event): void { ... }

// Or just use the one before/after you care about:
#[ListenerAfter('something_early')]
function other(SomeEvent $event): void { ... }
```

If you pass a listener with Listener attributes to `listener()` or `listenerService()`, the attribute defined configuration will be used.  If you pass configuration in the method signature, however, that will override any values taken from the attributes.

### Subscribers

A "Subscriber" (a name openly and unashamedly borrowed from Symfony) is a class with multiple listener methods on it.  Tukio allows you to bulk-register any listener-like methods on a class, just by registering the class.

```php
$provider->addSubscriber(SomeCollectionOfListeners::class, 'service_name');
```

As before, if the service name is the same as that of the class, it may be omitted.  A method will be registered if either:

* it has any `Listener*` attributes on it.
* the method name begins with `on`.

For example:

```php
class SomeCollectionOfListeners
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

### Listener classes

As hinted above, one of the easiest ways to structure a listener is to make it the only method on a class, particularly if it is named `__invoke()`, and give the service the same name as the class.  That way, it can be registered trivially and derive all of its configuration through attributes.  Since it is extremely rare for a listener to be registered twice (use cases likely exist, but we are not aware of one), this does not cause a name collision issue.

Tukio has two additional features to make it even easier.  One, if the listener method is `__invoke()`, then the ID of the listener will by default be just the class name.  Two, the Listener attributes may also be placed on the class, not the method, in which case the class-level settings will inherit to every method.

The result is that the easiest way to define listeners is as single-method classes, like so:

```php
class ListenerOne
{
    public function __construct(
        private readonly DependencyA $depA,
        private readonly DependencyB $depB,
    ) {}
    
    public function __invoke(MyEvent $event): void { ... }
}

#[ListenerBefore(ListenerOne::class)]
class ListenerTwo
{
    public function __invoke(MyEvent $event): void { ... }
}

$provider->listenerService(ListenerOne::class);
$provider->listenerService(ListenerTwo::class);
```

Now, the API call itself is trivially easy.  Just specify the class name.  `ListenerTwo::__invoke()` will be called before `ListnerOne::__invoke()`, regardless of the order in which they were registered.  When `ListenerOne` is requested from your DI container, the container will fill in its dependencies automatically.

This is the recommended way to write listeners for use with Tukio.

### Deprecated functionality

A few registration mechanisms left over from Tukio version 1 are still present, but explicitly deprecated.  They will be removed in a future version.  Please migrate off of them as soon as possible.

#### Dedicated registration methods

The following methods still work, but are just aliases around calling `listener()` or `listenerService()`.  They are less capable than just using `listener()`, as `listener()` allows for specifying a priority, before, and after all at once, including multiple before/after targets.  The methods below do neither.  Please migrate to `listener()` and `listenerService()`.

```php
public function addListener(callable $listener, ?int $priority = null, ?string $id = null, ?string $type = null): string;

public function addListenerBefore(string $before, callable $listener, ?string $id = null, ?string $type = null): string;

public function addListenerAfter(string $after, callable $listener, ?string $id = null, ?string $type = null): string;

public function addListenerService(string $service, string $method, string $type, ?int $priority = null, ?string $id = null): string;

public function addListenerServiceBefore(string $before, string $service, string $method, string $type, ?string $id = null): string;

public function addListenerServiceAfter(string $after, string $service, string $method, string $type, ?string $id = null): string;
```

#### Subscriber interface

In Tukio v1, there was an optional `SubscriberInterface` to allow for customizing the registration of methods as listeners via a static method that bundled the various `addListener*()` calls up within the class.  With the addition of attributes in PHP 8, however, that functionality is no longer necessary as attributes can do everything the Subscriber interface could, with less work.

The `SubscriberInterface` is still supported, but deprecated.  It will be removed in a future version.  Please migrate to attributes.

The basic case works like this:

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

### Compiled Provider

All of that registration and ordering logic is powerful, and it's surprisingly fast in practice.  What's even faster, though, is not having to re-register on every request.  For that, Tukio offers a compiled provider option.

The compiled provider comes in three parts: `ProviderBuilder`, `ProviderCompiler`, and a generated provider class.  `ProviderBuilder`, as the name implies, is an object that allows you to build up a set of Listeners that will make up a Provider.  They work exactly the same as on `OrderedListenerProvider`, and in fact it exposes the same `OrderedProviderInterface`.

`ProviderCompiler` then takes a builder object and writes a new PHP class to a provided stream (presumably a file on disk) that matches the definitions in the builder.  That built Provider is fixed; it cannot be modified and no new Listeners can be added to it, but all the ordering and sorting has already been done, making it notably faster (to say nothing of skipping the registration process itself).

Let's see it in action:

```php
use Crell\Tukio\ProviderBuilder;
use Crell\Tukio\ProviderCompiler;

$builder = new ProviderBuilder();

$builder->listener('listenerA', priority: 100);
$builder->listener('listenerB', after: 'listenerA');
$builder->listener([Listen::class, 'listen']);
$builder->listenerService(MyListener::class);
$builder->addSubscriber('subscriberId', Subscriber::class);

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

Alternatively, the compiler can output a file with an anonymous class.  In this case, a class name or namespace are irrelevant.

```php
// Write the generated compiler out to a file.
$filename = 'MyCompiledProvider.php';
$out = fopen($filename, 'w');

$compiler->compileAnonymous($builder, $out);

fclose($out);
```

Because the compiled container will be instantiated by including a file, but it needs a container instance to function, it cannot be easily just `require()`ed.  Instead, use the `loadAnonymous()` method on a `ProviderCompiler` instance to load it.  (It does not need to be the same instance that was used to create it.)

```php
$compiler = new ProviderCompiler();

$provider = $compiler->loadAnonymous($filename, $containerInstance);
```

But what if you want to have most of your listeners pre-registered, but have some that you add conditionally at runtime?  Have a look at the FIG's [`AggregateProvider`](https://github.com/php-fig/event-dispatcher-util/blob/master/src/AggregateProvider.php), and combine your compiled Provider with an instance of `OrderedListenerProvider`.

### Compiler optimization

The `ProviderBuilder` has one other trick.  If you specify one or more events via the `optimizeEvent($class)` method, then the compiler will pre-compute what listeners apply to it based on its type, including its parent classes and interfaces.  The result is a constant-time simple array lookup for those events, also known as "virtually instantaneous."

```php
use Crell\Tukio\ProviderBuilder;
use Crell\Tukio\ProviderCompiler;

$builder = new ProviderBuilder();

$builder->listener('listenerA', priority: 100);
$builder->listener('listenerB', after: 'listenerA');
$builder->listener([Listen::class, 'listen']);
$builder->listenerService(MyListener::class);
$builder->addSubscriber('subscriberId', Subscriber::class);

// Here's where you specify what events you know you will have.
// Returning the listeners for these events will be near instant.
$builder->optimizeEvent(EvenOne::class);
$builder->optimizeEvent(EvenTwo::class);

$compiler = new ProviderCompiler();

// Write the generated compiler out to a file.
$filename = 'MyCompiledProvider.php';
$out = fopen($filename, 'w');

$compiler->compileAnonymous($builder, $out);

fclose($out);
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

If you discover any security related issues, please use the [GitHub security reporting form](https://github.com/Crell/Tukio/security) rather than the issue queue.

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
