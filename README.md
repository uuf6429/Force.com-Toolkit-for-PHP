# Force.com Toolkit for PHP

The Force.com PHP Toolkit provides an easy-to-use wrapper for the Force.com Web Services SOAP API, presenting SOAP client implementations for both the enterprise and partner WSDLs.

See the [getting started guide](https://developer.salesforce.com/page/PHP_Toolkit_13.0_Getting_Started) for sample code to create, retrieve, update and delete records in the Force.com database.

## Upgrade

This newer version of the library is a major rewrite.

Migration should not be dificult since the public interface stayed the same or changed slightly (eg; some global constants are now class constants).

Here's an overview of what changed:
- **More Composer:** Requirements such as PHP extensions and 3rd-party PHP packages are now served through composer.
  This means once you install your extension with Composer, you won't have any further dependencies issues.
- **Namespacing:** Everything is now under `SForce` namespace. In particular this solves issues were very generic class names caused conflicts.
- **Cleaner Global Scope:** Since everything was moved to the namespace, things like constants or variables have also moved.
- **PSR-2:** Updated coding standard, also increases code quality. In particular, every class has its own file now.
- **Better Dev Env:** Contributing has become even easier. Code style is fixed automatically, running tests is easier, continuous test runs etc.

These changes come at a cost. The minimum supported PHP version is PHP 5.6. It _might_ work with older versions, but no guarantees.
You should upgraded immediately if you are still using an [unsupported PHP version](http://php.net/supported-versions.php).

## Installation

The easist way to include this library in your project is to use [Composer](getcomposer.org/):

```sh
composer require uuf6429/Force.com-Toolkit-for-PHP
```

Alternatively, the library can be loaded by any [PSR-4](https://www.php-fig.org/psr/psr-4/) autoloader.

# TODO
- [ ] Convert documentation to markdown
  - [ ] Point "getting started" link in readme to docs
- [ ] Use proper namespacing
- [ ] Add dependencies to composer (eg, ext-soap)
- [ ] Create tests
- [ ] Run tests against a developer edition
- [ ] One final commit to replace `uuf6429` with `developerforce`