<?php

namespace Pagon\Cache;

class File
{
    protected $options = array(
        'path'      => '/tmp',
        'hierarchy' => false,
    );

    /**
     * Init cache
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options + $this->options;
    }

    /**
     * Get value
     *
     * @param string $key
     * @return mixed
     * @return bool|null
     */
    public function get($key)
    {
        if (!$this->exists($key)) {
            return null;
        }
        $cache = include_once($this->path($key));
        if ($cache['expires'] && $cache['expires'] < time()) {
            return null;
        }
        return $cache['data'];
    }

    /**
     * Set value
     *
     * @param string $key
     * @param string $value
     * @param int    $timeout
     * @throws \RuntimeException
     * @return bool
     */
    public function set($key, $value, $timeout = 0)
    {
        $path = $this->path($key);
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            throw new \RuntimeException('File cache dir "' . $dir . '" has no permissions');
        }

        $cache = array(
            'expires' => $timeout ? time() + $timeout : 0,
            'data'    => $value,
        );

        file_put_contents($path, "<?php\nreturn " . var_export($cache, true) . ";");
    }

    /**
     * Delete key
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        return $this->exists($key) && unlink($this->path($key));
    }

    /**
     * Exists the key?
     *
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return is_file($this->path($key));
    }

    /**
     * Get path
     *
     * @param string $key
     * @return string
     */
    protected function path($key)
    {
        $path = sha1($key);
        if ($this->options['hierarchy']) {
            $path = substr($path, 0, 2) . '/' . substr($path, 2);
        }

        return $this->options['path'] . '/' . $path . '.php';
    }
}
