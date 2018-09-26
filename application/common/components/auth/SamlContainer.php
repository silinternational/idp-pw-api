<?php
namespace common\components\auth;

use SAML2\Compat\AbstractContainer;

class SamlContainer extends AbstractContainer
{
    public function getLogger()
    {
        return new \Monolog\Logger('log');
    }

    public function generateId()
    {
        return microtime();
    }

    public function debugMessage($message, $type)
    {

    }

    public function redirect($url, $data = [])
    {
        foreach ($data as $key => $value) {
            if (substr_count($url, '?') > 0) {
                $url .= '&';
            } else {
                $url .= '?';
            }

            $url .= $key . '=' . urlencode($value);
        }

        throw new RedirectException($url);
    }

    public function postRedirect($url, $data = [])
    {
        $this->redirect($url, $data);
    }
}
