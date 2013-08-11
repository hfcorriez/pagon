## Pagon框架? 

[![Build Status](https://travis-ci.org/hfcorriez/pagon.png)](https://travis-ci.org/hfcorriez/pagon)
[![Build Status](https://drone.io/github.com/hfcorriez/pagon/status.png)](https://drone.io/github.com/hfcorriez/pagon/latest)

邮件列表: [https://groups.google.com/d/forum/pagonframework](https://groups.google.com/d/forum/pagonframework)

```
Test cases		: 83
Code coverage	: 41%
```

> ;) 单元测试还在努力ing...


- 简单高效的现代应用框架。使用类似Ruby的[Sinatra](http://www.sinatrarb.com)或Node的[Express.js](http://expressjs.com)
- 致力于打造一个拥有最小化核心组件且尽量不依赖粗糙三方库的简单、智能和高效的框架。
- 高效不只是开发效率，还有执行效率。
- 不只WEB，CLI下的开发也做了很多优化。
- 只需要一套框架，便能快速完成一套高效Web应用的开发！
- `Write less, Do more!`

## 特性

- 简单，无配置就能使用，小应用可快速成型
- 智能，经过考虑的模式设计，
- 扩展，使用中间件方式随意扩展自己想要的
- 标准，基于[PSR规范](https://github.com/hfcorriez/fig-standards)开发
- 性能，效率上优于目前所有主流框架
- 事件，所有类基于事件，随时随地用事件驱动
- 现代，支持主流的现代应用开发：Web/Cli, Restful, Xml/Yaml/Json/Ini, Jade/Twig?, Dependency Injector

## 安装

已有项目

```
composer.phar require pagon/pagon=0.6.3
```

新项目（使用[`pagon/app`](https://github.com/pagon/app)skelton创建应用）

```
composer.phar create-project pagon/app myapp
```

## 使用

### Hello world

```php
$app = new App();

// 使用匿名函数实现控制器
$app->get('/', function($req, $res){
   $res->end('Hello world');
});
```

### 路由

标准路由

```php
$app->get('/auth/:type', function ($req, $res) {
    // Use key to get param
    $type = $req->params['type'];

    // Use index to get param
    $type = $req->params[0];

    // Todo something...
});
```

模糊路由

```php
$app->get('/see/*', function() {
    // Use index to get param
    $what = $req->params[0];

    // Todo something...
});
```

正则路由

```php
$app->get('^\/([\d]{4})\/([\d]{2})/([\d]{2})$', function ($req, $res) {
    $year = $req->params[0];
    $month = $req->params[1];
    $day = $req->params[2];

    // Todo something...
});
```

命名路由

```php
$app->get('/user/:id', function ($req, $res) {
    $my_url = Url::route('user', array('id' => $req->params['id']));

    // Todo something...
})->name('user');
```

路由参数默认值

```php
$app->get('/abc', function () {
    // Todo something
})->defaults(array('name' => 'abc'));
```

路由参数规则

```php
$app->get('/archive/:year', function() {
    // Todo something
})->rules(array('year' => '[\d]{4}'));
```

可选参数（只适用标准路由模式）

`可选参数配合默认值使用会更方便`

```php
$app->get('/auth(/:type)', function ($req, $res) {
    // Use key to get param
    $type = $req->params['type'];
})->defaults(array('type' => 'weibo'));
```

### 配置

配置说明

```
mode        运行模式，一般无需配置，根据PAGON_ENV自动生成
debug       是否开启调试模式
views       模板目录
buffer      是否开启缓冲区
timezone    时区，默认UTC
charset     编码，默认UTF-8,
autoload    自动加载主目录
error       是否Handle PHP抛出的错误
routes      路由列表
names       路由命名，一般自动生成
alias       类的别名，简化很长的类名
namespaces  注册命名空间地址，根据命名空间查找
engines     模板引擎，根据扩展名渲染引擎
errors      默认错误信息
stacks      中间件
mounts      应用加载的路径列表
bundles     包应用
locals      默认的模板变量
safe_query  在模板渲染时自动预防XSS
```

直接传递配置创建应用

```php
$app = new App(array(
	'timezone' => 'Asia/Shanghai',
	'debug' =>  true,
	'cookie' => array(
		'secret' => 'very secret',
		'domain' => 'test.com',
		'path' => '/' 		
	)
));

// 设置配置
$app->set('cookie.domain', 'abc.com');

// 获取配置
$app->get('cookie.domain');
```

通过文件加载配置，支持`json`, `yaml`, `ini`和`php`(`xml`后续也会支持)，例子：

config.php

```php
return array(
    'timezone' => 'Asia/Shanghai',
    'debug' =>  true,
    'cookie' => array(
        'secret' => 'very secret',
        'domain' => 'app.com',
        'path' => '/'
    )
);
```

config.json

```json
{
    "timezone": "Asia/Shanghai",
    "debug": true,
    "cookie": {
        "secret": "very secret",
        "domain": 'app.com',
        "path": '/'
    }
}
```

`ini`和`yaml`支持在此不再敖述！后续文档会详细的介绍。

注入配置

```
$app = new App('config.json');
```

### 中间件

中间件是打通`输入`和`输出`的中间链路，可以随意发挥来实现一个中间件

```php
$app->add(function($req, $res, $next) {
	if (!$req->session('user_id')) {
		$res->status(403)->end('Plz login to visit');
	}
	return $next();
})
```

使用框架内置的中间件

```
- Booster           助推器，根据配置文件为App做一些绑定工作，比如logger和cryptor
- CRSF              CRSF自动防御中间件
- OPAuth            OPAuth的中间件，用来做第三方验证
- PrettyException   异常和错误输出，Debug模式下默认开启
- Flash             信息闪存，用于验证提示等
- HttpMethods       完整的Http方法支持
- HttpBasicAuth     Http Basic验证支持
- I18N              多语言支持
- PageCache         页面缓存支持
- Session           Session支持，包括（Cookie, Redis, Memcache）
- MethodOveride     Http方法重载
```

自带中间件的例子

```php
// 直接生成（适合一些必用的中间件）
$app->add(new \Pagon\Middleware\Session\Cookie(array('name' => 'sessions')));
$app->add(new \Pagon\Middleware\HttpMethodOverride());

// 或者使用下面这种方式，适合不一定用到或者按路径加载的中间件
$app->add('Session\Cookie', array('name' => 'sessions'))
$app->add('HttpMethodOverride')

// 按照路由加载中间件
$app->add('/monitor', 'HttpBasicAuth', array('username' => 'test', 'password' => 'test'));
```

### 环境

环境配置可以通过环境变量`PAGON_ENV`来设置，默认为`develop`

```php
$app = new App();

// 配置development
$app->configure('develop', function(){
    $app->set('debug', true);
});

// 配置所有环境
$app->configure(function($mode) use ($app){
	switch ($mode) {
		case 'develop':
			$app->set('debug', true);
			break;
		case 'product':
			$app->add('PageCache', array('cache' => Cache::dispense('redis')));
			break;
	}
})
```

### 控制器

可以使用Closure的方式实现控制器

```php
$app->get('/api/ping', function($req, $res) {
    $res->end('pong');
});
```

也可以使用类继承的方式来创造一个控制器

```php
use Pagon\Rest

class Ping extend Rest {
    public function get($req, $res) {
        $res->end('pong');
    }
}

$app->get('/api/ping', 'Ping');
```

### 事件

事件列表

```
- App
  - run        应用运行前
  - bundle     Bundle加载前
  - bundle.x   x Bundle加载前
  - middleware 中间件加载前
  - flush      输出前
  - end        输出后
  - exit       退出时（错误也会触发）
  - crash      程序异常无法运行
  - error      未捕获错误
```

所有对象都基于事件实现，可以轻松在任何对象上绑定事件

```php
$app->on('run', function(){
	// Start profiler
	xhprof_enabled();
});

$app->on('exit', function(){
	// Get profiler data
	$profiler_data = xhprof_disabled();
	// Save it
});
```

### 依赖容器(Dependency Injector Container)

所有对象都基于依赖容器，都可以实现依赖注入。

```php
// 共享
$app->share('db', function($app){
	extrat($app->get('database'));
	return new \PDO($dsn, $username, $password);
})

// 注入
$app->random = function() {
	return rand();
}
```

## API

完整文档将随`1.0`发布。

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