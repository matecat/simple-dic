# Simple DIC

**Simple DIC** is a simple Dependency Injection Container(DIC) built on top of Pimple.

## Basic Usage

To use DIC do the following:

```php
use SimpleDIC\DIC;

$config = [...];

DIC::init($config);
```

## Configuring DIC

To init 



For further config details please refer to the official documentation:

[Configuration for the AWS SDK for PHP Version 3](https://docs.aws.amazon.com/en_us/sdk-for-php/v3/developer-guide/guide_configuration.html#credentials)

## Retrive an entry

In order to retrieve an entry simple do this:

```php
use SimpleDIC\DIC;

$dependency = DIC::get('key');
```

// The method returns:
* `false` if the entry has a wrong configuration;
* `NULL` if the entry does not exists.

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
$app = new \Symfony\Component\Console\Application('Simple S3', 'console tool');

// config
$config = Yaml::parseFile(__DIR__.'/../config/config.yaml');

// add commands here
$app->add(new \SimpleDIC\Console\DICDebug($config));
$app->run();
```

## Support

If you found an issue or had an idea please refer [to this section](https://github.com/mauretto78/simple-dic/issues).

## Authors

* **Mauro Cassani** - [github](https://github.com/mauretto78)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details