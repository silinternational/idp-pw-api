<?php

$I = new ApiTester($scenario);
$I->wantTo('check response when making get request');
$I->sendGET('/config');
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson(['idpName' => 'SIL']);
$I->seeResponseContainsJson([
    'support' => [
        'email' => 'info@insitehome.org',
    ]
]);

$I = new ApiTester($scenario);
$I->wantTo('check response when making post request');
$I->sendPOST('/config');
$I->seeResponseCodeIs(404);

$I = new ApiTester($scenario);
$I->wantTo('check response when making put request');
$I->sendPUT('/config');
$I->seeResponseCodeIs(404);

$I = new ApiTester($scenario);
$I->wantTo('check response when making delete request');
$I->sendDELETE('/config');
$I->seeResponseCodeIs(404);

$I = new ApiTester($scenario);
$I->wantTo('check response when making patch request');
$I->sendPATCH('/config');
$I->seeResponseCodeIs(404);

// this test will fail
$I = new ApiTester($scenario);
$I->wantTo('check response when making options request');
$I->sendOPTIONS('/config');
$I->seeResponseCodeIs(200);


