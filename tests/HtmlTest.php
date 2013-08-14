<?php

namespace Pagon;

use Pagon\Http\Input;

class HtmlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var App
     */
    protected $app;

    public function setUp()
    {
        $this->app = App::create();
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
            'REQUEST_URI'          => '/index.php',
            'SCRIPT_NAME'          => '/index.php',
            'PHP_SELF'             => '/index.php',
            'REQUEST_TIME'         => 1375528769,
        );
        $this->app->input = new Input(array('app' => $this->app));
        $this->app->run();
    }

    public function testDom()
    {
        $html = Html::dom('example', "Lorem Ipsum", array('id' => 'app'));

        $this->assertEquals('<example id="app">Lorem Ipsum</example>', $html);

        $html = Html::dom('br');

        $this->assertEquals('<br />', $html);
    }

    public function testA()
    {
        $a = Html::a('http://pagon.github.com', 'Pagon');

        $this->assertEquals('<a href="http://pagon.github.com">Pagon</a>', $a);
    }

    public function testScript()
    {
        $script = Html::script('http://pagon.github.com');

        $this->assertEquals('<script src="http://pagon.github.com" type="text/javascript"></script>', $script);
    }

    public function testLink()
    {
        $link = Html::style('http://pagon.github.com/app.css');

        $this->assertEquals('<link rel="stylesheet" href="http://pagon.github.com/app.css" />', $link);
    }

    public function testImage()
    {
        $image = Html::image('http://pagon.github.com/app.png');

        $this->assertEquals('<img src="http://pagon.github.com/app.png" />', $image);
    }

    public function testSelect()
    {
        $select = Html::select('type', array('1' => 'Apple', '2' => 'Google'), '1', array('class' => 'select'));

        $this->assertEquals('<select name="type" class="select"><option value="1" selected="true">Apple</option><option value="2">Google</option></select>', $select);
    }

    public function testEncode()
    {
        $html = Html::encode('<a>中文</a>');

        $this->assertEquals('&lt;a&gt;中文&lt;/a&gt;', $html);
    }

    public function testDecode()
    {
        $origin = Html::decode('&lt;a&gt;中文&lt;/a&gt;');

        $this->assertEquals('<a>中文</a>', $origin);
    }

    public function testSpecialEncode()
    {
        $html = Html::specialChars('<a>中文</a>');

        $this->assertEquals('&lt;a&gt;中文&lt;/a&gt;', $html);
    }
}
