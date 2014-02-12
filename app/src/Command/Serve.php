<?php

namespace Command;

use Pagon\Console;
use Pagon\Route;

class Serve extends Route
{
    public function run($req, $res)
    {
        session_start();

        $server_app = function ($request, $response) {
            /**
             * Static file check and render
             */
            $static_file = ROOT_DIR . '/public' . $request->getPath();
            if (is_file($static_file)) {
                echo Console::text(
                    "<green>200</green>"
                    . ' <cyan>' . str_pad($request->getMethod(), 6, ' ', STR_PAD_RIGHT) . '</cyan>'
                    . ' ' . $request->getPath(), true);

                $response->writeHead(200, array('Content-Type' => mime_content_type($static_file)));
                $response->end(file_get_contents($static_file));
                return;
            }

            // Initial
            $_COOKIE = $_FILES = $_SESSION = $_GET = $_POST = array();

            /**
             * Mock Application and init
             */
            $app = include(APP_DIR . '/bootstrap.php');
            $raw = '';

            $app->input = $mock_req = new \Pagon\Http\Input(array('app' => $app));
            $app->output = $mock_res = new \Pagon\Http\Output(array('app' => $app));
            $app->buffer = true;
            $app->cli = false;

            $headers = $request->getHeaders();
            $_GET = $request->getQuery();
            if ($headers['Cookie']) {
                $_COOKIE = decode_cookie($headers['Cookie']);
            }

            $request->on('data', function ($data) use (&$raw, $request, $headers, $app) {
                $raw .= $data;

                if (strlen($raw) < $headers['Content-Length']) return;

                // Start parse
                $parsed = parse_raw_http_request($raw, $headers['Content-Type']);

                // Inject data and files
                $app->input->data = $_POST = $parsed['data'];
                $app->input->files = $_FILES = $parsed['files'];

                include(ROOT_DIR . '/public/index.php');
            });

            /**
             * Web environment initial
             */

            foreach ($headers as $k => $v) {
                $mock_req->server['HTTP_' . strtoupper(str_replace('-', '_', $k))] = $v;
            }

            $mock_req->server['REQUEST_URI'] = $request->getPath() . ($_GET ? '?' . http_build_query($_GET) : '');
            $mock_req->server['REQUEST_METHOD'] = $request->getMethod();
            $mock_req->server['REMOTE_ADDR'] = '127.0.0.1';
            $mock_req->server['SERVER_NAME'] = '127.0.0.1';
            $mock_req->server['SERVER_PORT'] = 5000;
            $mock_req->server['SCRIPT_NAME'] = '/';

            /**
             * Pagon header inject
             */
            $mock_res->on('header', function () use ($response, $request, $mock_res, $mock_req) {
                echo Console::text(
                    ($mock_res->status < 400 ? "<green>$mock_res->status</green>" : "<red>$mock_res->status</red>")
                    . ' <cyan>' . str_pad($request->getMethod(), 6, ' ', STR_PAD_RIGHT) . '</cyan>'
                    . ' ' . $mock_req->url, true);

                $headers = $mock_res->headers;

                if ($cookies = $mock_res->buildCookie()) {
                    $headers['Set-Cookie'] = encode_cookie($cookies);
                }

                try {
                    $response->writeHead($mock_res->status, $headers);
                    $response->end($mock_res->body);
                } catch (\Exception $e) {
                }

                $mock_res->body('');
            });
        };

        $loop = \React\EventLoop\Factory::create();
        $socket = new \React\Socket\Server($loop);
        $http = new \React\Http\Server($socket, $loop);

        $http->on('request', $server_app);

        echo "Pagon serve at http://127.0.0.1:5000\n";

        $socket->listen(5000);
        $loop->run();
    }
}


function parse_raw_http_request($input, $content_type)
{
    $data = array('data' => array(), 'files' => array());
    $post = & $data['data'];
    $file = & $data['files'];

    // grab multipart boundary from content type header
    preg_match('/boundary=(.*)$/', $content_type, $matches);

    // content type is probably regular form-encoded
    if (!count($matches)) {
        // we expect regular puts to containt a query string containing data
        parse_str(urldecode($input), $post);
        return $data;
    }

    $boundary = $matches[1];

    // split content by boundary and get rid of last -- element
    $blocks = preg_split("/-+$boundary/", $input);
    array_pop($blocks);

    // loop data blocks
    foreach ($blocks as $id => $block) {
        if (empty($block))
            continue;

        // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char

        // parse uploaded files
        if (strpos($block, 'filename=') !== FALSE) {
            // match "name", then everything after "stream" (optional) except for prepending newlines
            preg_match("/name=\"([^\"]*)\"; filename=\"([^\"]*)\"\r\nContent-Type: (.*?)\r\n\r\n([^\n\r].*)?$/s", $block, $matches);

            $tmp_file = tempnam(sys_get_temp_dir(), 'php');
            $write_length = file_put_contents($tmp_file, $matches[4]);

            $file[$matches[1]] = array(
                'name'     => $matches[2],
                'type'     => $matches[3],
                'tmp_name' => $write_length ? $tmp_file : null,
                'error'    => $write_length ? UPLOAD_ERR_OK : UPLOAD_ERR_CANT_WRITE,
                'size'     => $write_length
            );
        } // parse all other fields
        else {
            // match "name" and optional value in between newline sequences
            preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
            $post[$matches[1]] = $matches[2];
        }
    }

    return $data;
}

function encode_cookie(array $cookies)
{
    $lines = array();

    foreach ($cookies as $cookie) {
        $paris = array($cookie['key'] . "=" . rawurlencode($cookie['value']));
        $_option = $cookie['option'];

        if ($_option['maxage']) $paris[] = 'Max-Age=' . $_option['expires'];
        if ($_option['path']) $paris[] = 'Path=' . $_option['path'];
        if ($_option['httponly']) $paris[] = 'HttpOnly';
        if ($_option['secure']) $paris[] = 'Secure';

        $lines[] = join('; ', $paris);
    }

    return join("\r\n", $lines);
}

function decode_cookie($string)
{
    parse_str(str_replace('; ', '&', urldecode($string)), $arr);
    return $arr;
}