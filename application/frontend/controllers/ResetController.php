<?php
namespace frontend\controllers;

use common\components\personnel\NotFoundException;
use common\helpers\Utils;
use common\models\EventLog;
use common\models\Reset;
use common\models\User;
use frontend\components\BaseRestController;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class ResetController extends BaseRestController
{
    /**
     * Access Control Filter
     * NEEDS TO BE UPDATED FOR EVERY ACTION
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ]
            ],
            'authenticator' => [
                'only' => [''], // Bypass authentication for all actions
            ],
        ]);
    }

    /**
     * @param String $uid
     * @return Reset
     * @throws NotFoundHttpException
     */
    public function actionView($uid)
    {
        $reset = Reset::findOne(['uid' => $uid]);
        if ($reset === null) {
            throw new NotFoundHttpException();
        }

        return $reset;
    }

    /**
     * Create new reset process
     * @return Reset|\stdClass
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionCreate()
    {
        $username = \Yii::$app->request->post('username');
        $verificationToken = \Yii::$app->request->post('verification_token');

        if ( ! $username) {
            throw new BadRequestHttpException(\Yii::t('app', 'Username is required'));
        }

        /*
         * Validate reCaptcha $verificationToken before proceeding.
         * This will throw an exception if not successful, checking response to
         * be double sure an exception is thrown.
         */
        if (\Yii::$app->params['recaptcha']['required']) {
            if ( ! $verificationToken) {
                throw new BadRequestHttpException(\Yii::t('app', 'reCAPTCHA verification code is required'));
            }
            
            $clientIp = Utils::getClientIp(\Yii::$app->request);
            if ( ! Utils::isRecaptchaResponseValid($verificationToken, $clientIp)) {
                throw new BadRequestHttpException(\Yii::t('app', 'reCAPTCHA failed verification'));
            }
        }

        /*
         * Check if $username looks like an email address
         */
        $usernameIsEmail = false;
        if (substr_count($username, '@')) {
            $usernameIsEmail = true;
        }

        /*
         * Find or create user, if user not found return empty object
         */
        try {
            if ($usernameIsEmail) {
                $user = User::findOrCreate(null, $username);
            } else {
                $user = User::findOrCreate($username);
            }
        } catch (NotFoundException $e) {
            \Yii::warning([
                'action' => 'create reset',
                'username' => $username,
                'status' => 'error',
                'error' => 'user not found',
            ]);
            return new \stdClass();
        } catch (\Exception $e) {
            \Yii::error([
                'action' => 'create reset',
                'username' => $username,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);
            throw new ServerErrorHttpException(
                \Yii::t('app', 'Unable to create new reset'),
                1469036552
            );
        }


        /*
         * Clear out expired resets
         */
        Reset::deleteExpired();
        
        /*
         * Find or create a reset
         */
        $reset = Reset::findOrCreate($user);

        /*
         * Send reset notification
         */
        $reset->send();

        return $reset;
    }

    /**
     * Update reset type/method and send verification
     * @param string $uid
     * @return Reset
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \yii\web\ServerErrorHttpException
     * @throws \yii\web\TooManyRequestsHttpException
     */
    public function actionUpdate($uid)
    {
        /** @var Reset $reset */
        $reset = Reset::findOne(['uid' => $uid]);
        if ($reset === null) {
            throw new NotFoundHttpException(
                \Yii::t('app', 'Reset not found'),
                1462989590
            );
        }

        $type = \Yii::$app->request->getBodyParam('type', null);
        $methodId = \Yii::$app->request->getBodyParam('uid', null);

        if ($type === null) {
            throw new BadRequestHttpException(
                \Yii::t('app', 'Invalid reset type'),
                1462989664
            );
        }

        /*
         * Update type
         */
        $reset->setType($type, $methodId);

        /*
         * Send verification
         */
        $reset->send();

        return $reset;
    }

    /**
     * @param string $uid
     * @return Reset
     * @throws NotFoundHttpException
     */
    public function actionResend($uid)
    {
        /** @var Reset $reset */
        $reset = Reset::findOne(['uid' => $uid]);
        if ($reset === null) {
            throw new NotFoundHttpException();
        }

        /*
         * Resend verification
         */
        $reset->send();

        return $reset;
    }

    /**
     * Validate reset code. Logs user in if successful
     * @param string $uid
     * @return array
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Exception
     */
    public function actionValidate($uid)
    {
        /** @var Reset $reset */
        $reset = Reset::findOne(['uid' => $uid]);
        if ($reset === null) {
            throw new NotFoundHttpException();
        }

        /*
         * Validate required parameters are present or throw 400 error
         */
        $code = \Yii::$app->request->getBodyParam('code', null);
        if ($code === null) {
            throw new BadRequestHttpException(
                \Yii::t('app', 'Code is required'),
                1462989866
            );
        }

        try {
            $clientId = Utils::getClientIdOrFail();
        } catch (\Exception $e) {
            throw new BadRequestHttpException(
                \Yii::t('app', 'Client ID is missing'),
                1483979025
            );
        }

        $log = [
            'action' => 'Validate reset',
            'reset_id' => $reset->id,
            'user' => $reset->user->email,
        ];

        $isValid = $reset->isUserProvidedCodeCorrect($code);
        if ($isValid === true) {

            $ipAddress = Utils::getClientIp(\Yii::$app->request);
            if ($reset->type === Reset::TYPE_METHOD) {
                $methodType = $reset->method->type;
            } else {
                $methodType = null;
            }

            /*
             * Log event with reset type/method details
             */
            EventLog::log(
                'ResetVerificationSuccessful',
                [
                    'Reset Type' => $reset->type,
                    'Attempts' => $reset->attempts,
                    'IP Address' => $ipAddress,
                    'Method type (if reset type is method)' => $methodType,
                    'Method value' => $reset->getMaskedValue(),
                ],
                $reset->user_id
            );

            /*
             * Reset verified successfully, create access token for user
             */
            try {
                $accessToken = $reset->user->createAccessToken($clientId, User::AUTH_TYPE_RESET);

                $log['status'] = 'success';
                \Yii::warning($log);

                /*
                 * Delete reset record, log errors, but let user proceed
                 */
                if ( ! $reset->delete()) {
                    \Yii::warning([
                        'action' => 'delete reset after validation',
                        'reset_id' => $reset->id,
                        'status' => 'error',
                        'error' => Json::encode($reset->getFirstErrors()),
                    ]);
                }

                /*
                 * return empty object so that it gets json encoded to {}
                 */
                return [
                    'access_token' => $accessToken,
                ];
            } catch (\Exception $e) {
                $log['status'] = 'error';
                $log['error'] = 'Unable to log user in after successful reset verification';
                \Yii::error($log);
                throw $e;
            }
        }

        EventLog::log(
            'ResetVerificationFailed',
            [
                'reset_id' => $reset->id,
                'type' => $reset->type,
                'attempts' => $reset->attempts,
            ],
            $reset->user_id
        );

        $log['status'] = 'error';
        $log['error'] = 'Reset code verification failed';
        \Yii::warning($log);
        throw new BadRequestHttpException(
            \Yii::t('app', 'Invalid verification code'),
            1462991098
        );
    }
}