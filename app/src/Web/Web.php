<?php

namespace Web;

use Pagon\Route\Rest;
use Pagon\Url;
use Pagon\View;

class Web extends Rest
{
    /**
     * Current template layout for page
     *
     * @var string
     */
    protected $_tpl_layout = 'layout.php';

    /**
     * Current template content for page
     *
     * @var string
     */
    protected $_tpl = '';

    /**
     * The page title
     *
     * @var string
     */
    protected $title = 'Pagon';

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
        if (!$this->app->output->body && $this->_tpl) {
            $this->render($this->_tpl);
        }
    }

    /**
     * Render template
     *
     * @param $tpl
     */
    protected function render($tpl)
    {
        $body = new View(
            $tpl,
            get_object_vars($this) + $this->app->locals,
            array('dir' => $this->app->views)
        );

        $this->app->render($this->_tpl_layout, array('body' => $body->render()) + get_object_vars($this));
    }

    /**
     * Load ORM and database
     */
    protected function loadOrm()
    {
        $this->app->loadOrm();
    }

    /**
     * Redirect the page
     *
     * @param $uri
     */
    protected function redirect($uri)
    {
        $this->output->redirect(Url::to($uri))->end();
    }
}