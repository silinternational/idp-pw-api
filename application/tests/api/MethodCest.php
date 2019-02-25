<?php

require_once "BaseCest.php";

class MethodCest extends BaseCest
{

    public function test1(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated DELETE request to method');
        $I->sendDELETE('/method');
        $I->seeResponseCodeIs(401);
    }

    public function test2(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated DELETE request to method');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendDELETE('/method');
        $I->seeResponseCodeIs(405);
    }

    public function test3(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated PATCH request to method');
        $I->sendPATCH('/method');
        $I->seeResponseCodeIs(401);
    }

    public function test4(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated PATCH request to method');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPATCH('/method');
        $I->seeResponseCodeIs(405);
    }

    public function test5(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated GET request for obtaining the'
            . ' methods of a user');
        $I->sendGET('/method');
        $I->seeResponseCodeIs(401);
    }

    public function test6(ApiTester $I, $scenario)
    {
        $I->wantTo('check response that only verified methods exist when making authenticated GET'
            . ' request for obtaining the methods of a user');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendGET('/method');
        $I->seeResponseCodeIs(200);
        $I->cantSeeResponseContainsJson([ // phone verification method is not supported
            'type' => "phone",
        ]);

        $scenario->incomplete('test is broken because fake methods cannot be verified');

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

    public function test62(ApiTester $I)
    {
        $I->wantTo('check response for authenticated GET request to method for a user'
            . ' with auth_type=reset');
        $I->haveHttpHeader('Authorization', 'Bearer user5');
        $I->sendGET('/method');
        $I->seeResponseCodeIs(403);
    }

    public function test7(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated POST request for creating a new method');
        $I->sendPOST('/method',['type'=>'email','value'=>'user@domain.com']);
        $I->seeResponseCodeIs(401);
    }

    public function test8(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated POST request for creating a new method');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPOST('/method',['type'=>'email','value'=>'user@domain.com']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'type' => "email",
            'value' => "user@domain.com"
        ]);
    }

    public function test82(ApiTester $I, $scenario)
    {
        $I->wantTo('check response when making authenticated POST request for creating an'
            . ' already existing method');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPOST('/method',['type'=>'email','value'=>'email-1456769679@domain.org']);

        $scenario->incomplete('test is broken because fake methods cannot be verified');

        $I->seeResponseCodeIs(409);
    }

    public function test84(ApiTester $I)
    {
        $I->wantTo('check response for authenticated POST request to method for a user with'
            . ' auth_type=reset');
        $I->haveHttpHeader('Authorization', 'Bearer user5');
        $I->sendPOST('/method',['type'=>'email','value'=>'email@example.com']);
        $I->seeResponseCodeIs(403);
    }

    public function test9(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated GET request to obtain a method');
        $I->sendGET('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(401);
    }

    public function test10(ApiTester $I, $scenario)
    {
        $I->wantTo('check response when making authenticated GET request to obtain a method');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendGET('/method/22222222222222222222222222222222');

        $scenario->incomplete('test is broken because fake methods are not known by uid');

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => "22222222222222222222222222222222",
            'value' => "email-1456769679@domain.org"
        ]);
    }

    public function test102(ApiTester $I)
    {
        $I->wantTo('check response for authenticated GET request to method/{uid} for a user'
            . ' with auth_type=reset');
        $I->haveHttpHeader('Authorization', 'Bearer user5');
        $I->sendGET('/method/55555555555555555555555555555555');
        $I->seeResponseCodeIs(403);
    }

    public function test11(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated GET request to obtain a method as'
            . ' a non-owner of the method');
        $I->haveHttpHeader('Authorization', 'Bearer user2');
        $I->sendGET('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(404);
    }

    public function test12(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated POST request to method/id');
        $I->sendPOST('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(401);
    }

    public function test13(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated POST request method/id');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPOST('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(405);
    }

    public function test14(ApiTester $I)
    {
        $I->wantTo('check response when making an unauthenticated PUT request with no code');
        $I->sendPUT('/method/11111111111111111111111111111111/verify');
        $I->seeResponseCodeIs(400);
    }

    public function test15(ApiTester $I, $scenario)
    {
        $I->wantTo('check response when making an unauthenticated PUT request with valid code to a'
            . ' validated method when trying to update a method');
        $I->sendPUT('/method/11111111111111111111111111111111/verify',['code'=>'1234']);

        $scenario->incomplete('test is broken because fake methods are not known by uid');

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => "11111111111111111111111111111111",
            'type' => "phone",
            'value' => "1,1234567890"
        ]);
    }

    public function test152(ApiTester $I)
    {
        $I->wantTo('check response when making an unauthenticated PUT request to update a method as a'
            . ' non-owner of the method');
        $I->sendPUT('/method/11111111111111111111111111111111/verify',['code'=>'1234']);
        $I->seeResponseCodeIs(404);
    }

    public function test153(ApiTester $I)
    {
        $I->wantTo('check response when making an unauthenticated PUT request with invalid code and'
            . ' expired verification time when trying to update a method');
        $I->sendPUT('/method/33333333333333333333333333333333/verify',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
    }

    public function test154(ApiTester $I, $scenario)
    {
        $I->wantTo('check response when making an unauthenticated PUT request with invalid code and'
            . ' unexpired verification time when trying to update a method');
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'13245']);

        $scenario->incomplete('test is broken because fake methods are not known by uid');

        $I->seeResponseCodeIs(400);
    }

    public function test155(ApiTester $I, $scenario)
    {
        $I->wantTo('check response when making an unauthenticated PUT request with valid code to an'
            . ' unvalidated method when trying to update a method');
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'123456789']);

        $scenario->incomplete('test is broken because fake methods are not known by uid');

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => "33333333333333333333333333333335",
            'type' => "email",
            'value' => "email-145676972@domain.org"
        ]);
    }

    public function test156(ApiTester $I)
    {
        $I->wantTo('check response when making an unauthenticated PUT request with valid code to'
            . ' an unvalidated method when trying to update a method');
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'123456789']);
        $I->seeResponseCodeIs(404);
    }

    public function test157(ApiTester $I, $scenario)
    {
        $I->wantTo('check response when making multiple unauthenticated PUT requests with invalid'
            . ' code and unexpired verification time when trying to update a method');
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'13245']);

        $scenario->incomplete('test is broken because fake methods are not known by uid');

        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'13245']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/method/33333333333333333333333333333335/verify',['code'=>'13245']);
        $I->seeResponseCodeIs(429);
    }

    public function test16(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated DELETE request to method/id');
        $I->sendDELETE('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(401);
    }

    public function test17(ApiTester $I, $scenario)
    {
        $I->wantTo('check response when making authenticated DELETE request to method/id');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendDELETE('/method/11111111111111111111111111111111');

        $scenario->incomplete('test is broken because fake methods are not known by uid');

        $I->seeResponseCodeIs(200);
        $I->sendGET('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(404);
    }

    public function test172(ApiTester $I, $scenario)
    {
        $I->wantTo('check response when making authenticated DELETE request as a non-owner of'
            . ' the method');
        $I->haveHttpHeader('Authorization', 'Bearer user2');

        $scenario->incomplete('test is broken because fake methods are not known by uid');

        $I->sendDELETE('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(404);
    }

    public function test174(ApiTester $I)
    {
        $I->wantTo('check response for authenticated DELETE request to method/{uid} for a user'
            . ' with auth_type=reset');
        $I->haveHttpHeader('Authorization', 'Bearer user5');
        $I->sendDELETE('/method/55555555555555555555555555555555');
        $I->seeResponseCodeIs(403);
    }

    public function test18(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated PATCH request to method/id');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPATCH('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(405);
    }

    public function test19(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated OPTIONS request to method/id');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendOPTIONS('/method/11111111111111111111111111111111');
        $I->seeResponseCodeIs(200);
    }

    public function test20(ApiTester $I)
    {
        $I->wantTo('check response when making PUT request to method/{uid}/resend with incorrect token');
        $I->haveHttpHeader('Authorization', 'Bearer invalidToken');
        $I->sendPUT('/method/11111111111111111111111111111111/resend');
        $I->seeResponseCodeIs(401);
    }

    public function test21(ApiTester $I)
    {
        $I->wantTo('check response when making PUT request to method/{uid}/resend for a user'
            . ' with auth_type=reset');
        $I->haveHttpHeader('Authorization', 'Bearer user5');
        $I->sendPUT('/method/55555555555555555555555555555555/resend');
        $I->seeResponseCodeIs(403);
    }
}
