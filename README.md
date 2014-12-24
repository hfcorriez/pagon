Pagon
=====

Pagon is a modern php framework with elegant syntax. It also enables you to quickly and easily build RESTful web applications.

[中文文档]

## Quick Started

### Installation

Pagon utilizes [Composer] to manage its dependencies. First you need to have `Composer` which you should install globally.

You can also download a copy of the `composer.phar` in your repo's root, run a command such as the following:

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

In your development environment, you can use Pagon built-in debugger server for rapid development. But when you deployed your project in production environment, please use a ` Nginx` or `Apache` server.

``` bash
$ ./bin/pagon serve
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

## Cli

## Showcase

inews: [https://github.com/Trimidea/inews]

## License

Copyright (c) 2014 hfcorriez. MIT Licensed, see [LICENSE] for details.

[中文文档]: https://github.com/hfcorriez/pagon/blob/master/README_CN.md
[Composer]: https://getcomposer.org/
[Download]: https://github.com/hfcorriez/pagon/releases/latest
[Paris Doc]: http://paris.readthedocs.org/en/latest/philosophy.html
[https://github.com/Trimidea/inews]: https://github.com/Trimidea/inews
[LICENSE]:https://github.com/hfcorriez/pagon/blob/master/LICENSE.md
