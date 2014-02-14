## Pagon框架

[![Build Status](https://travis-ci.org/pagon/framework.png)](https://travis-ci.org/pagon/framework)
[![Build Status](https://drone.io/github.com/pagon/framework/status.png)](https://drone.io/github.com/pagon/framework/latest)

邮件列表: [https://groups.google.com/d/forum/pagonframework](https://groups.google.com/d/forum/pagonframework)

> 新版本框架已经分离，单独迁移到 [https://github.com/pagon/framework](https://github.com/pagon/framework)

## 快速开始

### 安装

下载

```bash
$ wget https://drone.io/github.com/hfcorriez/pagon/files/build/pagon-0.8.0-dev.tar.gz
```

Git

```bash
$ git clone -b 0.8.0-dev git@github.com:hfcorriez/pagon.git myapp
```

### 代码

```php
$app = App::create();

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

### 预览

![运行欢迎页面](https://f.cloud.github.com/assets/119550/2168909/28e8f986-954e-11e3-8031-9c21079cbef6.jpg)

## 目标

- 简单高效的现代应用框架。使用类似Ruby的[Sinatra](http://www.sinatrarb.com)或Node的[Express.js](http://expressjs.com)
- 致力于打造一个拥有最小化核心组件且尽量不依赖粗糙三方库的简单、智能和高效的框架。
- 高效不只是开发效率，还有执行效率。
- 不只WEB，CLI下的开发也做了很多优化。
- 只需要一套框架，便能快速完成一套高效Web应用的开发！
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