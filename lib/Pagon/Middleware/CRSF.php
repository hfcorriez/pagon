<?php

namespace Pagon\Middleware;

use Pagon\Middleware;

class CRSF extends Middleware
{
    // Some options
    protected $options = array(
        'form'    => true,
        'refer'   => true,
        'name'    => '__ct',
        'sign'    => 'fu*kCrSF',
        'expires' => 3600
    );

    /**
     * Call
     */
    public function call()
    {
        if ($this->input->isPost()) {
            if ($this->options['form']) {
                $token = $this->input->data($this->options['name']);
                if (!$token
                    || !($value = $this->decodeToken($token))
                    || $value[0] != $this->options['sign']
                    || ($value[1] + $this->options['expires'] < time())
                ) {
                    $this->callback();
                    return;
                }
            }

            /**
             * Refer check
             *
             * if refer is not domain or sub-domain of current site
             *
             * current: http://xxx.com
             *
             * refer "http://xxx.com/submit" will pass
             * refer "http://a.xxx.com/submit" will fail
             * refer "http://yyy.com/submit" will fail
             *
             */
            if ($this->options['refer']) {
                $url = parse_url($this->input->refer());
                if ($this->input->domain() != $url['host']) {
                    $this->callback();
                    return;
                }
            }
        }

        $this->next();

        if ($this->options['form'] && strpos($this->output->contentType(), 'html')) {
            // Check if buffer start
            if ($this->app->enabled('buffer')) {
                $this->output->write(ob_get_clean());
            }

            if (strpos($this->output->body, '</form>')) {
                $this->output->body = preg_replace('/.*?<\/form>/', '<input type="hidden" name="' . $this->options['name'] . '" value="' . $this->encodeToken() . '" /></form>', $this->output->body);
            }
        }
    }

    /**
     * Decode token
     *
     * @param $token
     * @return array|bool
     */
    protected function decodeToken($token)
    {
        if ($value = $this->app->cryptor->decrypt($token)) {
            return explode('||', $value);
        }
        return false;
    }

    /**
     * Encode token
     *
     * @return string
     */
    protected function encodeToken()
    {
        return $this->app->cryptor->encrypt($this->options['sign'] . '||' . time());
    }

    /**
     * Callback the route or response the html
     */
    protected function callback()
    {
        if (isset($this->options['callback'])) {
            $this->options['callback']($this->input, $this->output);
        } else {
            $this->output->status = 403;
            $this->output->end('<html><head><title></title></head><body><h2>Security Check Fail!</h2></body></html>');
        }
    }
}
