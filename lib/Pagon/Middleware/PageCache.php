<?php

namespace Pagon\Middleware;

use Pagon\Middleware;

class PageCache extends Middleware
{
    protected $options = array(
        'domain'  => false,
        'prefix'  => 'page::',
        'key'     => false,
        'cache'   => false,
        'hash'    => true,
        'encode'  => 'json',
        'timeout' => 0
    );

    public function call()
    {
        if (!$this->options['cache']) {
            $this->next();
            return;
        }

        if ($this->options['domain']) {
            $key = $this->input->url();
        } elseif ($this->options['key']) {
            if ($this->options['key'] instanceof \Closure) {
                $key = call_user_func($this->options['key']);
            } else {
                $key = $this->options['key'];
            }
        } else {
            $key = $this->input->uri();
        }

        if ($this->options['hash']) {
            $key = sha1($this->options['hash']);
        }

        $key = $this->options['prefix'] . $key;

        /** @var $cache \Pagon\Cache */
        $cache = $this->options['cache'];

        if ($page = $cache->get($key)) {
            switch ($this->options['encode']) {
                case 'json':
                    $page = json_decode($page, true);
                    break;
                default:
                    $page = unserialize($page);
            }
            $this->output->header($page['header']);
            $this->output->end($page['body']);
            return;
        }

        $this->next();

        $page = array();
        $page['header'] = $this->output->header();
        $page['body'] = $this->output->body();

        switch ($this->options['encode']) {
            case 'json' :
                $page = json_encode($page);
                break;
            default:
                $page = serialize($page);
        }

        $cache->set($key, $page, $this->options['timeout']);
    }
}