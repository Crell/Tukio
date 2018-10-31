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

## Usage


Tukio implements both the Message and Task components of PSR-14.  As with PSR-14 itself it is broken up into a series of collaborating objects.  (These instructions assume you are already familiar with PSR-14.)

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
