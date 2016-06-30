<?php

require_once "BaseCest.php";

class AuthCest extends BaseCest
{

//    public function test1(ApiTester $I)
//    {
//        $I->wantTo('check response when making a GET request for logging in with no client_id');
//        $I->stopFollowingRedirects();
//        $I->sendGET('/auth/login');
//        $I->seeResponseCodeIs(400);
//    }

    public function test2(ApiTester $I)
    {
        $I->wantTo('check response when making a GET request for logging in with client_id but no access_token');
        $I->stopFollowingRedirects();
        $I->sendGET('/auth/login?client_id=asdf');
        $I->seeResponseCodeIs(302);
    }

//    public function test3(ApiTester $I)
//    {
//        $I->wantTo('check response when making a POST request for logging in with client_id');
//        $I->stopFollowingRedirects();
//        $I->haveHttpHeader('Authorization', 'Bearer user1');
//        $I->sendPOST('/auth/login?client_id=asdf');
//        $I->seeResponseCodeIs(405);
//    }

    public function test33(ApiTester $I)
    {
        $I->wantTo('check response when making a PUT request for logging in with client_id');
        $I->stopFollowingRedirects();
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/auth/login?client_id=asdf');
        $I->seeResponseCodeIs(405);
    }

    public function test34(ApiTester $I)
    {
        $I->wantTo('check response when making a DELETE request for logging in with client_id');
        $I->stopFollowingRedirects();
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendDELETE('/auth/login?client_id=asdf');
        $I->seeResponseCodeIs(405);
    }

    public function test35(ApiTester $I)
    {
        $I->wantTo('check response when making a OPTIONS request for logging in with client_id');
        $I->stopFollowingRedirects();
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendOPTIONS('/auth/login?client_id=asdf');
        $I->seeResponseCodeIs(405);
    }

    public function test4(ApiTester $I)
    {
        $I->wantTo('check response for making a GET request for logging out when already logged in');
        $I->stopFollowingRedirects();
        $I->haveHttpHeader('Authorization', 'Bearer user2');
        $I->haveHttpHeader('X-Codeception-CodeCoverage', '');
        $I->haveHttpHeader('HTTP_X_CODECEPTION_CODECOVERAGE', '');
        $I->sendGET('/user/me');
        $I->seeResponseCodeIs(200);
        $I->sendGET('/auth/logout?access_token=user2');
        $I->seeResponseCodeIs(302);
        $I->haveHttpHeader('Authorization', 'Bearer user2');
        $I->sendGET('/user/me');
        $I->seeResponseCodeIs(401);
    }

    public function test5(ApiTester $I)
    {
        $I->wantTo('check response for making a GET request for logging out when already logged out');
        $I->stopFollowingRedirects();
        $I->haveHttpHeader('Authorization', 'Bearer user4');
        $I->sendGET('/user/me');
        $I->seeResponseCodeIs(401);
        $I->sendGET('/auth/logout?access_token=user4');
        $I->seeResponseCodeIs(302);
        $I->haveHttpHeader('Authorization', 'Bearer user4');
        $I->sendGET('/user/me');
        $I->seeResponseCodeIs(401);
    }

    public function test6(ApiTester $I)
    {
        $I->wantTo('check response for making a POST request for logging out when already logged in');
        $I->stopFollowingRedirects();
        $I->haveHttpHeader('Authorization', 'Bearer user2');
        $I->sendPOST('/auth/logout?access_token=user2');
        $I->seeResponseCodeIs(405);
    }

    public function test7(ApiTester $I)
    {
        $I->wantTo('check response for making a PUT request for logging out when already logged in');
        $I->stopFollowingRedirects();
        $I->haveHttpHeader('Authorization', 'Bearer user2');
        $I->sendPUT('/auth/logout?access_token=user2');
        $I->seeResponseCodeIs(405);
    }

    public function test8(ApiTester $I)
    {
        $I->wantTo('check response for making a OPTIONS request for logging out when already logged in');
        $I->stopFollowingRedirects();
        $I->haveHttpHeader('Authorization', 'Bearer user2');
        $I->sendOPTIONS('/auth/logout?access_token=user2');
        $I->seeResponseCodeIs(200);
    }
}
