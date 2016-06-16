<?php


class UserCest
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
        $I->wantTo('check response when passing in correct token');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendGET('/user/me');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'first_name' => "User",
            'last_name' => "One",
            'idp_username' => 'first_last',
            'email' => 'first_last@organization.org',
        ]);
    }

    public function test2(ApiTester $I)
    {
        $I->wantTo('check response when passing in incorrect token');
        $I->haveHttpHeader('Authorization', 'Bearer user11');
        $I->sendGET('/user/me');
        $I->seeResponseCodeIs(401);
    }

    public function test3(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated post request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPOST('/user/me');
        $I->seeResponseCodeIs(404);
    }

    public function test4(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated post request');
        $I->haveHttpHeader('Authorization', 'Bearer user11');
        $I->sendPOST('/user/me');
        $I->seeResponseCodeIs(404);
    }

    public function test5(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated delete request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendDELETE('/user/me');
        $I->seeResponseCodeIs(404);
    }

    public function test6(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated delete request');
        $I->haveHttpHeader('Authorization', 'Bearer user11');
        $I->sendDELETE('/user/me');
        $I->seeResponseCodeIs(404);
    }

    public function test7(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated patch request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPATCH('/user/me');
        $I->seeResponseCodeIs(404);
    }

    public function test8(ApiTester $I)
    {
        $I->wantTo('check response when making unathenticated patch request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPATCH('/user/me');
        $I->seeResponseCodeIs(404);
    }

    public function test9(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated options request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendOPTIONS('/user/me');
        $I->seeResponseCodeIs(200);
    }

    public function test10(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated options request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendOPTIONS('/user/me');
        $I->seeResponseCodeIs(200);
    }
}
