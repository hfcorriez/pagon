<?php

namespace Command;

use Pagon\Config\Ini;
use Pagon\Console;
use Pagon\Route\Command;

class App extends Command
{
    protected $arguments = array(
        'action' => array(
            'help' => 'Action to do',
            'enum' => array('config', 'env', 'version')
        ),
    );

    public function run($req, $res)
    {
        $app = include(APP_DIR . '/bootstrap.php');

        switch ($this->params['action']) {
            case "config":
                $config = array();
                foreach ($app->raw() as $key => $value) {
                    if ($key === 'locals') break;

                    $config[$key] = $value;
                }

                echo Ini::dump($config);
                break;
            case "env":
                echo Console::text($app->mode(), 'green', true);
                break;
            case "version":
                echo Console::text(\Pagon\VERSION, 'green', true);
                break;
            case "cleanup":
                if (Console::confirm("确定清除 '.git' 目录?")) {
                    echo "Cleaned";
                }
                break;
            default:
                echo Console::text("未知的操作: {$this->params['action']}", 'redbg', true);
                break;
        }
    }
}