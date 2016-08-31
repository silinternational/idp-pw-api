<?php
namespace frontend\controllers;

use common\exception\InvalidCodeException;
use common\models\Method;
use common\models\Reset;
use common\models\User;
use frontend\components\BaseRestController;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\TooManyRequestsHttpException;

class MethodController extends BaseRestController
{
    /**
     * Access Control Filter
     * REMEMBER: NEEDS TO BE UPDATED FOR EVERY ACTION
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ]
            ]
        ]);
    }

    /**
     * Return list of available reset methods for user.
     * @return Method[]
     */
    public function actionIndex()
    {
        /** @var User $user */
        $user = \Yii::$app->user->identity;

        $verifiedMethods = $user->getVerifiedMethods();

        $verifiedMethods[] = [
            'type' => Reset::TYPE_PRIMARY,
            'value' => $user->email,
        ];

        if ($user->hasSpouse()) {
            $verifiedMethods[] = [
                'type' => Reset::TYPE_SPOUSE,
                'value' => $user->getSpouseEmail(),
            ];
        }

        if ($user->hasSupervisor()) {
            $verifiedMethods[] = [
                'type' => Reset::TYPE_SUPERVISOR,
                'value' => $user->getSupervisorEmail(),
            ];
        }

        return $verifiedMethods;
    }

    /**
     * View single method
     * @param string $uid
     * @return Method
     * @throws NotFoundHttpException
     */
    public function actionView($uid)
    {
        $method = Method::findOne(['uid' => $uid, 'user_id' => \Yii::$app->user->getId()]);
        if ($method === null) {
            throw new NotFoundHttpException();
        }

        return $method;
    }

    /**
     * Create new unverified method and send verification
     * @return Method
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     * @throws \Exception
     */
    public function actionCreate()
    {
        $request = \Yii::$app->request;

        $type = $request->post('type');
        $value = $request->post('value');

        if ($type === null || ! in_array($type, [Method::TYPE_EMAIL, Method::TYPE_PHONE])) {
            throw new BadRequestHttpException(
                sprintf('Type is required. Options are: %s or %s', Method::TYPE_EMAIL, Method::TYPE_PHONE)
            );
        } elseif ($value === null) {
            throw new BadRequestHttpException('Value is required.');
        }

        /*
         * Check for existing method with this value
         */
        $method = Method::findOne(['value' => $value, 'user_id' => \Yii::$app->user->getId()]);
        if ($method !== null) {
            /*
             * if not verified yet, resend verification
             */
            if ($method->verified === 0) {
                $method->sendVerification();
                return $method;
            } else {
                /*
                 * method exists and is verified, throw conflict
                 */
                throw new ConflictHttpException('Method already exists');
            }
        }

        /*
         * Create method entry to be verified
         */
        $newMethod = Method::createAndSendVerification(
            \Yii::$app->user->getId(),
            $type,
            $value
        );

        return $newMethod;
    }

    /**
     * Validates user submitted code and marks method as verified if valid
     * @param string $uid
     * @return Method
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws TooManyRequestsHttpException
     * @throws \Exception
     */
    public function actionUpdate($uid)
    {
        /*
         * Delete methods not yet verified that have expired - Just-in-time cleanup
         */
        try {
            Method::deleteExpiredUnverifiedMethods();
        } catch (\Exception $e) {
            \Yii::error([
                'action' => 'deleteExpiredUnverifiedMethods',
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);
        }

        /*
         * Find method if belongs to user and is not expired
         */
        /** @var Method $method */
        $method = Method::findOne([
            'uid' => $uid,
            'user_id' => \Yii::$app->user->getId(),
        ]);

        /*
         * If not found, throw 404
         */
        if ($method === null) {
            throw new NotFoundHttpException();
        }

        /*
         * If method is already verified, just return it as if successful
         */
        if ($method->verified === 1) {
            return $method;
        }

        /*
         * Ensure verification attempts is not above limit
         */
        if ($method->verification_attempts >= \Yii::$app->params['reset']['maxAttempts']) {
            throw new TooManyRequestsHttpException();
        }

        /*
         * Get verification code and attempt to verify
         */
        $request = \Yii::$app->request;
        $code = $request->getBodyParam('code');
        if ($code === null) {
            throw new BadRequestHttpException('Code is required');
        }

        try {
            $method->validateAndSetAsVerified($code);
        } catch (InvalidCodeException $e) {
            throw new BadRequestHttpException('Invalid verification code', 1470315942);
        } catch (\Exception $e) {
            throw new ServerErrorHttpException(
                'Unable to set method as verified: ' . $e->getMessage(),
                1470315941,
                $e
            );
        }

        return $method;
    }

    /**
     * Delete method
     * @param string $uid
     * @return array
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Exception
     */
    public function actionDelete($uid)
    {
        /** @var Method $method */
        $method = Method::findOne([
            'uid' => $uid,
            'user_id' => \Yii::$app->user->getId()
        ]);

        if ($method === null) {
            throw new NotFoundHttpException();
        }

        if ( ! $method->delete()) {
            throw new ServerErrorHttpException('Unable to delete method');
        }

        return [];
    }

    /**
     * @param string $uid
     * @return \stdClass
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     * @throws \Exception
     */
    public function actionResend($uid)
    {
        $method = Method::findOne([
            'uid' => $uid,
            'user_id' => \Yii::$app->user->getId(),
        ]);

        if ($method === null) {
            throw new NotFoundHttpException();
        } elseif ($method->verified === 1) {
            throw new BadRequestHttpException('Method already verified');
        }

        /*
         * resend verification
         */
        $method->sendVerification();

        /*
         * Return empty object
         */
        return new \stdClass();
    }
}