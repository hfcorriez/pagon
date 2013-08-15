<?php

namespace Pagon\Middleware;

use Pagon\Middleware;

class CSRF extends Middleware
{
    // Some options
    protected $injectors = array(
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
            if ($this->injectors['form']) {
                $token = $this->input->data($this->injectors['name']);
                if (!$token
                    || !($value = $this->decodeToken($token))
                    || $value[0] != $this->injectors['sign']
                    || ($value[1] + $this->injectors['expires'] < time())
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
            if ($this->injectors['refer']) {
                // Parse url
                $url = parse_url($this->input->refer());
                // Check host and domain
                if ($this->input->domain() != $url['host']) {
                    $this->callback();
                    return;
                }
            }
        }

        // Add csrf_token to request
        $token = $this->input->csrf_token = $this->encodeToken();
        // Add csrf_token to locals variables
        $this->output->locals['csrf_token'] = $token;

        // Next
        $this->next();

        if (
            // If enable form injection
            $this->injectors['form']
            // And the page is html content type
            && strpos($this->output->type(), 'html')
        ) {
            // Check if buffer start
            if ($this->app->enabled('buffer')) {
                $this->output->write(ob_get_clean());
            }

            // Check form if exists?
            if (strpos($this->output->body, '</form>')) {
                $this->output->body = str_replace('</form>', '<input type="hidden" name="' . $this->injectors['name'] . '" value="' . $token . '" /></form>', $this->output->body);
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
        return $this->app->cryptor->encrypt($this->injectors['sign'] . '||' . time());
    }

    /**
     * Callback the route or response the html
     */
    protected function callback()
    {
        if (isset($this->injectors['callback'])) {
            $this->injectors['callback']($this->input, $this->output);
        } else {
            $this->app->handleError('unsafe');
        }
    }
}
