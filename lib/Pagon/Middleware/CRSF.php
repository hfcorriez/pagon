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
        // Inject the error type
        $this->app->errors['unsafe'] = array(403, 'Security Check Fail!');

        // Check if post?
        if ($this->input->is("post")) {
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
                // Parse url
                $url = parse_url($this->input->refer());
                // Check host and domain
                if ($this->input->domain() != $url['host']) {
                    $this->callback();
                    return;
                }
            }
        }

        // Add crsf_token to request
        $token = $this->input->crsf_token = $this->encodeToken();
        // Add crsf_token to locals variables
        $this->output->locals['crsf_token'] = $token;

        // Next
        $this->next();

        if (
            // If enable form injection
            $this->options['form']
            // And the page is html content type
            && strpos($this->output->contentType(), 'html')
        ) {
            // Check if buffer start
            if ($this->app->enabled('buffer')) {
                $this->output->write(ob_get_clean());
            }

            // Check form if exists?
            if (strpos($this->output->body, '</form>')) {
                $this->output->body = str_replace('</form>', '<input type="hidden" name="' . $this->options['name'] . '" value="' . 'd' . '" /></form>', $this->output->body);
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
            $this->app->handleError('unsafe');
        }
    }
}
