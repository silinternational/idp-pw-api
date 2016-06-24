<?php


class PasswordCest extends BaseCest
{

    public function test1(ApiTester $I)
    {
        $I->wantTo('check response when making GET request with no token for obtaining info about password');
        $I->sendGET('/password');
        $I->seeResponseCodeIs(401);
    }

    public function test2(ApiTester $I)
    {
        $I->wantTo('check response when making GET request with incorrect token for obtaining info about password');
        $I->haveHttpHeader('Authorization', 'Bearer user11');
        $I->sendGET('/password');
        $I->seeResponseCodeIs(401);
    }

    public function test3(ApiTester $I)
    {
        $I->wantTo('check response when making GET request with correct token for obtaining info about password');
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
        $I->wantTo('check response when making authenticated POST request to /password');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPOST('/password');
        $I->seeResponseCodeIs(405);
    }

    public function test5(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated POST request to /password');
        $I->haveHttpHeader('Authorization', 'Bearer user11');
        $I->sendPOST('/password');
        $I->seeResponseCodeIs(401);
    }
//
//    public function test6(ApiTester $I)
//    {
//        $I->wantTo('check response when making authenticated PUT request to update the password');
//        $I->haveHttpHeader('Authorization', 'Bearer user1');
//        $I->sendPUT('/password',['password' => 'newPassword33']);
//        $I->seeResponseCodeIs(200);
//    }

    public function test7(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated DELETE request to /password');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendDELETE('/password');
        $I->seeResponseCodeIs(405);
    }

    public function test8(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated DELETE request to /password');
        $I->haveHttpHeader('Authorization', 'Bearer user11');
        $I->sendDELETE('/password');
        $I->seeResponseCodeIs(401);
    }

    public function test9(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated PATCH request to /password');
        $I->haveHttpHeader('Authorization', 'Bearer user11');
        $I->sendPATCH('/password');
        $I->seeResponseCodeIs(401);
    }

    public function test10(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated PATCH request to /password');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPATCH('/password');
        $I->seeResponseCodeIs(405);
    }

    public function test11(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated OPTIONS request to /password');
        $I->haveHttpHeader('Authorization', 'Bearer user11');
        $I->sendOPTIONS('/password');
        $I->seeResponseCodeIs(200);
    }

    public function test12(ApiTester $I)
    {
        $I->wantTo('check response when changing the password (PUT request) to something that does not meet minLength requirement');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/password',['password' => 'A!dswo']);
        $I->seeResponseCodeIs(400);
        $body = json_decode($I->grabResponse(), true);
        if (substr_count($body['message'], 'code 100') <= 0) {
            throw new \Exception('Expected error code not present in message', 1466798390);
        }
    }

    public function test13(ApiTester $I)
    {
        $I->wantTo('check response when changing the password (PUT request) to something that does not meet minNumber requirement');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/password',['password' => 'A!sdfasdfasdfasdf1']);
        $I->seeResponseCodeIs(400);
        $body = json_decode($I->grabResponse(), true);
        if (substr_count($body['message'], 'code 120') <= 0) {
            throw new \Exception('Expected error code not present in message', 1466798391);
        }
    }

    public function test15(ApiTester $I)
    {
        $I->wantTo('check response when changing the password (PUT request) to something that has zxcvbn score of 1');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/password',['password' => 'Je$u$12345']);
        $I->seeResponseCodeIs(400);
        $body = json_decode($I->grabResponse(), true);
        if (substr_count($body['message'], 'code 150') <= 0) {
            throw new \Exception('Expected error code not present in message', 1466798392);
        }
    }

//    public function test16(ApiTester $I)
//    {
//        $I->wantTo('check response when changing the password (PUT request) to something that has zxcvbn score of 2');
//        $I->haveHttpHeader('Authorization', 'Bearer user1');
//        $I->sendPUT('/password',['password' => 'helloworld10']);
//        $I->seeResponseCodeIs(200);
//    }
//
//    public function test17(ApiTester $I)
//    {
//        $I->wantTo('check response when changing the password (PUT request) to something that has zxcvbn score of 3');
//        $I->haveHttpHeader('Authorization', 'Bearer user1');
//        $I->sendPUT('/password',['password' => 'helloworld1010fi']);
//        $I->seeResponseCodeIs(200);
//    }

    public function test18(ApiTester $I)
    {
        $I->wantTo('check response when changing the password (PUT request) to something that does not meet maxLength requirement');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/password',['password' => 'Lorem ipsum dolor sit amet, nonummy ligula volutpat hac integer nonummy. Suspendisse ultricies, congue etiam tellus, erat libero, nulla eleifend, mauris pellentesque. Suspendisse integer praesent vel, integer gravida mauris, fringilla vehicula lacinia non123. Suspendisse integer praesent vel, integer gravida mauris, fringilla vehi. Suspendisse integer praesent vel, integer gravida mauris, fringilla vehi']);
        $I->seeResponseCodeIs(400);
        $body = json_decode($I->grabResponse(), true);
        if (substr_count($body['message'], 'code 110') <= 0) {
            throw new \Exception('Expected error code not present in message', 1466798393);
        }
    }
}
