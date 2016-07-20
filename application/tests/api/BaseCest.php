<?php

use tests\api\FixtureHelper;

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