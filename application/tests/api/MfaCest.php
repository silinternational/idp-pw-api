<?php

require_once "BaseCest.php";

class MfaCest extends BaseCest
{
    public function test10(ApiTester $I)
    {
        $I->wantTo('check response when making GET request to /mfa with incorrect token');
        $I->haveHttpHeader('Authorization', 'Bearer invalidToken');
        $I->sendGET('/mfa');
        $I->seeResponseCodeIs(401);
    }

    public function test11(ApiTester $I)
    {
        $I->wantTo('check response when making GET request to /mfa for a user'
            . ' with auth_type=reset');
        $I->haveHttpHeader('Authorization', 'Bearer user5');
        $I->sendGET('/mfa');
        $I->seeResponseCodeIs(403);
    }

    // TODO: Add test(s) for authorized access to GET /mfa

    public function test20(ApiTester $I)
    {
        $I->wantTo('check response when making POST request to /mfa with incorrect token');
        $I->haveHttpHeader('Authorization', 'Bearer invalidToken');
        $I->sendPOST('/mfa');
        $I->seeResponseCodeIs(401);
    }

    public function test21(ApiTester $I)
    {
        $I->wantTo('check response when making POST request to /mfa for a user'
            . ' with auth_type=reset');
        $I->haveHttpHeader('Authorization', 'Bearer user5');
        $I->sendPOST('/mfa');
        $I->seeResponseCodeIs(403);
    }

    // TODO: Add test(s) for authorized access to POST /mfa

    public function test30(ApiTester $I)
    {
        $I->wantTo('check response when making PUT request to mfa/{id} with incorrect token');
        $I->haveHttpHeader('Authorization', 'Bearer invalidToken');
        $I->sendPUT('/mfa/1');
        $I->seeResponseCodeIs(401);
    }

    public function test31(ApiTester $I)
    {
        $I->wantTo('check response when making PUT request to mfa/{id} for a user'
            . ' with auth_type=reset');
        $I->haveHttpHeader('Authorization', 'Bearer user5');
        $I->sendPUT('/mfa/5');
        $I->seeResponseCodeIs(403);
    }

    // TODO: Add test(s) for authorized access to PUT /mfa/{id}

    public function test40(ApiTester $I)
    {
        $I->wantTo('check response when making DELETE request to mfa/{id} with incorrect token');
        $I->haveHttpHeader('Authorization', 'Bearer invalidToken');
        $I->sendDELETE('/mfa/1');
        $I->seeResponseCodeIs(401);
    }

    public function test41(ApiTester $I)
    {
        $I->wantTo('check response when making DELETE request to mfa/{id} for a user'
            . ' with auth_type=reset');
        $I->haveHttpHeader('Authorization', 'Bearer user5');
        $I->sendDELETE('/mfa/5');
        $I->seeResponseCodeIs(403);
    }

    // TODO: Add test(s) for authorized access to DELETE /mfa/{id}

    public function test50(ApiTester $I)
    {
        $I->wantTo('check response when making PUT request to mfa/{id}/verify with incorrect token');
        $I->haveHttpHeader('Authorization', 'Bearer invalidToken');
        $I->sendPUT('/mfa/1/verify');
        $I->seeResponseCodeIs(401);
    }

    public function test51(ApiTester $I)
    {
        $I->wantTo('check response when making PUT request to mfa/{id}/verify for a user'
            . ' with auth_type=reset');
        $I->haveHttpHeader('Authorization', 'Bearer user5');
        $I->sendPUT('/mfa/5/verify');
        $I->seeResponseCodeIs(403);
    }

    public function test52(ApiTester $I)
    {
        $I->wantTo('check response when making PUT request to mfa/{id}/verify/registration for a user'
            . ' with auth_type=reset');
        $I->haveHttpHeader('Authorization', 'Bearer user5');
        $I->sendPUT('/mfa/5/verify/registration');
        $I->seeResponseCodeIs(403);
    }

    // TODO: Add test(s) for authorized access to PUT /mfa/{id}/verify
}
