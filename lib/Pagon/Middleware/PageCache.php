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
        'timeout' => 0
    );

    public function call()
    {
        // Check cache provider and input method
        if (!$this->options['cache'] || !$this->input->is("get")) {
            $this->next();
            return;
        }

        if ($this->options['domain']) {
            // If generate key with domain
            $key = $this->input->url();
        } elseif ($this->options['key']) {
            // Support create key dynamic
            if ($this->options['key'] instanceof \Closure) {
                $key = call_user_func($this->options['key'], $this->input, $this->output);
            } else {
                $key = $this->options['key'];
            }
        } else {
            // Create key only with uri
            $key = $this->input->uri();
        }

        if ($this->options['hash']) {
            // Hash the key
            $key = sha1($this->options['hash']);
        }

        // Add prefix for the key
        $key = $this->options['prefix'] . $key;

        /** @var $cache \Pagon\Cache */
        $cache = $this->options['cache'];

        if ($page = $cache->get($key)) {
            // Try to get the page cacheF
            $page = json_decode($page, true);
            $this->output->header($page['header']);
            $this->output->end($page['body']);
            return;
        }

        // Next
        $this->next();

        $page = array();
        $page['header'] = $this->output->header();
        $page['body'] = $this->output->body();

        // Save data to cache
        $cache->set($key, json_encode($page), $this->options['timeout']);
    }
}