<?php

use tests\api\FixtureHelper;

class MethodCest extends BaseCest
{
    // tests

    public function test1(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated delete request');
        $I->sendDELETE('/method');
        $I->seeResponseCodeIs(404);
    }

    public function test2(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated delete request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendDELETE('/method');
        $I->seeResponseCodeIs(404);
    }

    public function test3(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated patch request');
        $I->sendPATCH('/method');
        $I->seeResponseCodeIs(404);
    }

    public function test4(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated patch request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPATCH('/method');
        $I->seeResponseCodeIs(404);
    }

    public function test5(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated get request');
        $I->sendGET('/method');
        $I->seeResponseCodeIs(401);
    }

    public function test6(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated get request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendGET('/method');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => "11111111111111111111111111111111",
            'type' => "phone",
            'value' => "1,1234567890",
        ]);
    }

    public function test62(ApiTester $I)
    {
        $I->wantTo('check response for only verified methods when making authenticated get request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendGET('/method');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => "11111111111111111111111111111111",
            'type' => "phone",
            'value' => "1,1234567890",
        ]);
        $I->seeResponseContainsJson([
            'id' => "22222222222222222222222222222222",
            'type' => "email",
            'value' => "email-1456769679@domain.org",
        ]);
        $I->cantSeeResponseContainsJson([
            'value' => 'email-1456769721@domain.org'
        ]);
        $I->cantSeeResponseContainsJson([
            'value' => '1,1234567891'
        ]);
        $I->cantSeeResponseContainsJson([
            'value' => 'email-145676972@domain.org'
        ]);
    }

    public function test7(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated post request');
        $I->sendPOST('/method',['type'=>'email','value'=>'user@domain.com']);
        $I->seeResponseCodeIs(401);
    }

    public function test8(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated post request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPOST('/method',['type'=>'email','value'=>'user@domain.com']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'type' => "email",
            'value' => "user@domain.com"
        ]);
    }

    public function test82(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated post request for existing method');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPOST('/method',['type'=>'phone','value'=>'1,1234567890']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'type' => "phone",
            'value' => "1,1234567890"
        ]);
    }

    public function test9(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated get request');
        $I->sendGET('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(401);
    }

    public function test10(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated get request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendGET('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => "11111111111111111111111111111111",
            'type' => "phone",
            'value' => "1,1234567890"
        ]);
    }

    public function test11(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated get request as other user');
        $I->haveHttpHeader('Authorization', 'Bearer user2');
        $I->sendGET('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(404);
    }

    public function test12(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated post request');
        $I->sendPOST('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(404);
    }

    public function test13(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated post request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPOST('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(404);
    }

    public function test14(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated put request');
        $I->sendPUT('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(401);
    }

    public function test15(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated put request with valid code to a validated method');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/method/11111111111111111111111111111111',['code'=>'1234']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => "11111111111111111111111111111111",
            'type' => "phone",
            'value' => "1,1234567890"
        ]);
    }

    public function test152(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated put request as other user');
        $I->haveHttpHeader('Authorization', 'Bearer user2');
        $I->sendPUT('/method/11111111111111111111111111111111',['code'=>'1234']);
        $I->seeResponseCodeIs(404);
    }

    public function test153(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated put request with invalid code and expired verification time');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/method/33333333333333333333333333333333',['code'=>'13245']);
        $I->seeResponseCodeIs(404);
    }

    public function test154(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated put request with invalid code and unexpired verification time');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
    }

    public function test155(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated put request with valid code to an unvalidated method');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'123456789']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => "33333333333333333333333333333335",
            'type' => "email",
            'value' => "email-145676972@domain.org"
        ]);
    }

    public function test156(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated put request with valid code to an unvalidated method');
        $I->haveHttpHeader('Authorization', 'Bearer user2');
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'123456789']);
        $I->seeResponseCodeIs(404);
    }

    public function test157(ApiTester $I)
    {
        $I->wantTo('check response when making multiple authenticated put request with invalid code and unexpired verification time');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335',['code'=>'13245']);
        $I->seeResponseCodeIs(429);
    }

    public function test16(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated delete request');
        $I->sendDELETE('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(401);
    }

    public function test17(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated delete request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendDELETE('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(200);
    }

    public function test172(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated delete request as other user');
        $I->haveHttpHeader('Authorization', 'Bearer user2');
        $I->sendDELETE('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(404);
    }

    public function test18(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated patch request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPATCH('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(404);
    }

    public function test19(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated options request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendOPTIONS('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(200);
    }
}
