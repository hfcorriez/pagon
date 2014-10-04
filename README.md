## Pagon框架

[![Build Status](https://travis-ci.org/pagon/framework.png)](https://travis-ci.org/pagon/framework)
[![Build Status](https://drone.io/github.com/pagon/framework/status.png)](https://drone.io/github.com/pagon/framework/latest)

邮件列表: [https://groups.google.com/d/forum/pagonframework](https://groups.google.com/d/forum/pagonframework)

> 新版本框架已经分离，单独迁移到 [https://github.com/pagon/framework](https://github.com/pagon/framework)

## 快速开始

### 安装

下载

```bash
$ wget https://drone.io/github.com/hfcorriez/pagon/files/build/pagon-0.8.0.tar.gz
```

Git

```bash
$ git clone git@github.com:hfcorriez/pagon.git myapp
$ composer install
```

Composer

```
$ composer.phar create-project pagon/pagon myapp
```

### 代码

```php
$app = Pagon::create();

$app->get('/', function($req, $res) {
  $res->render('index.php');
});

$app->run();
```

### 运行

```bash
$ ./bin/pagon serve
Pagon serve at http://127.0.0.1:5000
```

> 内建服务器运行方式只适用于开发，生产环境请使用HTTP服务器来维护

### 预览

![运行欢迎页面](https://f.cloud.github.com/assets/119550/2168909/28e8f986-954e-11e3-8031-9c21079cbef6.jpg)

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