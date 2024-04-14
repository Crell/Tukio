# Changelog

All notable changes to `Tukio` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## 2.0.0 - 2024-04-14

### Added
- Major internal refactoring.
- There is now a `listener()` method on the Provider and Compiler classes that allows specifying multiple before/after rules at once, in addition to priority. It is *recommended* to use this method in place of the older ones.
- Similarly, there is a `listenerService()` method for registering any service-based listener.
- Upgraded to OrderedCollection v2, and switched to a Topological-based sort.  The main advantage is the ability to support multiple before/after rules.  However, this has a side effect that the order of listeners that had no relative order specified may have changed.  This is not an API break as that order was never guaranteed, but may still affect some order-sensitive code that worked by accident.  If that happens, and you care about the order, specify before/after orders as appropriate.
- Attributes are now the recommended way to register listeners.
- Attributes may be placed on the class level, and will be inherited by method-level listeners.

### Deprecated
- `SubscriberInterface` is now deprecated.  It will be removed in v3.
- the `addListener`, `addListenerBefore`, `addListenerAfter`, `addListenerService`, `addListenerServiceBefore`, and `addListenerServiceAfter` methods have been deprecated.  They will be removed in v3.  Use `listener()` and `listenerService()` instead.

### Fixed
- Nothing

### Removed
- Nothing

### Security
- Nothing


## 1.5.0 - 2023-03-25

### Added
- Tukio now depends on Crell/OrderedCollection, which used to be part of this library.

### Deprecated
- Nothing

### Fixed
- Nothing

## 1.4.1 - 2022-06-02

### Added
- Nothing

### Deprecated
- Nothing

### Fixed
- Moved phpstan to a dev dependency, where it should have been in the first place.

## 1.4.0 - 2022-03-30

### Added
- Added PHPStan and PHPBench as direct dev dependencies.

### Deprecated
- Nothing

### Fixed
- The codebase is now PHPStan Level 6 compliant.  There should be no functional changes.

### Removed
- Removed support for PHP < 7.4.  Stats show the number of such users is zero.
- Increased required PHPUnit version to support PHP 8.1
- Remove PHPInsights and the nasty vendor-bin workaround it required for dev.

### Security
- Nothing

## NEXT - YYYY-MM-DD

### Added
- Nothing

### Deprecated
- Nothing

### Fixed
- Nothing

### Removed
- Nothing

### Security
- Nothing
