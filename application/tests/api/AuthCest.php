<?php


class AuthCest
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
        $I->wantTo('check response when logging in with no client_id');
        $I->stopFollowingRedirects();
        $I->sendGET('/auth/login');
        $I->seeResponseCodeIs(401);
    }

    public function test2(ApiTester $I)
    {
        $I->wantTo('check response when logging in with client_id');
        $I->stopFollowingRedirects();
        $I->sendGET('/auth/login?client_id=asdf');
        $I->seeResponseCodeIs(302);
    }

    public function test3(ApiTester $I)
    {
        $I->wantTo('check response when logging in with client_id and access-token');
        $I->stopFollowingRedirects();
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendGET('/auth/login?client_id=asdf');
        $I->seeResponseCodeIs(302);
    }

    public function test4(ApiTester $I)
    {
        $I->wantTo('check response logging out when logged in');
        $I->stopFollowingRedirects();
        $I->haveHttpHeader('Authorization', 'Bearer user2');
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
        $I->wantTo('check response logging out when logged out');
        $I->haveHttpHeader('Authorization', 'Bearer user22');
        $I->sendGET('/user/me');
        $I->seeResponseCodeIs(401);
        $I->sendGET('/auth/logout?access_token=user22');
        $I->seeResponseCodeIs(401);
        $I->haveHttpHeader('Authorization', 'Bearer user2');
        $I->sendGET('/user/me');
        $I->seeResponseCodeIs(401);
    }
}
