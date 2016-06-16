<?php


class PasswordCest
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
        $I->wantTo('check response when making get request with no token');
        $I->sendGET('/password');
        $I->seeResponseCodeIs(401);
    }

    public function test2(ApiTester $I)
    {
        $I->wantTo('check response when making get request with incorrect token');
        $I->haveHttpHeader('Authorization', 'Bearer user11');
        $I->sendGET('/password');
        $I->seeResponseCodeIs(401);
    }

    public function test3(ApiTester $I)
    {
        $I->wantTo('check response when making get request with correct token');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendGET('/password');
        $I->seeResponseCodeIs(200);
        $I->seeResponseMatchesJsonType([
            'last_changed' => 'string:date',
            'expires' => 'string:date'
        ]);
    }

    public function test4(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated post request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPOST('/password');
        $I->seeResponseCodeIs(404);
    }

    public function test5(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated post request');
        $I->haveHttpHeader('Authorization', 'Bearer user11');
        $I->sendPOST('/password');
        $I->seeResponseCodeIs(404);
    }

    public function test6(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated put request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/password',['password' => 'newPassword33']);
        $I->seeResponseCodeIs(200);
    }

    public function test7(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated delete request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendDELETE('/password');
        $I->seeResponseCodeIs(404);
    }

    public function test8(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated delete request');
        $I->haveHttpHeader('Authorization', 'Bearer user11');
        $I->sendDELETE('/password');
        $I->seeResponseCodeIs(404);
    }

    public function test9(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated patch request');
        $I->haveHttpHeader('Authorization', 'Bearer user11');
        $I->sendPATCH('/password');
        $I->seeResponseCodeIs(404);
    }

    public function test10(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated patch request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPATCH('/password');
        $I->seeResponseCodeIs(404);
    }

    public function test11(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated options request');
        $I->haveHttpHeader('Authorization', 'Bearer user11');
        $I->sendOPTIONS('/password');
        $I->seeResponseCodeIs(200);
    }

    public function test12(ApiTester $I)
    {
        $I->wantTo('check response when password does not meet minLength requirement');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/password',['password' => 'newPasswo']);
        $I->seeResponseCodeIs(400);
    }

    public function test13(ApiTester $I)
    {
        $I->wantTo('check response when password does not meet minNumber requirement');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/password',['password' => 'asdfasdfasdfasdf1']);
        $I->seeResponseCodeIs(400);
    }

    public function test14(ApiTester $I)
    {
        $I->wantTo('check response when password does not meet minNumber requirement');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/password',['password' => 'asdfasdfasdfasdf1']);
        $I->seeResponseCodeIs(400);
    }

    public function test15(ApiTester $I)
    {
        $I->wantTo('check response when password has zxcvbn score of 1');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/password',['password' => 'asdfgh1234']);
        $I->seeResponseCodeIs(400);
    }

    public function test16(ApiTester $I)
    {
        $I->wantTo('check response when password has zxcvbn score of 2');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/password',['password' => 'helloworld10']);
        $I->seeResponseCodeIs(200);
    }

    public function test17(ApiTester $I)
    {
        $I->wantTo('check response when password has zxcvbn score of 3');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/password',['password' => 'helloworld1010fi']);
        $I->seeResponseCodeIs(200);
    }

    public function test18(ApiTester $I)
    {
        $I->wantTo('check response when password does not meet maxLength requirement');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/password',['password' => 'Lorem ipsum dolor sit amet, nonummy ligula volutpat hac integer nonummy. Suspendisse ultricies, congue etiam tellus, erat libero, nulla eleifend, mauris pellentesque. Suspendisse integer praesent vel, integer gravida mauris, fringilla vehicula lacinia non123. Suspendisse integer praesent vel, integer gravida mauris, fringilla vehi. Suspendisse integer praesent vel, integer gravida mauris, fringilla vehi']);
        $I->seeResponseCodeIs(400);
    }
}
