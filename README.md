## What's OmniApp?

OmniApp is expressjs-like framework of PHP

- Simple
	
	It's simple to use as [Express.js](http://expressjs.com/)
	
- Smart

	Support much more function with better design pattern

- Flexible

	The middleware pattern can be extend as you like

- Standard 
	
	According to [PSR](https://github.com/php-fig/fig-standards) - PSR-0, PSR-1, PSR-2

- Performence 

	It's faster than better design frameworks, such as slim, lavarel, kohana, and a  little slower than colaphp and micromvc


## Usage

### Hello world

```
$app = new App();
$app->get('/', function($req, $res){
   $res->end('Hello world');
});
```

### Config

```
$config = include('default_config.php');
$app = new App($config);

$app->config('cookie.secret', 'abc');
```

### Configure mode

```
$app = new App();
$app->mode('development', function(){
    $app->config('debug', true);
});
```

### Api

	wait for release...

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