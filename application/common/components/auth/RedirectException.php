<?php
namespace common\components\auth;

/**
 * Class RedirectException - Used to trigger a redirect to perform login/logout
 * when IdP uses this method, such as with SAML
 * @package common\components\auth
 */
class RedirectException extends \Exception
{
    /**
     * @var string
     */
    public $url;

    /**
     * RedirectException constructor.
     * @param string $url
     * @param string|null $message
     * @param int|null $code
     * @param null|\Exception $previous
     */
    public function __construct($url, $message = null, $code = null, \Exception $previous = null)
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