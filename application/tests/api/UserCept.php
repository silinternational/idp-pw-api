<?php

$I = new ApiTester($scenario);
$I->wantTo('check response when passing in correct token');
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendGET('/user/me');
$I->seeResponseCodeIs(200);
$I->seeResponseContainsJson([
    'first_name' => "User",
    'last_name' => "One",
    'idp_username' => 'first_last',
    'email' => 'first_last@organization.org',
]);

$I = new ApiTester($scenario);
$I->wantTo('check response when passing in incorrect token');
$I->haveHttpHeader('Authorization', 'Bearer user11');
$I->sendGET('/user/me');
$I->seeResponseCodeIs(401);

$I = new ApiTester($scenario);
$I->wantTo('check response when making authenticated post request');
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendPOST('/user/me');
$I->seeResponseCodeIs(404);

$I = new ApiTester($scenario);
$I->wantTo('check response when making unauthenticated post request');
$I->haveHttpHeader('Authorization', 'Bearer user11');
$I->sendPOST('/user/me');
$I->seeResponseCodeIs(404);

$I = new ApiTester($scenario);
$I->wantTo('check response when making authenticated delete request');
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendDELETE('/user/me');
$I->seeResponseCodeIs(404);

$I = new ApiTester($scenario);
$I->wantTo('check response when making unauthenticated delete request');
$I->haveHttpHeader('Authorization', 'Bearer user11');
$I->sendDELETE('/user/me');
$I->seeResponseCodeIs(404);

$I = new ApiTester($scenario);
$I->wantTo('check response when making authenticated patch request');
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendPATCH('/user/me');
$I->seeResponseCodeIs(404);

$I = new ApiTester($scenario);
$I->wantTo('check response when making unathenticated patch request');
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendPATCH('/user/me');
$I->seeResponseCodeIs(404);

// this test will fail
$I = new ApiTester($scenario);
$I->wantTo('check response when making authenticated options request');
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendOPTIONS('/user/me');
$I->seeResponseCodeIs(200);

// this test will fail
$I = new ApiTester($scenario);
$I->wantTo('check response when making unauthenticated options request');
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendOPTIONS('/user/me');
$I->seeResponseCodeIs(200);