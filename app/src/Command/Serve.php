<?php

namespace Command;

use Pagon\Console;
use Pagon\Route;

class Serve extends Route
{
    public function run($req, $res)
    {
        $devApp = function ($request, $response) {
            /**
             * Static file check and render
             */
            $static_file = ROOT_DIR . '/public' . $request->getPath();
            if (file_exists($static_file)) {
                echo Console::text(
                    "<green>200</green>"
                    . ' <cyan>' . str_pad($request->getMethod(), 6, ' ', STR_PAD_RIGHT) . '</cyan>'
                    . ' ' . $request->getPath(), true);

                $response->writeHead(200, array('Content-Type' => mime_content_type($static_file)));
                $response->end(file_get_contents($static_file));
                return;
            }

            /**
             * Mock Application and init
             */
            $app = include(APP_DIR . '/bootstrap.php');

            $app->input = $devReq = new \Pagon\Http\Input(array('app' => $app));
            $app->output = $devRes = new \Pagon\Http\Output(array('app' => $app));
            $app->buffer = true;
            $app->cli = false;

            $request->on('data', function ($data) use ($headers, $devReq, $app) {
                $_POST = parse_raw_http_request($data, $headers['Content-Type']);

                include(ROOT_DIR . '/public/index.php');
            });

            /**
             * Web environment initial
             */
            $headers = $request->getHeaders();
            $_GET = $query = $request->getQuery();

            foreach ($headers as $k => $v) {
                $devReq->server['HTTP_' . strtoupper(str_replace('-', '_', $k))] = $v;
            }

            $devReq->server['REQUEST_URI'] = $request->getPath() . ($query ? '?' . http_build_query($query) : '');
            $devReq->server['REQUEST_METHOD'] = $request->getMethod();
            $devReq->server['REMOTE_ADDR'] = '127.0.0.1';
            $devReq->server['SERVER_NAME'] = '127.0.0.1';
            $devReq->server['SERVER_PORT'] = 5000;
            $devReq->server['SCRIPT_NAME'] = '/';

            /**
             * Pagon header inject
             */
            $devRes->on('header', function () use ($response, $request, $devRes, $devReq) {
                echo Console::text(
                    ($devRes->status < 400 ? "<green>$devRes->status</green>" : "<red>$devRes->status</red>")
                    . ' <cyan>' . str_pad($request->getMethod(), 6, ' ', STR_PAD_RIGHT) . '</cyan>'
                    . ' ' . $devReq->url, true);

                $response->writeHead($devRes->status, $devRes->headers);
                $response->end($devRes->body);

                $devRes->body('');
            });
        };

        $loop = \React\EventLoop\Factory::create();
        $socket = new \React\Socket\Server($loop);
        $http = new \React\Http\Server($socket, $loop);

        $http->on('request', $devApp);

        echo "Pagon serve at http://127.0.0.1:5000\n";

        $socket->listen(5000);
        $loop->run();
    }
}


function parse_raw_http_request($input, $content_type)
{
    $a_data = array();

    // grab multipart boundary from content type header
    preg_match('/boundary=(.*)$/', $content_type, $matches);

    // content type is probably regular form-encoded
    if (!count($matches)) {
        // we expect regular puts to containt a query string containing data
        parse_str(urldecode($input), $a_data);
        return $a_data;
    }

    $boundary = $matches[1];

    // split content by boundary and get rid of last -- element
    $a_blocks = preg_split("/-+$boundary/", $input);
    array_pop($a_blocks);

    // loop data blocks
    foreach ($a_blocks as $id => $block) {
        if (empty($block))
            continue;

        // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char

        // parse uploaded files
        if (strpos($block, 'application/octet-stream') !== FALSE) {
            // match "name", then everything after "stream" (optional) except for prepending newlines
            preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
            $a_data['files'][$matches[1]] = $matches[2];
        } // parse all other fields
        else {
            // match "name" and optional value in between newline sequences
            preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
            $a_data[$matches[1]] = $matches[2];
        }
    }

    return $a_data;
}