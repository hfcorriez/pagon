<?php

namespace OmniApp\Controller;

class Web extends \OmniApp\Controller
{

    /**
     * @var \OmniApp\Http\Request
     */
    protected $request;

    /**
     * @var \OmniApp\Http\Response
     */
    protected $response;

    /**
     * before run
     */
    function before()
    {
    }

    /**
     * after run
     */
    function after()
    {
    }

    /**
     * Run
     *
     * @param \OmniApp\Http\Request $req
     * @param \OmniApp\Http\Response $res
     * @return mixed
     */
    function run($req, $res)
    {
        $this->request = $req;
        $this->response = $res;
        $method = $req->method();

        if (method_exists($this, strtolower($method))) {
            $this->{$method}();
        } elseif (method_exists($this, 'all')) {
            $this->{'all'}();
        }
    }
}