<?php

namespace OmniApp;

class Module
{
    const _CLASS_ = __CLASS__;

    public function load(App $app)
    {
        throw new \Exception("Can not run core module directly");
    }
}
