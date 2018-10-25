<?php

require_once "BaseCest.php";

class UserCest extends BaseCest
{

    public function test1(ApiTester $I)
    {
        $I->wantTo('check response when making GET request to /user/me with correct token');
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
        $I->wantTo('check response when making GET request to /user/me with incorrect token');
        $I->haveHttpHeader('Authorization', 'Bearer invalidToken');
        $I->sendGET('/user/me');
        $I->seeResponseCodeIs(401);
    }

    public function test3(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated POST request to /user/me');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPOST('/user/me');
        $I->seeResponseCodeIs(405);
    }

    public function test4(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated POST request to /user/me');
        $I->haveHttpHeader('Authorization', 'Bearer invalidToken');
        $I->sendPOST('/user/me');
        $I->seeResponseCodeIs(401);
    }

    public function test5(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated DELETE request to /user/me');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendDELETE('/user/me');
        $I->seeResponseCodeIs(405);
    }

    public function test6(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated DELETE request to /user/me');
        $I->haveHttpHeader('Authorization', 'Bearer invalidToken');
        $I->sendDELETE('/user/me');
        $I->seeResponseCodeIs(401);
    }

    public function test7(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated PATCH request to /user/me');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPATCH('/user/me');
        $I->seeResponseCodeIs(405);
    }

    public function test8(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated PATCH request to /user/me');
        $I->haveHttpHeader('Authorization', 'Bearer invalidToken');
        $I->sendPATCH('/user/me');
        $I->seeResponseCodeIs(401);
    }

    public function test9(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated OPTIONS request to /user/me');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendOPTIONS('/user/me');
        $I->seeResponseCodeIs(200);
    }

    public function test10(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated OPTIONS request to /user/me');
        $I->haveHttpHeader('Authorization', 'Bearer invalidToken');
        $I->sendOPTIONS('/user/me');
        $I->seeResponseCodeIs(200);
    }
}
