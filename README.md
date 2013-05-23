## Pagon框架? 


[![Build Status](https://travis-ci.org/hfcorriez/pagon.png)](https://travis-ci.org/hfcorriez/pagon)

```
Test cases		: 42
Code coverage	: 38%
```

- 简单高效的PHP框架，使用方式类似Ruby的[Sinatra](http://www.sinatrarb.com)或Node的[Express.js](http://expressjs.com)
- 致力于打造一个拥有最小化核心组件且尽量不依赖第三方库的简单、智能和高效的框架。
- 高效不止是开发效率，还包括执行效率。
- 不止支持Web开发，也支持CLI下的开发并且做了很多优化。
- 现阶段是实现`只需要一套框架，便能快速完成一套高效Web应用的开发`！

## 特性

- 简单，几乎无需配置就能使用
- 智能，有很多经过考虑的模式设计
- 扩展，使用中间件方式随意扩展自己想要的
- 标准，基于PSR标准开发
- 性能，效率上优于目前所有主流框架
- 事件，基于事件打造，随时随地事件驱动
- 前卫，对良好的新技术或思想提供快速支持，比如Restful

## 安装

已有项目

```
composer.phar require pagon/app=0.5.2
```

新项目（使用[`pagon/app`](https://github.com/pagon/app)skelton创建应用）

```
composer.phar create-project pagon/app myapp
```

## 例子

### Hello world

```php
$app = new App();

// 使用匿名函数实现控制器
$app->get('/', function($req, $res){
   $res->end('Hello world');
});

// 路由映射到类控制器上
$app->post('/submit', '\Route\Submit');
```

### 配置

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

### 中间件

中间件是打通`输入`和`输出`的中间链路，可以随意发挥来实现一个中间件

```php
$app->add(function($req, $res, $next) {
	if (!$req->session('user_id')) {
		$res->status(403)->end('Plz login to visit');
	}
	return $next();
})

// 使用框架内置的中间件

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

环境配置可以通过环境变量`PAGON_ENV`来设置，默认为`development`

```php
$app = new App();

// 配置development
$app->configure('development', function(){
    $app->set('debug', true);
});

// 配置所有环境
$app->configure(function($mode) use ($app){
	switch ($mode) {
		case 'development':
			$app->set('debug', true);
			break;
		case 'production':
			$app->add('PageCache', array('cache' => new Cache\Redis($app->get('redis'))));
			break;
	}
})
```

### 控制器

可以使用Closure的方式实现控制器，也可以使用类继承的方式来创造一个控制器

```php
class Api extend \Pagon\Rest {
    public function get($req, $res) {
        $res->json(array(
            'error' => 0,
            'message' => 'OK'
        ));
    }
}

$app->get('/api', 'Api');
```

### 事件

所有对象都基于事件实现，可以轻松在任何对象上绑定事件

```php
$app->on('run', function(){
	// Start profiler
	xhprof_enabled();
});

$app->on('shutdown', function(){
	// Get profiler data
	$profiler_data = xhprof_disabled();
	// Save it
});
```

### 依赖容器(DI)

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