# Changelog

All notable changes to `Tukio` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

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
