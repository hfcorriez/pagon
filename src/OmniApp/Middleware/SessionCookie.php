<?php

namespace OmniApp\Middleware;

use OmniApp\Middleware;

class SessionCookie extends Middleware
{
    public function call($option = array())
    {
        $option = $option + array(
            'name' => 'sessions',
        );

        $req = $this->input->env();
        if ($_sessions = $this->input->cookie($option['name'])) {
            $_sessions = $req['sessions'] = (array)$_sessions;
        }

        $this->next();

        if ($req['sessions'] != $_sessions) {
            $this->output->cookie($option['name'], $req['sessions'], array('sign' => true));
            $req['sessions'] = array();
        }
    }
}