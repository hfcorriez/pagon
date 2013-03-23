<?php

namespace Pagon\Utility;

class Cryptor
{
    protected $options = array(
        'cipher' => MCRYPT_RIJNDAEL_256,
        'mode'   => MCRYPT_MODE_CBC,
        'block'  => 32,
        'rand'   => null,
        'key'    => null
    );

    protected $_iv_size;

    /**
     * @param array|string $options
     * @throws \InvalidArgumentException
     *
     *  new Cryptor('key')
     *  new Cryptor(array('key' => 'test', 'cipher' => MCRYPT_RIJNDAEL_192))
     */
    public function __construct($options = array())
    {
        if ($options) {
            if (is_array($options)) {
                $this->options = $options + $this->options;
            } else {
                $this->options['key'] = (string)$options;
            }
        }

        if (!$this->options['key']) throw new \InvalidArgumentException('Cryptor need key param');

        // Find the max length of the key, based on cipher and mode
        $size = mcrypt_get_key_size($this->options['cipher'], $this->options['mode']);

        if (isset($this->options['key'][$size])) {
            // Shorten the key to the maximum size
            $this->options['key'] = substr($this->options['key'], 0, $size);
        }

        // Store the IV size
        $this->_iv_size = mcrypt_get_iv_size($this->options['cipher'], $this->options['mode']);

        // Set the rand type if it has not already been set
        if ($this->options['rand'] === null) {
            if (defined('MCRYPT_DEV_URANDOM')) {
                // Use /dev/urandom
                $this->options['rand'] = MCRYPT_DEV_URANDOM;
            } elseif (defined('MCRYPT_DEV_RANDOM')) {
                // Use /dev/random
                $this->options['rand'] = MCRYPT_DEV_RANDOM;
            } else {
                // Use the system random number generator
                $this->options['rand'] = MCRYPT_RAND;
            }
        }
    }

    /**
     * Encrypts a string and returns an encrypted string that can be decoded.
     *
     * The encrypted binary data is encoded using [base64](http://php.net/base64_encode)
     * to convert it to a string. This string can be stored in a database,
     * displayed, and passed using most other means without corruption.
     *
     * @param   string  $data   data to be encrypted
     * @return  string
     */
    public function encrypt($data)
    {
        if ($this->options['rand'] === MCRYPT_RAND) {
            // The system random number generator must always be seeded each
            // time it is used, or it will not produce true random results
            mt_srand();
        }

        // Create a random initialization vector of the proper size for the current cipher
        $iv = mcrypt_create_iv($this->_iv_size, $this->options['rand']);

        // Encrypt the data using the configured options and generated iv
        $data = mcrypt_encrypt($this->options['cipher'], $this->options['key'], $data, $this->options['mode'], $iv);

        // Use base64 encoding to convert to a string
        return base64_encode($iv . $data);
    }

    /**
     * Decrypts an encoded string back to its original value.
     *
     * @param   string  $data   encoded string to be decrypted
     * @return  bool|string
     */
    public function decrypt($data)
    {
        // Convert the data back to binary
        $data = base64_decode($data, TRUE);

        if (!$data) {
            // Invalid base64 data
            return FALSE;
        }

        // Extract the initialization vector from the data
        $iv = substr($data, 0, $this->_iv_size);

        if ($this->_iv_size !== strlen($iv)) {
            // The iv is not the expected size
            return FALSE;
        }

        // Remove the iv from the data
        $data = substr($data, $this->_iv_size);

        // Return the decrypted data, trimming the \0 padding bytes from the end of the data
        return rtrim(mcrypt_decrypt($this->options['cipher'], $this->options['key'], $data, $this->options['mode'], $iv), "\0");
    }
}
