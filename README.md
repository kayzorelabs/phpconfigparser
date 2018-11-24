ConfigParser - A Configuration File Parser for PHP 7
=======================================================

[![Build Status](https://travis-ci.org/kayzorelabs/phpconfigparser.svg?branch=master)](https://travis-ci.org/kayzorelabs/phpconfigparser)

What is ConfigParser?
------------------------------------

ConfigParser is a configuration file parser for PHP 7.

The ConfigParser class provides a way to read, interpret and write configuration files with structure similar to what’s found in Microsoft Windows INI files.

Requirements
============

* PHP 7.1 and up.

License
========

ConfigParser is licensed under the LGPLv3 License. See the LICENSE file for details.

Installation (Composer)
=======================

### 0. Install Composer

If you don't have Composer yet, download it following the instructions on
http://getcomposer.org/ or just run the following command:

``` bash
curl -s http://getcomposer.org/installer | php
```

### 1. Add the kayzorelabs/phpconfigparser package in your project

Run composer require command

```
composer.phar require kayzorelabs/phpconfigparser
```

Or add the kayzore/configparser package in your composer.json

```json
{
    "require": {
        "kayzorelabs/phpconfigparser": "dev-master"
    }
}
```
And tell composer to download the package by running the command:

```bash
$ php composer.phar update kayzorelabs/phpconfigparser
```

Composer will install the bundle to your project's `vendor/kayzorelabs` directory.

Documentation
==============

Basic instructions on the usage of the library are presented below.

Supported INI File Structure
----------------------------

A configuration file consists of sections, each led by a `[section]` header,  followed by `name = value`  entries..

Leading and trailing whitespace is removed from keys and values. Values can be omitted, these will be stored as an empty string.

Configuration files may include comments, prefixed by `;`. Hash marks (`#` ) may no longer be used as comments and will throw a deprecation warning if used.

Usage
------------------------------------

First, take the following INI file as an example:

    [DEFAULT]
    ServerAliveInterval = 45
    Compression = yes
    CompressionLevel = 9
    ForwardX11 = yes

    [github.com]
    user = foo

    [topsecret.server.com]
    Port = 50022
    ForwardX11 = no

TODO

Development
===========

Original Authors
------------------------------------

* Kayzore Labs - <kayzorelabs@gmail.com>

Submitting bugs and feature requests
------------------------------------

Bugs and feature requests are tracked on [GitHub](https://github.com/kayzorelabs/phpconfigparser/issues).

Running tests
------------------------------------

TODO