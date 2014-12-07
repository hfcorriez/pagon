<?php

namespace Api;

use Pagon\Route\Rest;
use Pagon\View;

class Api extends Rest
{
    /**
     * The API data
     *
     * @var array
     */
    protected $data = array();

    /**
     * The API format
     *
     * @var string
     */
    protected $_format = 'json';

    /**
     * Xml option for dump
     *
     * @var array
     */
    protected $_xml_option = array();

    /**
     * Json callback function
     *
     * @var string
     */
    protected $_jsonp_callback = 'callback';

    /**
     * Show error error message
     *
     * @param string $message
     * @param int    $error_code
     * @param int    $code
     */
    protected function error($message, $error_code = 400, $code = 400)
    {
        $this->dump(array('message' => $message, 'code' => $error_code), $code);
    }

    /**
     * Show ok message
     */
    protected function ok($message)
    {
        $this->dump(array('message' => $message, 'code' => '0'));
    }

    /**
     * Dump API data
     *
     * @param array $data
     * @param int   $code
     */
    protected function dump(array $data, $code = 200)
    {
        $this->output->status($code);

        switch ($this->_format) {
            case 'xml':
                $this->output->xml($data, $this->_xml_option);
                break;
            case 'jsonp':
                $this->output->jsonp($data, $this->_jsonp_callback);
                break;
            default:
                $this->output->json($data);

        }
        $this->output->end();
    }

    /**
     * Before
     */
    protected function before()
    {
        $this->loadOrm();
    }

    /**
     * After
     */
    protected function after()
    {
        $this->dump($this->data);
    }

    /**
     * Load ORM and database
     */
    protected function loadOrm()
    {
        $this->app->loadOrm();
    }
}