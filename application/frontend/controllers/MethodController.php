<?php
namespace frontend\controllers;

use common\models\Method;
use common\models\User;
use frontend\components\BaseRestController;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\Idp\IdBroker\Client\ServiceException;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\TooManyRequestsHttpException;

class MethodController extends BaseRestController
{
    /**
     * @var IdBrokerClient
     */
    public $idBrokerClient;

    public function init()
    {
        parent::init();
        $config = \Yii::$app->params['mfa'];
        $this->idBrokerClient = new IdBrokerClient(
            $config['baseUrl'],
            $config['accessToken'],
            [
                IdBrokerClient::TRUSTED_IPS_CONFIG              => $config['validIpRanges']       ?? [],
                IdBrokerClient::ASSERT_VALID_BROKER_IP_CONFIG   => $config['assertValidBrokerIp']   ?? true,
            ]
        );
    }

    /**
     * Access Control Filter
     * REMEMBER: NEEDS TO BE UPDATED FOR EVERY ACTION
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'matchCallback' => function () {
                            $user = \Yii::$app->user->identity;
                            return ($user->isAuthScopeFull());
                        }
                    ],
                ]
            ]
        ]);
    }

    /**
     * Return list of available reset methods for user.
     * @return array<Method|array>
     */
    public function actionIndex()
    {
        /** @var User $user */
        $user = \Yii::$app->user->identity;

        return $user->getMethodsAndPersonnelEmails();
    }

    /**
     * View single method
     * @param string $uid
     * @return array<string,string>
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function actionView($uid)
    {
        $employeeId = \Yii::$app->user->identity->employee_id;

        $method = Method::getOneVerifiedMethod($uid, $employeeId);

        $method['type'] = 'email';
        return $method;
    }

    /**
     * Create new unverified method and send verification
     * @return array<string,string>
     * @throws BadRequestHttpException
     * @throws \Exception
     */
    public function actionCreate()
    {
        $request = \Yii::$app->request;

        $value = $request->post('value');
        if ($value === null) {
            throw new BadRequestHttpException(\Yii::t('app', 'Value is required'), 1542750428);
        }

        $employeeId = \Yii::$app->user->identity->employee_id;

        try {
            $method = $this->idBrokerClient->createMethod($employeeId, $value);
        } catch (ServiceException $e) {
            if ($e->httpStatusCode === 409) {
                throw new TooManyRequestsHttpException(
                    \Yii::t('app', 'Recovery method already exists'),
                    1542750430
                );
            } elseif ($e->httpStatusCode === 400) {
                throw new BadRequestHttpException(\Yii::t('app', 'Value is required'), 1542750431);
            } else {
                throw $e;
            }
        }
        $method['type'] = 'email';
        return $method;
    }

    /**
     * Validates user submitted code and marks method as verified if valid
     * @param string $uid
     * @return array<string,string>
     * @throws BadRequestHttpException
     * @throws HttpException
     * @throws \Exception
     */
    public function actionVerify($uid)
    {
        $messages = [
            400 => 'Invalid verification code',
            404 => 'Recovery method not found',
            410 => 'Expired verification code',
            429 => 'Too many failures for this recovery method',
        ];

        $codes = [
            400 => 1542749429,
            404 => 1542749427,
            410 => 1545144979,
            429 => 1542749428,
        ];

        $code = \Yii::$app->request->getBodyParam('code');
        if ($code === null) {
            throw new BadRequestHttpException(\Yii::t('app', 'Code is required'), 1542749426);
        }

        $employeeId = \Yii::$app->user->identity->employee_id;

        try {
            $method = $this->idBrokerClient->verifyMethod($uid, $employeeId, $code);
        } catch (ServiceException $e) {
            throw new HttpException(
                $e->httpStatusCode,
                \Yii::t('app', $messages[$e->httpStatusCode] ?? ''),
                $codes[$e->httpStatusCode] ?? 0
            );
        }

        $method['type'] = 'email';
        return $method;
    }

    /**
     * Delete method
     * @param string $uid
     * @return \stdClass
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function actionDelete($uid)
    {
        $employeeId = \Yii::$app->user->identity->employee_id;

        try {
            $this->idBrokerClient->deleteMethod($uid, $employeeId);
        } catch (ServiceException $e) {
            if ($e->httpStatusCode === 404) {
                throw new NotFoundHttpException(\Yii::t('app', 'Recovery method not found'), 1542749425);
            } else {
                throw $e;
            }
        }

        \Yii::$app->response->statusCode = 204;

        /*
         * Return empty object
         */
        return new \stdClass();
    }

    /**
     * @param string $uid
     * @return \stdClass
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function actionResend($uid)
    {
        $employeeId = \Yii::$app->user->identity->employee_id;

        try {
            $this->idBrokerClient->resendMethod($uid, $employeeId);
        } catch (ServiceException $e) {
            if ($e->httpStatusCode === 404) {
                throw new NotFoundHttpException(\Yii::t('app', 'Recovery method not found'), 1542749424);
            } else {
                throw $e;
            }
        }

        \Yii::$app->response->statusCode = 204;

        /*
         * Return empty object
         */
        return new \stdClass();
    }

}
