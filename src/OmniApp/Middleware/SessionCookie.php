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
            $_SESSION = $req['sessions'] = (array)$_sessions;
        }

        $this->next();

        $res = $this->response->env();
        if ($res['sessions']) {
            $this->response->cookie($option['name'], $res['sessions'], array('sign' => true));
            $res['sessions'] = array();
        }
    }
}