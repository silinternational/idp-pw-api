<?php

namespace common\components\auth;

use Yii;
use yii\filters\auth\AuthMethod;
use yii\web\UnauthorizedHttpException;

class HttpOnlyAuth extends AuthMethod
{
    /**
     * Authenticates the user based on the access_token stored in the session.
     * @param \yii\web\User $user the user object
     * @param \yii\web\Request $request the request object
     * @param \yii\web\Response $response the response object
     * @return \yii\web\IdentityInterface | null the authenticated user identity. If authentication information is not provided, null will be returned.
     * @throws UnauthorizedHttpException if authentication information is provided but is invalid.
     */
    public function authenticate($user, $request, $response)
    {
        $accessToken = \Yii::$app->request->get('access_token');
        $expirationTime = $accessToken->expire;
        $currentTime = time();

        if ($accessToken === null) {
            return null;
        }
        if ($expirationTime <= $currentTime) {
            return null;
        }

        $identity = $user->loginByAccessToken($accessToken, get_class($this));
        if ($identity === null) {
            $this->handleFailure($response);
        }

        return $identity;
    }

    /**
     * @inheritdoc
     */
    public function handleFailure($response)
    {
        throw new UnauthorizedHttpException('Your request was made with invalid credentials.');
    }
}
