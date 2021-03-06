# Simple DIC

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/5bee3c5a5e774e5aba1fcf9f622f08d2)](https://www.codacy.com/app/mauretto78_2/simple-dic?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=mauretto78/simple-dic&amp;utm_campaign=Badge_Grade)
[![license](https://img.shields.io/github/license/matecat/simple-dic.svg)]()
[![Packagist](https://img.shields.io/packagist/v/matecat/simple-dic.svg)]()
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/matecat/simple-dic/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mauretto78/simple-dic/?branch=master)

**Simple DIC** is a simple Dependency Injection Container(DIC).

## Basic Usage

To init DIC you must provide a configuration file. Use `initFromFile` method:

Here is a sample (in YAML format):

```yaml
dummy-key: 'dummy-value'
dummy-array: [43243,2432,4324,445667]
three: 3
two: 2
acme:
  class: Matecat\SimpleDIC\Dummy\Acme
acme-calculator:
  class: Matecat\SimpleDIC\Dummy\AcmeCalculator
  method: init
  method_arguments: ['@three', '@two']
acme-parser:
  class: Matecat\SimpleDIC\Dummy\AcmeParser
  arguments: ['string']
acme-repo:
  class: Matecat\SimpleDIC\Dummy\AcmeRepo
  arguments: ['@acme']
```

After pass the name of dependency, you can specify:

* `class` : the full qualified class name 
* `arguments`: an array of arguments to pass to instantiate the class from the constructor
* `method`: if you want to run a specific method of the class (method could be static or not not)
* `method_arguments`: an array of arguments to pass to instantiate the runned class method

If you want to pass an entry already present to other one, simply use the '@' symbol.

## Change Caching Directory

You cah use `setCacheDir` to setup yor cache directory. Do this **before** invoke `initFromFile` method:

```php
DIC::setCacheDir(__DIR__.'/../_cache_custom');
DIC::initFromFile(__DIR__ . '/../config/ini/redis.ini');

// ...
```

## Parameters

If you want to use a separate parameters file, you can use `DICParams` class. Take a look at the following example of params configuration file
 (YAML format):

```yaml
your_secret_token: 'YOUR_SECRET_TOKEN'
your_secret_password: 'YOUR_SECRET_PASS'
```

You can setup `DICParams` class now:

```php
use Matecat\SimpleDIC\DICParams;

DICParams::initFromFile('your_params_file.yaml');
```

And then, you can use '%' synthax in your DIC configuration file. Please bear in mind that you MUST set parameters **before** instantiate DIC. 

```yaml
client:
    class: 'SimpleDIC\Dummy\Client'
    arguments: ['%your_secret_token%', '%your_secret_password%']
```

## Environment Variable Support

To use your environment variable, simply follow the `%env(xxxx)%` synthax, consider this example:

```yaml
logger:
    class: 'Matecat\SimpleDIC\Dummy\Logger'
    arguments: ['%env(FOO)%']
```

## Retrieve an entry

In order to retrieve an entry use `get` method:

```php
use Matecat\SimpleDIC\DIC;

$dependency = DIC::get('key');
```

Please note that the method returns:
* `false` if the entry has a wrong configuration;
* `NULL` if the entry does not exists.

## Lazy loading and automatic caching

The entries are **lazy-loaded** when you invoke `get` or `has` method for the first time. 

If you have apcu enabled on your system, DIC will automatically cache the entry in APCU store. Please note that the **id in cache always refers to the sha1() of the init file**.

## Commands

If you have an application which uses [Symfony Console](https://github.com/symfony/console), you have some commands available:

*  ```dic:debug```     Dumps the entry list in the DIC.

You can register the commands in your app, consider this example:

```php
#!/usr/bin/env php
<?php
set_time_limit(0);

...

use Symfony\Component\Yaml\Yaml;

// create symfony console app
$app = new \Symfony\Component\Console\Application('Simple DIC', 'console tool');

// config
$configFile = __DIR__.'/../config/yaml/config.yaml';

// add commands here
$app->add(new \Matecat\SimpleDIC\Console\DICDebug($configFile));
$app->run();
```

## Support

If you found an issue or had an idea please refer [to this section](https://github.com/mauretto78/simple-dic/issues).

## Authors

* **Mauro Cassani** - [github](https://github.com/mauretto78)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details