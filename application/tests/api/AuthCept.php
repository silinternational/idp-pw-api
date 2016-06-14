<?php

$I = new ApiTester($scenario);
$I->wantTo('check response when logging in');
$I->stopFollowingRedirects();
$I->sendGET('/auth/login');
$I->seeResponseCodeIs(302);

$I = new ApiTester($scenario);
$I->wantTo('check response logging out when logged in');
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendGET('/user/me');
$I->seeResponseCodeIs(200);
$I->sendGET('/auth/logout');
$I->seeResponseCodeIs(302);
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendGET('/user/me');
$I->seeResponseCodeIs(401);
