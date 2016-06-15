<?php


class ResetCest
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
        $I->wantTo('check response when making authenticated get request');
        $I->haveHttpHeader('Authorization', 'Bearer user1');
        $I->sendGET('/reset');
        $I->seeResponseCodeIs(404);
    }

    public function test2(ApiTester $I)
    {
        $I->wantTo('check response when making unauthenticated get request');
        $I->sendGET('/reset');
        $I->seeResponseCodeIs(404);
    }

    public function test3(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated get request');
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
        $I->wantTo('check response when making unauthenticated get request');
        $I->sendGET('/reset/11111111111111111111111111111111');
        $I->seeResponseCodeIs(200);
    }

    public function test5(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated put request');
        $I->sendPUT('/reset/11111111111111111111111111111111',[
            'uid' => '22222222222222222222222222222222',
            'type' => 'phone',
            'value' => '###-###-4567'
        ]);
        $I->seeResponseCodeIs(200);
    }

    public function test6(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated put request');
        $I->sendPUT('/reset/11111111111111111111111111111111',[
            'type' => 'supervisor',
            'value' => '****@sil.com'
        ]);
        $I->seeResponseCodeIs(200);
    }

    public function test7(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated put request');
        $I->sendPUT('/reset/11111111111111111111111111111111/resend');
        $I->seeResponseCodeIs(200);
    }

    public function test8(ApiTester $I)
    {
        $I->wantTo('check response when making authenticated put request');
        $I->sendPUT('/reset/33333333333333333333333333333333/validate',['code' => '333']);
        $I->seeResponseCodeIs(200);
    }

    public function test9(ApiTester $I)
    {
        $I->wantTo('check response when making multiple authenticated put request');
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
