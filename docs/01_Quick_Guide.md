# 安装

## 直接添加

```
$ composer.phar require pagon/pagon="*"
```

## 使用脚手架创建

> （使用 [`pagon/app`](https://github.com/pagon/app) skelton创建应用）

```
$ composer.phar create-project pagon/app myapp
```

## 单文件方式

```
wget https://github.com/hfcorriez/pagon/raw/0.8.0-dev/pack/pagon.core.php
```

# 使用

## Hello world

```php
$app = App::create();

$app->get('/', function($req, $res){
   $res->write('Hello world');
});

$app->run();
```

## 命令行模式

> 命令行模式仅能在命令行下运行

```php
$app = App::create();

$app->command('hello', function($req, $res){
   $res->write('Hello world');
});

$app->command('help', function($req, $res){
   $res->write('Help Guide');
});

$app->run();
```

## API

```php
$app = App::create();

$app->get('/users', function($req, $res){
  $res->json(array(
    array('name' => 'hfcorriez', 'id' => 1)
  ));
});

$app->run();
```