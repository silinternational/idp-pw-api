<?php


class ConfigCest
{
    public function _before(ApiTester $I)
    {
    }

    public function _after(ApiTester $I)
    {
    }

    // tests

    public function test1(ApiTester $I)
    {
        $I->wantTo('check response when making get request');
        $I->sendGET('/config');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['idpName' => 'SIL']);
        $I->seeResponseContainsJson([
            'support' => [
                'email' => 'info@insitehome.org',
            ]
        ]);
    }

    public function test2(ApiTester $I)
    {
        $I->wantTo('check response when making post request');
        $I->sendPOST('/config');
        $I->seeResponseCodeIs(404);
    }

    public function test3(ApiTester $I)
    {
        $I->wantTo('check response when making put request');
        $I->sendPUT('/config');
        $I->seeResponseCodeIs(404);
    }

    public function test4(ApiTester $I)
    {
        $I->wantTo('check response when making delete request');
        $I->sendDELETE('/config');
        $I->seeResponseCodeIs(404);
    }

    public function test5(ApiTester $I)
    {
        $I->wantTo('check response when making patch request');
        $I->sendPATCH('/config');
        $I->seeResponseCodeIs(404);
    }

    public function test6(ApiTester $I)
    {
        $I->wantTo('check response when making options request');
        $I->sendOPTIONS('/config');
        $I->seeResponseCodeIs(200);
    }
}
