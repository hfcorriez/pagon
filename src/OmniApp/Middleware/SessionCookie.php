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

        $req = $this->request->env();
        if ($_sessions = $this->request->cookie($option['name'])) {
            $_sessions = $req['sessions'] = (array)$_sessions;
        }

        $this->next();

        if ($req['sessions'] != $_sessions) {
            $this->response->cookie($option['name'], $req['sessions'], array('sign' => true));
            $req['sessions'] = array();
        }
    }
}