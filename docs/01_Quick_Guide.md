# 安装

## 发行版

[下载](https://github.com/hfcorriez/pagon/releases)

## Composer

### 已有项目

```
$ composer.phar require pagon/pagon="*"
```

### 新项目

> （使用 [`pagon/app`](https://github.com/pagon/app) skelton创建应用）

```
$ composer.phar create-project pagon/app myapp
```

## 单文件

下载

```
wget https://github.com/hfcorriez/pagon/raw/0.8.0-dev/pack/pagon.core.php
```

使用

```php
require('pagon.core.php');

$app = Pagon\App::create();
```

# 入门

## Hello world

```php
$app = App::create();

$app->get('/', function($req, $res){
   $res->write('Hello world');
});

$app->run();
```

## 命令行模式

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

> 命令行模式仅能在命令行下运行

## API

```php
$app = App::create();

$app->get('/users', function($req, $res){
  // 使用JSON输出
  $res->json(array(
    array('name' => 'hfcorriez', 'id' => 1)
  ));
});

$app->run();
```