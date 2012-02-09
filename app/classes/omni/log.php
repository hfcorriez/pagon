<?php

namespace Omni;

const LOG_DEBUG = 0;
const LOG_NOTICE = 1;
const LOG_WARN = 2;
const LOG_ERROR = 3;
const LOG_CRITICAL = 4;

class Log extends Module
{
    public static $logs;
    public static $path_format = '$date/$tag.log';
    public static $log_format = '[$datetime] #$id $text';

    private static $_levels = array(
        'debug'     => LOG_DEBUG,
        'notice'    => LOG_NOTICE,
        'warn'      => LOG_WARN,
        'error'     => LOG_ERROR,
        'critical'  => LOG_CRITICAL
    );

    public static function init()
    {
        Event::on(EVENT_SHUTDOWN, function(){ Log::save(); });
        Event::on(EVENT_EXCEPTION, function($e){ Log::error($e); });
    }

    public static function __callstatic($name, $argments)
    {
        if (isset(self::$_levels[$name])) self::write($argments[0], self::$_levels[$name], $name);
    }

    public static function write($text, $level = LOG_NOTICE, $tag = false)
    {
        if (!App::$config->log) return trigger_error('Config->log not set.', E_USER_NOTICE);
        if ($level < App::$config->log['level']) return;

        $log = array (
            'id' => isset($_SERVER['HTTP_TRACK_ID']) ? $_SERVER['HTTP_TRACK_ID'] : '',
            'time' => microtime(true),
            'text' => $text,
            'level' => $level,
            'tag' => $tag ? $tag : array_search($level, self::$_levels),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'memory' => memory_get_usage(),
        );
        self::$logs[] = $log;
    }

    public static function save()
    {
        if (empty(self::$logs)) return false;
        if (!App::$config->log['dir']) return trigger_error('Config->log["dir"] not set.', E_USER_NOTICE);

        $index = strrpos(self::$path_format, '/');
        $dir_format = $index !== false ? substr(self::$path_format, 0, $index) : '';
        $file_format = $index !== false ? substr(self::$path_format, $index+1) : self::$path_format;

        $dir = App::$config->log['dir'] . '/' . strtr($dir_format, array('$date'=>date('Ymd')));
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $logs_wraper = array();
        foreach (self::$logs as $log)
        {
            $replace = array();
            $log['datetime'] = (date('Y-m-d H:i:s', $log['time'])) . substr($log['time'] - floor($log['time']), 1, 4);
            foreach ($log as $k=>$v) $replace['$' . $k] = $v;
            $logs_wraper[$log['tag']][] = strtr(self::$log_format, $replace);
        }
        foreach ($logs_wraper as $tag => $wraper) file_put_contents($dir . '/' . strtr($file_format, array('$tag'=>$tag)), join(PHP_EOL, $wraper) . PHP_EOL, FILE_APPEND);
    }
}