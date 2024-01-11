<?php

namespace tests\mock\emailer;

use Sil\EmailService\Client\EmailServiceClient;

class FakeEmailServiceClient extends EmailServiceClient
{
    public $emailsSent = [];

    public function email(array $config = [])
    {
        $this->emailsSent[] = $config;
        return $config;
    }

    /**
     * Ping the /site/status URL, and throw an exception if there's a problem.
     *
     * @return string "OK".
     */
    public function getSiteStatus()
    {
        return 'OK';
    }
}
