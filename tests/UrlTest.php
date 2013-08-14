<?php

namespace Pagon;

use Pagon\Http\Input;

class UrlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var App
     */
    protected $app;

    public function setUp()
    {
        $this->app = App::create(array(
            'site_url'  => 'http://apple.com/test',
            'asset_url' => 'http://cdn.apple.com'
        ));
        $_SERVER = array(
            'HTTP_HOST'            => 'localhost',
            'HTTP_CONNECTION'      => 'keep-alive',
            'HTTP_CACHE_CONTROL'   => 'max-age=0',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_USER_AGENT'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.95 Safari/537.36',
            'HTTP_ACCEPT_ENCODING' => 'gzip,deflate,sdch',
            'HTTP_ACCEPT_LANGUAGE' => 'zh-CN,zh;q=0.8',
            'PATH'                 => '/usr/bin:/bin:/usr/sbin:/sbin',
            'SERVER_SIGNATURE'     => '',
            'SERVER_SOFTWARE'      => 'Apache/2.2.24 (Unix) DAV/2 PHP/5.3.25 mod_ssl/2.2.24 OpenSSL/0.9.8y',
            'SERVER_NAME'          => 'localhost',
            'SERVER_ADDR'          => '::1',
            'SERVER_PORT'          => '80',
            'REMOTE_ADDR'          => '::1',
            'DOCUMENT_ROOT'        => '/Users/hfcorriez/Code',
            'SERVER_ADMIN'         => 'hfcorriez@gmail.com',
            'SCRIPT_FILENAME'      => '/Users/hfcorriez/Code/index.php',
            'REMOTE_PORT'          => '52872',
            'GATEWAY_INTERFACE'    => 'CGI/1.1',
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_METHOD'       => 'GET',
            'QUERY_STRING'         => '',
            'REQUEST_URI'          => '/',
            'SCRIPT_NAME'          => '/index.php',
            'PHP_SELF'             => '/index.php',
            'REQUEST_TIME'         => 1375528769,
        );
        $this->app->input = new Input(array('app' => $this->app));
        $this->app->run();
    }

    public function testSite()
    {
        $url = Url::site();

        $this->assertEquals('http://apple.com/test', $url);
    }

    public function testBase()
    {
        $base = Url::base();

        $this->assertEquals('', $base);
    }

    public function testToWithUrl()
    {
        $url = Url::to('http://inews.io', array('from' => 'local'));

        $this->assertEquals('http://inews.io?from=local', $url);
    }

    public function testTo()
    {
        $url = Url::to('abc', array('from' => 'local'));

        $this->assertEquals('/abc?from=local', $url);
    }

    public function testToFull()
    {
        $url = Url::to('abc', array('from' => 'local'), true);

        $this->assertEquals('http://apple.com/test/abc?from=local', $url);
    }

    public function testRouteUrl()
    {
        $this->app->get('/app', function () {
        })->name('app');

        $url = Url::route('app');

        $this->assertEquals('/app', $url);
    }

    public function testRouteFullUrl()
    {
        $this->app->get('/app', function () {
        })->name('app');

        $url = Url::route('app', null, null, true);

        $this->assertEquals('http://apple.com/test/app', $url);
    }

    public function testRouteWithParams()
    {
        $this->app->get('/user/:id', function () {
        })->name('user');

        $url = Url::route('user', array('id' => '1'));

        $this->assertEquals('/user/1', $url);
    }

    public function testRouteWithParamsIndex()
    {
        $this->app->get('/user/*', function () {
        })->name('user');

        $url = Url::route('user', array('1'));

        $this->assertEquals('/user/1', $url);
    }

    public function testRouteFullWithParams()
    {
        $this->app->get('/user/:id', function () {
        })->name('user');

        $url = Url::route('user', array('id' => '1'), null, true);

        $this->assertEquals('http://apple.com/test/user/1', $url);
    }

    public function testAssetUrl()
    {
        $url = Url::asset('/jquery.min.js');

        $this->assertEquals('http://cdn.apple.com/jquery.min.js', $url);
    }

    public function testCurrent()
    {
        $url = Url::current();

        $this->assertEquals('/', $url);
    }

    public function testCurrentWithQuery()
    {
        $url = Url::current(array('id' => 1));

        $this->assertEquals('/?id=1', $url);
    }

    public function testCurrentFull()
    {
        $url = Url::current(null, true);

        $this->assertEquals('http://apple.com/test/', $url);
    }
}
