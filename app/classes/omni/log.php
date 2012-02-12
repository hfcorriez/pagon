<?php

namespace Omni;

const LOG_DEBUG = 0;
const LOG_NOTICE = 1;
const LOG_WARN = 2;
const LOG_ERROR = 3;
const LOG_CRITICAL = 4;

class Log extends Module
{
    public static $messages = array();
    public static $filename = ':date/:level.log';
    public static $message = '[:datetime] :text #:id';

    private static $_config;

    private static $_levels = array(
        'debug' => LOG_DEBUG,
        'notice' => LOG_NOTICE,
        'warn' => LOG_WARN,
        'error' => LOG_ERROR,
        'critical' => LOG_CRITICAL
    );

    public static function init($config)
    {
        self::$_config = $config;
        Event::on(EVENT_SHUTDOWN, function()
        {
            Log::save();
        });

        Event::on(EVENT_EXCEPTION, function(\Exception $e)
        {
            Log::write($e, LOG_ERROR);
        });
    }

    public static function __callstatic($name, $argments)
    {
        if (isset(self::$_levels[$name])) self::write($argments[0], self::$_levels[$name], $name);
    }

    public static function write($text, $level = LOG_NOTICE, $tag = false)
    {
        if ($level < self::$_config['level']) return false;

        $microtime = microtime(true);
        $message = array(
            'id' => isset($_SERVER['HTTP_TRACKING_ID']) ? $_SERVER['HTTP_TRACKING_ID'] : '',
            'time' => $microtime,
            'text' => $text,
            'level' => $tag ? $tag : array_search($level, self::$_levels),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'memory' => memory_get_usage(),
            'datetime' => date('Y-m-d H:i:s', $microtime) . substr($microtime - floor($microtime), 1, 4),
            'date' => date('Y-m-d', floor($microtime)),
        );
        self::$messages[] = $message;
    }

    public static function save()
    {
        if (empty(self::$messages)) return false;

        $log_filename = empty(self::$_config['filename']) ? self::$filename : self::$_config['filename'];
        $dirname_exists = array();

        foreach (self::$messages as $message)
        {
            $replace = array();
            foreach ($message as $k => $v) $replace[':' . $k] = $v;

            $message_text = strtr(self::$message, $replace);
            $filename = self::$_config['dir'] . '/' . strtr($log_filename, $replace);
            $dirname = dirname($filename);
            if (!in_array($dirname, $dirname_exists) && !is_dir($dirname)) {
                mkdir($dirname, 0777, true);
                $dirname_exists[] = $dirname;
            }

            file_put_contents($filename, $message_text . PHP_EOL, FILE_APPEND);
        }
    }
}