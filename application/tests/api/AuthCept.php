<?php

$I = new ApiTester($scenario);
$I->wantTo('check response when logging in with no client_id');
$I->stopFollowingRedirects();
$I->sendGET('/auth/login');
$I->seeResponseCodeIs(401);

$I = new ApiTester($scenario);
$I->wantTo('check response when logging in with client_id');
$I->stopFollowingRedirects();
$I->sendGET('/auth/login?client_id=asdf');
$I->seeResponseCodeIs(302);

$I = new ApiTester($scenario);
$I->wantTo('check response when logging in with client_id and access-token');
$I->stopFollowingRedirects();
$I->haveHttpHeader('Authorization', 'Bearer user1');
$I->sendGET('/auth/login?client_id=asdf');
$I->seeResponseCodeIs(302);

$I = new ApiTester($scenario);
$I->wantTo('check response logging out when logged in');
$I->haveHttpHeader('Authorization', 'Bearer user2');
$I->sendGET('/user/me');
$I->seeResponseCodeIs(200);
$I->sendGET('/auth/logout');
$I->seeResponseCodeIs(302);
$I->haveHttpHeader('Authorization', 'Bearer user2');
$I->sendGET('/user/me');
$I->seeResponseCodeIs(401);

$I = new ApiTester($scenario);
$I->wantTo('check response logging out when logged out');
$I->haveHttpHeader('Authorization', 'Bearer user22');
$I->sendGET('/user/me');
$I->seeResponseCodeIs(401);
$I->sendGET('/auth/logout');
$I->seeResponseCodeIs(401);
$I->haveHttpHeader('Authorization', 'Bearer user2');
$I->sendGET('/user/me');
$I->seeResponseCodeIs(401);