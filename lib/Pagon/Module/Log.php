<?php

namespace Pagon\Module;

use Pagon\Event;
use Pagon\App;
use Pagon\Http\Input;

/**
 * Log
 * @method static debug
 * @method static info
 * @method static warn
 * @method static error
 * @method static emerg
 */
class Log
{
    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARN = 2;
    const LEVEL_ERROR = 3;
    const LEVEL_EMERG = 4;

    protected static $config;
    protected static $messages = array();
    protected static $filename = ':level.log';
    private static $levels = array(
        'debug' => self::LEVEL_DEBUG,
        'info'  => self::LEVEL_INFO,
        'warn'  => self::LEVEL_WARN,
        'error' => self::LEVEL_ERROR,
        'emerg' => self::LEVEL_EMERG
    );

    /**
     * Init log config
     *
     * @static
     * @internal param $config
     */
    public static function init(array $config = array())
    {
        if ($config) self::$config = &$config;
        elseif (!empty(App::$config['log'])) self::$config = &App::$config['log'];

        if (!isset(self::$config['dir'])) self::$config['dir'] = '.';
        if (!isset(self::$config['level'])) self::$config['level'] = self::LEVEL_DEBUG;

        Event::on(EVENT::SHUTDOWN, function () {
            Log::save();
        });
    }

    /**
     * call level name as method
     *
     * @static
     * @param $name
     * @param $arguments
     */
    public static function __callstatic($name, $arguments)
    {
        if (isset(self::$levels[$name])) self::write($arguments[0], self::$levels[$name], $name);
    }

    /**
     * Record log
     *
     * @static
     * @param      $text
     * @param int  $level
     * @param null $tag
     * @return bool
     */
    public static function write($text, $level = self::LEVEL_INFO, $tag = null)
    {
        if ($level < self::$config['level']) return false;

        $micro_time = microtime(true);
        $message = array(
            'id'       => Input::trackId(),
            'time'     => $micro_time,
            'text'     => $text,
            'ip'       => getenv('REMOTE_ADDR'),
            'level'    => $tag ? $tag : array_search($level, self::$levels),
            'memory'   => memory_get_usage(),
            'datetime' => date('Y-m-d H:i:s', $micro_time) . substr($micro_time - floor($micro_time), 1, 4),
            'date'     => date('Y-m-d', floor($micro_time)),
        );
        self::$messages[] = $message;
        return true;
    }

    /**
     * Save log message
     *
     * @static
     * @return bool
     */
    public static function save()
    {
        if (empty(self::$messages)) return;

        $log_filename = empty(self::$config['filename']) ? self::$filename : self::$config['filename'];
        $dir_exists = array();

        foreach (self::$messages as $message) {
            $replace = array();
            foreach ($message as $k => $v) $replace[':' . $k] = $v;

            $header = '[' . $message['datetime'] . '] [' . str_pad($message['level'], 7, ' ', STR_PAD_BOTH) . '] ';
            $text = $header . str_replace("\n", "\n{$header}", trim($message['text'], "\n"));

            $filename = self::$config['dir'] . '/' . strtr($log_filename, $replace);
            $dir = dirname($filename);
            if (!in_array($dir, $dir_exists)) {
                if (!is_dir($dir)) {
                    if (mkdir($dir, 0777, true)) $dir_exists[] = $dir;
                } else $dir_exists[] = $dir;
            }

            if (in_array($dir, $dir_exists)) file_put_contents($filename, $text . "\n", FILE_APPEND);
        }
    }
}