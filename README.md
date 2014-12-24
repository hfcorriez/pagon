Pagon
=====

Pagon is Modern PHP framework with elegant syntax. It also allow you to build RESTful web applications quickly and easily.

Other languages: [中文文档]

## Quick Started

### Installation

Pagon use [Composer] to manage its dependencies. First you need to have `Composer` installed globally.

You can also download a copy of the `composer.phar` in your repository's root, run a command such as the following:

``` bash
curl -s https://getcomposer.org/installer | php
```

#### via Download/Git

[Download] source files from Github or clone from Github

``` bash
$ git clone git@github.com:hfcorriez/pagon.git myapp
$ composer install # php composer.phar install
```

#### Via Composer Create-Project

``` bash
$ composer create-project pagon/pagon myapp # php composer.phar ...
```

### Debugger server

You can use Pagon built-in debugger server for rapid development under development environment. please use a ` Nginx` or `Apache` server for production environment.

``` bash
$ ./bin/pagon serve [-p|--port <PORT>]
Pagon serve at http://127.0.0.1:5000
```

## Configuration

All of the configuration files for the Pagon are stored in the `app/config` directory.

Optional attributes

```
mode        # Runing mode
debug       # Debug mode
views       # Template directory
buffer      # Open a buffer or not
timezone    # timezone (default: UTC)
charset     # charset (default: UTF-8)
autoload    # Auto load directory
error       # Handle error or not
routes      # Routes
names       # Router name, auto created
alias       # Class aliases, for short class name
namespaces  # Namespaces
engines     # Template engines
errors      # Default errors
stacks      # stacks
mounts      # mounts
bundles     # bundles
locals      # locals
url_rewrite # Url rewrite or not
```

## Routing

To get started, let's create our first route. In Pagon, the simplest route is a route to a Closure.

```php
// Init app
$app = Pagon::create();

// Routing with a callback function
$app->get('/', function($req, $res) {
  $res->render('index.php');
});

// Routing with a class method
$app->get('/users/:id', 'Web\\User');

// Specify named parameters in your routes
$app->post('/users/:id(/:op)', 'Web\\UserOperator');

// Run app
$app->run();

```

## Paris ORM

Pagon ships with a superb ORM: Paris. More detailed documentation and examples, please check out [Paris Doc] .

Simple Example

```php
$users = \Model\User::dispense()->where('status', 1)->find_many();
```

## Migration

Init Database schema

```bash
./bin/cli db:init
```

> Will execute file `app/migrations/schema.sql` 

Migrate, Status

```bash
$ ./bin/cli db:generate AddUserLoginTime
+f ./migrations/20141208030747_AddUserLoginTime.php

$ ./bin/cli db:status

 Status   Migration ID    Migration Name
-----------------------------------------
   down  20141208030747  AddUserLoginTime

$ ./bin/cli db:migrate
 == 20141208030747 AddUserLoginTime migrating
 == 20141208030747 AddUserLoginTime migrated 0.0084s
```

> Except `init` command, all the other commands map to [phpmig](https://github.com/davedevelopment/phpmig) to execute。

## Cli

> Manage PHP shell for *unix console

`./bin/cli`

```php
$app->command('db\:init', '\Command\DB\Init');
$app->command('queue\:start', '\Command\Queue\Start');
```

Execution

```bash
$ ./bin/cli db:init
$ ./bin/cli queue:start
```

## Showcase

iNews: [https://github.com/Trimidea/inews]

## License

Copyright (c) 2014-2015 hfcorriez. MIT Licensed, see [LICENSE] for details.

[中文文档]: https://github.com/hfcorriez/pagon/blob/master/README_CN.md
[Composer]: https://getcomposer.org/
[Download]: https://github.com/hfcorriez/pagon/releases/latest
[Paris Doc]: http://paris.readthedocs.org/en/latest/philosophy.html
[https://github.com/Trimidea/inews]: https://github.com/Trimidea/inews
[LICENSE]:https://github.com/hfcorriez/pagon/blob/master/LICENSE.md
