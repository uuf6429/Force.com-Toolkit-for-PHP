# Force.com Toolkit for PHP

[![Build Status](https://travis-ci.org/uuf6429/Force.com-Toolkit-for-PHP.svg?branch=Major-refactor)](https://travis-ci.org/uuf6429/Force.com-Toolkit-for-PHP)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-orange.svg)](LICENSE)
[![Coverage](https://sonarcloud.io/api/badges/measure?metric=coverage&key=Force.com-Toolkit-for-PHP%3AMajor-refactor)](https://sonarcloud.io/dashboard?id=Force.com-Toolkit-for-PHP%3AMajor-refactor)
[![Packagist](https://img.shields.io/packagist/v/uuf6429/Force.com-Toolkit-for-PHP.svg)](https://packagist.org/packages/uuf6429/Force.com-Toolkit-for-PHP)

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
- **Bug Fixes:** Huge amount of bugs have been fixed: whitespace in output from some files, missing variables, redundant arguments, incorrect method calls etc...
- [**Schema Generator:**](#schema-generator) A tool for generating a rough DDL schema which you can use in your IDE to help writing SOQL queries.
- **Badges:** Now you can quickly get an overview of the project just by looking at the summary.
- [**Custom API Version:**](#wsdl-sources) You can use your own WSDL sources, which means you can use a different API version, potentially with access to more API entities.

These changes come at a cost. The minimum supported PHP version is PHP 5.6. It _might_ work with older versions, but no guarantees.
You should upgrade immediately if you are still using an [unsupported PHP version](http://php.net/supported-versions.php).

## Installation

The easist way to include this library in your project is to use [Composer](getcomposer.org/):

```sh
composer require uuf6429/Force.com-Toolkit-for-PHP
```

Alternatively, the library can be loaded by any [PSR-4](https://www.php-fig.org/psr/psr-4/) autoloader.

**Important:** This library generates classes from WSDL inside `src/SForce/Wsdl` by default.
Please see [WSDL Class Path](#wsdl-class-path) section to change this behaviour.

## Features

### WSDL Sources

The WSDL can be customized for your desired version and source. You can either provide your own WSDL file(s) or having your own code providing these.

**Note:** This library relies on one specific WSDL source, so for example, you cannot connect to two (or more) API endpoints with different WSDL sources. In this case, pick a common API level and use it for all.

To use your own WSDL source, first you have to add an "extras" entry to your `composer.json` and then you need to pass the correct WSDL path to `$client->createConnection($wsdl)`.
Here are a few `composer.json` examples:

- A class with a static method that returns a list of sources (as strings)
    ```json
    {
        "extra": {
            "sforce-wsdl-source": "MyWsdlSource::getSource"
        }
    }
    ```
- A function that returns a list of sources (as strings)
    ```json
    {
        "extra": {
            "sforce-wsdl-source": "myproject_get_wsdl_source"
        }
    }
    ```
- A list of URLs (must be accessible by composer)
    ```json
    {
        "extra": {
            "sforce-wsdl-source": [
                "https://my-sforce.com/soap/enterprise.wsdl",
                "https://my-sforce.com/soap/partner.wsdl",
                "https://my-sforce.com/soap/metadata.wsdl"
            ]
        }
    }
    ```
- A list of local files (relative to project root)
    ```json
    {
        "extra": {
            "sforce-wsdl-source": [
                "src/SForce/Wsdl/enterprise.wsdl",
                "src/SForce/Wsdl/partner.wsdl",
                "src/SForce/Wsdl/metadata.wsdl"
            ]
        }
    }
    ```

### WSDL Class Path

As mentioned in the previous section, some classes are generated from the SOAP WSDL, even if the default settings are used.
By default, these classes will end up in `src/SForce/Wsdl` of your project, however this can be changed via `composer.json`:

```json
{
    "extra": {
        "sforce-wsdl-classpath": "cache/SForce"
    }
}
```

- **Note 1:** The autoloader is automatically updated to point to the class path. You don't need to change it yourself. In other words: class are loaded automatically.
- **Note 2:** Every time you change the location, make sure to delete the generated files from the previous location. The generator cannot clean up for you since it won't know about the previous location.

### Schema Generator

The [SchemaGen class](src/SchemaGen.php) is a simple tool that generates a rough DDL for the SOAP API.
You can use the generated SQL schema file inside your IDE as a "DDL Data Source" (eg, in PhpStorm).
This allows the IDE to suggest useful information when you're writing your SOQL queries. Here's how it looks like:

![SchemaGen](https://imgfy.me/images/image6b7b0c2fb3239b82.png)

## Testing

Unit tests only require PHPUnit (installed by default via Composer), so you can simply run the following:

```sh
vendor/bin/phpunit test/Unit
```

Integration tests require a real SalesForce account and you need to provide the credentials as environment variables:

```sh
SALESFORCE_USER="john@doe.com"
SALESFORCE_PASS="Som3p4ssw0rd"
SALESFORCE_TOKEN="b0dca2fa0b3ef1a5bf5ba9dd6bdf0fca"
vendor/bin/phpunit test/Integration
```

## TODO

- [ ] Convert documentation to markdown
  - [ ] Point "getting started" link in readme to docs
- [x] Use proper namespacing
- [x] Add dependencies to composer (eg, ext-soap)
- [ ] Create tests
- [x] Run tests against a developer edition
- [ ] Check and set up code coverage
- [ ] Final cleanup commit
  - [ ] replace `uuf6429` with `developerforce` (eg; readme, composer)
  - [ ] replace `Major-refactor` with `master` (eg; badges)