<?php


class ResetCest extends BaseCest
{

    public function test1(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated GET request to /reset');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendGET('/reset');
        $I->seeResponseCodeIs(405);
    }

    public function test12(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated DELETE request to /reset');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendDELETE('/reset');
        $I->seeResponseCodeIs(405);
    }

    public function test13(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated DELETE request to /reset');
        $I->sendDELETE('/reset');
        $I->seeResponseCodeIs(401);
    }

    public function test14(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated OPTIONS request to /reset');
        $I->sendOPTIONS('/reset');
        $I->seeResponseCodeIs(200);
    }

    public function test15(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated OPTIONS request to /reset');
        $I->sendOPTIONS('/reset');
        $I->seeResponseCodeIs(200);
    }

    public function test2(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated GET request to /reset');
        $I->sendGET('/reset');
        $I->seeResponseCodeIs(401);
    }

    public function test3(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated GET request for obtaining reset object');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendGET('/reset/11111111111111111111111111111111');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'uid' => "11111111111111111111111111111111",
            'methods' => [
                'type' => "primary",
                'value' => "f****_l**t@o***********.o**",
            ]
        ]);
    }

    public function test4(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated GET request for obtaining reset object');
        $I->sendGET('/reset/11111111111111111111111111111111');
        $I->seeResponseCodeIs(200);
    }

    public function test5(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated PUT request for updating a reset object');
        $I->sendPUT('/reset/11111111111111111111111111111111',[
            'uid' => '22222222222222222222222222222222',
            'type' => 'phone',
            'value' => '###-###-4567'
        ]);
        $I->seeResponseCodeIs(200);
    }

    public function test6(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated PUT request for updating a reset object');
        $I->sendPUT('/reset/11111111111111111111111111111111',[
            'type' => 'supervisor',
            'value' => '****@sil.com'
        ]);
        $I->seeResponseCodeIs(200);
    }

    public function test62(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated DELETE request to /reset/id');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendDELETE('/reset/11111111111111111111111111111111',[
            'type' => 'supervisor',
            'value' => '****@sil.com'
        ]);
        $I->seeResponseCodeIs(405);
    }

    public function test63(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated DELETE request to /reset/id');
        $I->sendDELETE('/reset/11111111111111111111111111111111',[
            'type' => 'supervisor',
            'value' => '****@sil.com'
        ]);
        $I->seeResponseCodeIs(401);
    }

    public function test7(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated PUT request to resend the reset');
        $I->sendPUT('/reset/11111111111111111111111111111111/resend');
        $I->seeResponseCodeIs(200);
    }

    public function test72(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated PUT request to resend the verification');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/reset/11111111111111111111111111111111/resend');
        $I->seeResponseCodeIs(200);
    }


    public function test8(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated PUT request to validate a reset code');
        $I->sendPUT('/reset/33333333333333333333333333333333/validate',['code' => '333']);
        $I->seeResponseCodeIs(200);
    }

    public function test82(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated PUT request to validate a reset code');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendPUT('/reset/33333333333333333333333333333333/validate',['code' => '333']);
        $I->seeResponseCodeIs(200);
    }

    public function test83(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated DELETE request to reset/id/validate');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendDELETE('/reset/33333333333333333333333333333333/validate',['code' => '333']);
        $I->seeResponseCodeIs(405);
    }

    public function test9(ApiTester $I)
    {
        $I->wantTo('check response when making multiple authenticated PUT request to validate a reset code');
        $I->sendPUT('/reset/33333333333333333333333333333334/validate',['code' => '344']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/reset/33333333333333333333333333333334/validate',['code' => '344']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/reset/33333333333333333333333333333334/validate',['code' => '344']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/reset/33333333333333333333333333333334/validate',['code' => '344']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/reset/33333333333333333333333333333334/validate',['code' => '344']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/reset/33333333333333333333333333333334/validate',['code' => '344']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/reset/33333333333333333333333333333334/validate',['code' => '344']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/reset/33333333333333333333333333333334/validate',['code' => '344']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/reset/33333333333333333333333333333334/validate',['code' => '344']);
        $I->seeResponseCodeIs(400);
        $I->sendPUT('/reset/33333333333333333333333333333334/validate',['code' => '344']);
        $I->seeResponseCodeIs(429);
    }
}
