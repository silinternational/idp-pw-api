<?php

use tests\api\FixtureHelper;
use tests\helpers\BrokerUtils;

class BaseCest
{
    /** @var  tests\api\FixtureHelper */
    public $fixtureHelper;

    public function _inject(tests\api\FixtureHelper $fixtureHelper)
    {
        $this->fixtureHelper = $fixtureHelper;
    }

    public function _before(ApiTester $I)
    {
        $this->fixtureHelper->_beforeSuite();
    }

    public function _after(ApiTester $I)
    {
        $this->fixtureHelper->_afterSuite();
    }
}
