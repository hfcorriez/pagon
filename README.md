## Pagon框架

## 快速开始

### 安装

[从 Github 下载](https://github.com/hfcorriez/pagon/releases/latest)

从 Git 安装

```bash
$ git clone git@github.com:hfcorriez/pagon.git myapp
$ composer install
```

从 Composer 安装

```bash
$ composer create-project pagon/pagon myapp
```

### 轻路由

```php
$app = Pagon::create();

// 匿名函数做为路由
$app->get('/', function($req, $res) {
  $res->render('index.php');
});

// 类做为路由
$app->get('/users/:id', 'Web\\User');

// 可选参数的路由，op 为可选参数
$app->post('/users/:id(/:op)', 'Web\\UserOperator');

// 运行应用
$app->run();
```

### 命令行

> 主要用户一些命令行交互和指令的统一管理

`./bin/cli`

```php
$app->command('db\:init', '\Command\DB\Init');
$app->command('queue\:start', '\Command\Queue\Start');
```

执行

```bash
$ ./bin/cli db:init
$ ./bin/cli queue:start
```

### 数据库

> 操作数据库采用 [Paris](https://github.com/j4mie/paris) 做为ORM。[查看文档](http://paris.readthedocs.org/en/latest/)

简单示例

```php
$users = \Model\User::dispense()->where('status', 1)->find_many();
```

初始化

```bash
./bin/cli db:init
```

> 会执行 app/migrations/schema.sql 到数据库

升级和状态

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

> 除了 init 外的指令，都会映射到 [phpmig](https://github.com/davedevelopment/phpmig) 的指令上。

### 运行

```bash
$ ./bin/pagon serve
Pagon serve at http://127.0.0.1:5000
```

> 内建服务器运行方式只适用于开发，生产环境请使用 Nginx 或 Apache 来运行。

### 预览

![运行欢迎页面](https://cloud.githubusercontent.com/assets/119550/5330562/63b05914-7e38-11e4-96f3-0a51a8aa4d01.jpg)

## 特性

- 用心设计的简单高效的现代应用框架。
- 拥有最小化核心组件且尽量不依赖三方库的简单、智能和高效的框架。
- 无需配置可开发，基于配置可方便开发。
- 标准化，规范化。
- 易扩展，易植入。
- 开发快速、高效，出色的运行性能。
- Web和Console下只需要一套代码
- `Write less, Do more!`

## License

(The MIT License)

Copyright (c) 2012 hfcorriez &lt;hfcorriez@gmail.com&gt;

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.