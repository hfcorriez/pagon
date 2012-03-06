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

/**
 * Omni Exception
 */
class Exception extends \Exception
{
    public static $php_errors = array(
        E_ERROR             => 'Fatal Error',
        E_USER_ERROR        => 'User Error',
        E_PARSE             => 'Parse Error',
        E_WARNING           => 'Warning',
        E_USER_WARNING      => 'User Warning',
        E_STRICT            => 'Strict',
        E_NOTICE            => 'Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
    );

    public function __construct($message, array $variables = NULL, $code = 0)
    {
        if (defined('E_DEPRECATED'))
        {
            // E_DEPRECATED only exists in PHP >= 5.3.0
            self::$php_errors[E_DEPRECATED] = 'Deprecated';
        }
    }

    /**
     * Exception output as string
     *
     * @static
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s [ %s ]: %s ~ %s [ %d ]',
                       get_class($this), $this->getCode(), strip_tags($this->getMessage()), $this->getFile(),
                       $this->getLine());
    }
}