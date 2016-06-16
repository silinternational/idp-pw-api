<?php


class ConfigCest extends BaseCest
{

    public function test1(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated GET request to config');
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

    public function test12(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated GET request to config');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
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
        $I->wantTo('check response when making unauthenticated POST request to config');
        $I->sendPOST('/config');
        $I->seeResponseCodeIs(401);
    }

    public function test22(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated POST request to config');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPOST('/config');
        $I->seeResponseCodeIs(405);
    }

    public function test3(ApiTester $I)
    {
        $I->wantTo('check response when making unathenticated PUT request to config');
        $I->sendPUT('/config');
        $I->seeResponseCodeIs(401);
    }

    public function test32(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated PUT request to config');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/config');
        $I->seeResponseCodeIs(405);
    }

    public function test4(ApiTester $I)
    {
        $I->wantTo('check response when making unathenticated DELETE request to config');
        $I->sendDELETE('/config');
        $I->seeResponseCodeIs(401);
    }

    public function test42(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated DELETE request to config');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendDELETE('/config');
        $I->seeResponseCodeIs(405);
    }

    public function test5(ApiTester $I)
    {
        $I->wantTo('check response when making unathenticated PATCH request to config');
        $I->sendPATCH('/config');
        $I->seeResponseCodeIs(401);
    }

    public function test52(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated PATCH request to config');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPATCH('/config');
        $I->seeResponseCodeIs(405);
    }

    public function test6(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated OPTIONS request to config');
        $I->sendOPTIONS('/config');
        $I->seeResponseCodeIs(200);
    }

    public function test7(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated OPTIONS request to config');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendOPTIONS('/config');
        $I->seeResponseCodeIs(200);
    }
}
