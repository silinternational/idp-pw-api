<?php

namespace common\components\auth;

use Exception;

/**
 * Class RedirectException - Used to trigger a redirect to perform login/logout
 * when IdP uses this method, such as with SAML
 * @package common\components\auth
 */
class RedirectException extends Exception
{
    /**
     * @var string
     */
    public $url;

    /**
     * RedirectException constructor.
     * @param string $url The URL to target with a redirect.
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param Exception|null $previous [optional] The previous throwable used for the exception chaining.
     */
    public function __construct(string $url, $message = '', $code = 0, Exception $previous = null)
    {
        $this->url = $url;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
