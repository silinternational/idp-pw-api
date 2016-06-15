<?php


class MethodCest
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

    public function test7(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated post request');
        $I->sendPOST('/method',['type'=>'email','value'=>'shep@gmail.com']);
        $I->seeResponseCodeIs(401);
    }

    public function test8(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated post request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPOST('/method',['type'=>'email','value'=>'shep@gmail.com']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'type' => "email",
            'value' => "shep@gmail.com"
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
        $I->wantTo('check response when making authenticated put request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/method/11111111111111111111111111111111',['code'=>'1234']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => "11111111111111111111111111111111",
            'type' => "phone",
            'value' => "1,1234567890"
        ]);
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
