# 路由

## 标准路由

```php
$app->get('/auth/:type', function ($req, $res) {
    // 使用键名获取
    $type = $req->params['type'];

    // 使用索引获取
    $type = $req->params[0];

    // 做点什么
});
```

## 模糊路由

```php
$app->get('/see/*', function() {
    // 使用索引值获取键值
    $what = $req->params[0];

    // 做点什么
});
```

## 正则路由

```php
$app->get('^\/([\d]{4})\/([\d]{2})/([\d]{2})$', function ($req, $res) {
    list($year, $month, $day) = $req->params;

    // 做点什么
});
```

## 命名路由

```php
$app->get('/user/:id', function ($req, $res) {
    $my_url = Url::route('user', array('id' => $req->params['id']));

    // 做点什么
})->name('user');
```

## 路由参数默认值

```php
$app->get('/abc', function () {
    // 做点什么
})->defaults(array('name' => 'abc'));
```

## 路由参数规则

```php
$app->get('/archive/:year', function() {
    // 做点什么
})->rules(array('year' => '[\d]{4}'));
```

## 可选参数

> 只适用标准路由模式，配合默认值使用会更方便

```php
$app->get('/auth(/:type)', function ($req, $res) {
    // 使用键名获取键值
    $type = $req->params['type'];
})
  ->defaults(array('type' => 'weibo'));
```

# 配置

## 配置说明

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
url_rewrite 是否开启Url rewrite
```

## 配置应用

```php
$app = App::create(array(
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

## 通过文件加载配置

> 支持`json`, `yaml`, `ini`, `xml`和`php`

例子：

`config.php`

```php
<?php

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

`config.json`

```json
{
    "timezone": "Asia/Shanghai",
    "debug": true,
    "cookie": {
        "secret": "very secret",
        "domain": "app.com",
        "path": "/"
    }
}
```

`ini`和`yaml`支持在此不再敖述！后续文档会详细的介绍。

## 注入配置

```
$app = App::create('config.json');
```

# 中间件

> 中间件是打通`输入`和`输出`的中间链路，可以随意发挥来实现一个中间件

## 自己创建中间件

```php
$app->add(function($req, $res, $next) {
	if (!$req->session('user_id')) {
		$res->status(403)->end('Plz login to visit');
	}
	return $next();
})
```

## 框架内置的中间件

```
- Booster           助推器，根据配置文件为App做一些绑定工作，比如logger和cryptor
- CSRF              CSRF自动防御中间件
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

例子：

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

# 环境

环境配置可以通过环境变量`PAGON_ENV`来设置，默认为`develop`

```php
$app = App::create();

// 获取当前环境
$app->mode();

// 自定义环境模式
$app->mode(function() {
    return getenv("PHP_ENV");
});
```

# 控制器

## 匿名函数方式

```php
$app->get('/api/ping', function($req, $res) {
    $res->end('pong');
});
```

## 继承方式

> 可以通过内置的基础控制器来派生出自己的控制器

### Restful控制器

```php
use Pagon\Rest

class Ping extend Rest {

    // GET /ping
    public function get($req, $res) {
        $res->end('pong');
    }
}


$app->get('/ping', 'Ping');
```

### Classic控制器

```php
use Pagon\Classic

class Service extend Classic {

    // GET/POST/... /service/echo
    public function actionEcho($req, $res) {
        $res->end('echo');
    }
}

$app->all('/service/:action', 'Service');
```

# 事件

## 事件列表

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

> 所有对象都基于事件实现，可以轻松在任何对象上绑定事件

例子：

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

# 依赖容器(Dependency Injector Container)

> 所有对象都基于依赖容器，都可以实现依赖注入。

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