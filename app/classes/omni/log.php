<?php
/**
 * OmniApp Framework
 *
 * @package   OmniApp
 * @author    Corrie Zhao <hfcorriez@gmail.com>
 * @copyright (c) 2011 - 2012 OmniApp Framework
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 *
 */

namespace Omni;

const LOG_DEBUG = 0;
const LOG_NOTICE = 1;
const LOG_WARN = 2;
const LOG_ERROR = 3;
const LOG_CRITICAL = 4;

/**
 * Log
 */
class Log extends Module
{
    /**
     * @var array log config
     */
    protected static $_config;

    // log messages
    protected static $_messages = array();

    // log filename format
    protected static $_filename = ':date/:level.log';

    // level map
    private static $_levels = array(
        'debug'    => LOG_DEBUG,
        'notice'   => LOG_NOTICE,
        'warn'     => LOG_WARN,
        'error'    => LOG_ERROR,
        'critical' => LOG_CRITICAL
    );

    /**
     * Init log config
     *
     * @static
     *
     * @param $config
     */
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

    /**
     * call level name as method
     *
     * @static
     *
     * @param $name
     * @param $argments
     */
    public static function __callstatic($name, $argments)
    {
        if (isset(self::$_levels[$name])) self::write($argments[0], self::$_levels[$name], $name);
    }

    /**
     * Record log
     *
     * @static
     *
     * @param      $text
     * @param int  $level
     * @param bool $tag
     *
     * @return bool
     */
    public static function write($text, $level = LOG_NOTICE, $tag = false)
    {
        if ($level < self::$_config['level']) return false;

        $microtime = microtime(true);
        $message = array(
            'id'       => isset($_SERVER['HTTP_TRACKING_ID']) ? $_SERVER['HTTP_TRACKING_ID'] : '',
            'time'     => $microtime,
            'text'     => $text,
            'level'    => $tag ? $tag : array_search($level, self::$_levels),
            'ip'       => $_SERVER['REMOTE_ADDR'],
            'memory'   => memory_get_usage(),
            'datetime' => date('Y-m-d H:i:s', $microtime) . substr($microtime - floor($microtime), 1, 4),
            'date'     => date('Y-m-d', floor($microtime)),
        );
        self::$_messages[] = $message;
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
        if (empty(self::$_messages)) return false;

        $log_filename = empty(self::$_config['filename']) ? self::$_filename : self::$_config['filename'];
        $dirname_exists = array();

        foreach (self::$_messages as $message)
        {
            $replace = array();
            foreach ($message as $k => $v) $replace[':' . $k] = $v;

            $header = '[' . $message['datetime'] . '] [' . str_pad($message['level'], 8, ' ', STR_PAD_BOTH) . '] ';
            $text = $header . str_replace("\n", "\n{$header}", trim($message['text'], "\n"));

            $filename = self::$_config['dir'] . '/' . strtr($log_filename, $replace);
            $dirname = dirname($filename);
            if (!in_array($dirname, $dirname_exists) && !is_dir($dirname))
            {
                mkdir($dirname, 0777, true);
                $dirname_exists[] = $dirname;
            }

            file_put_contents($filename, $text . "\n", FILE_APPEND);
        }
    }
}