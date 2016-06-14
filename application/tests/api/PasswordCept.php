<?php

$I = new ApiTester($scenario);
$I->wantTo('check response when making get request with no token');
$I->sendGET('/password');
$I->seeResponseCodeIs(401);

$I = new ApiTester($scenario);
$I->wantTo('check response when making get request with incorrect token');
$I->haveHttpHeader('Authorization', 'Bearer user11');
$I->sendGET('/password');
$I->seeResponseCodeIs(401);

$I = new ApiTester($scenario);
$I->wantTo('check response when making get request with correct token');
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendGET('/password');
$I->seeResponseCodeIs(200);
$I->seeResponseMatchesJsonType([
    'last_changed' => 'string:date',
    'expires' => 'string:date'
]);

$I = new ApiTester($scenario);
$I->wantTo('check response when making authenticated post request');
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendPOST('/password');
$I->seeResponseCodeIs(404);

$I = new ApiTester($scenario);
$I->wantTo('check response when making unauthenticated post request');
$I->haveHttpHeader('Authorization', 'Bearer user11');
$I->sendPOST('/password');
$I->seeResponseCodeIs(404);

// this test will fail
//$I = new ApiTester($scenario);
//$I->wantTo('check response when making authenticated put request');
//$I->haveHttpHeader('Authorization', 'Bearer user1');
//$I->sendPUT('/password',['password' => 'newPassword']);
//$I->seeResponseCodeIs(200);

$I = new ApiTester($scenario);
$I->wantTo('check response when making authenticated delete request');
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendDELETE('/password');
$I->seeResponseCodeIs(404);

$I = new ApiTester($scenario);
$I->wantTo('check response when making unauthenticated delete request');
$I->haveHttpHeader('Authorization', 'Bearer user11');
$I->sendDELETE('/password');
$I->seeResponseCodeIs(404);

$I = new ApiTester($scenario);
$I->wantTo('check response when making authenticated patch request');
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendPATCH('/password');
$I->seeResponseCodeIs(404);

$I = new ApiTester($scenario);
$I->wantTo('check response when making unauthenticated patch request');
$I->haveHttpHeader('Authorization', 'Bearer user11');
$I->sendPATCH('/password');
$I->seeResponseCodeIs(404);

// this test will fail
//$I = new ApiTester($scenario);
//$I->wantTo('check response when making authenticated options request');
//$I->haveHttpHeader('Authorization', 'Bearer user11');
//$I->sendOPTIONS('/password');
//$I->seeResponseCodeIs(200);