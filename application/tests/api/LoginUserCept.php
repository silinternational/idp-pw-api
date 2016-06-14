<?php
use Codeception\Util\Xml as XmlUtils;

$I = new ApiTester($scenario);
$I->wantTo('perform actions and see result');
$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
$I->sendGET('/user/me');
$I->seeResponseCodeIs(200);

$I = new ApiTester($scenario);
$I->wantTo('do something cool');
$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
$I->sendGET('/config');
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson(['idpName' => 'SIL']);
$I->seeResponseContainsJson([
    'support' => [
        'email' => 'info@insitehome.org',
    ]
]);