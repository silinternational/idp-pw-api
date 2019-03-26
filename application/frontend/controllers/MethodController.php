<?php
namespace frontend\controllers;

use common\helpers\Utils;
use common\models\Method;
use common\models\User;
use frontend\components\BaseRestController;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\Idp\IdBroker\Client\ServiceException;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
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
        $config = \Yii::$app->params['idBrokerConfig'];
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
                        'actions' => ['verify'],
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'matchCallback' => function () {
                            $user = \Yii::$app->user->identity;
                            return ($user->isAuthScopeFull());
                        }
                    ],
                ]
            ],
            'authenticator' => [
                'except' => ['verify'] // bypass authentication for /method/{id}/verify
            ],
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
        $messages = [
            400 => \Yii::t('app', 'Value is required'),
            409 => \Yii::t('app', 'Recovery method already exists'),
            422 => \Yii::t('app', 'Invalid email address provided'),
        ];

        $request = \Yii::$app->request;

        $value = $request->post('value');
        if ($value === null) {
            throw new BadRequestHttpException(\Yii::t('app', 'Value is required'), 1542750428);
        }

        $employeeId = \Yii::$app->user->identity->employee_id;

        if (\Yii::$app->user->identity->email == $value) {
            throw new ConflictHttpException(
                \Yii::t('app', 'Primary email address supplied as alternate recovery method.'),
                1550138424
            );
        }
        try {
            $method = $this->idBrokerClient->createMethod($employeeId, $value);
        } catch (ServiceException $e) {
            throw new HttpException(
                $e->httpStatusCode,
                $messages[$e->httpStatusCode] ?? '',
                1542750430
            );
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
            400 => \Yii::t('app', 'Invalid verification code'),
            404 => \Yii::t('app', 'Recovery method not found'),
            410 => \Yii::t('app', 'Expired verification code'),
            429 => \Yii::t('app', 'Too many failures for this recovery method'),
        ];

        $code = \Yii::$app->request->getBodyParam('code');
        if ($code === null) {
            throw new BadRequestHttpException(\Yii::t('app', 'Code is required'), 1542749426);
        }

        try {
            $method = $this->idBrokerClient->verifyMethod($uid, '', $code);
        } catch (ServiceException $e) {
            throw new HttpException(
                $e->httpStatusCode,
                $messages[$e->httpStatusCode] ?? '',
                1542749427
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

    /**
     * Move data from local Method table to id-broker Method table
     */
    public function actionMove()
    {
        $startTime = microtime(true);

        try {
            Method::deleteExpiredUnverifiedMethods();
        } catch (\Throwable $e) {
            \Yii::error([
                'action' => 'method/move',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        }

        $methods = Method::find()
            ->where(['verified' => 1, 'type' => Method::TYPE_EMAIL, 'deleted_at' => null])
            ->limit(100)
            ->all();

        if (empty($methods)) {
            \Yii::$app->response->statusCode = 204;
        }

        $n = count($methods);
        $errorCount = 0;

        /**
         * @var Method $method
         */
        foreach ($methods as $method) {
            try {
                $brokerMethod = $this->idBrokerClient->createMethod(
                    $method->user->employee_id,
                    $method->value,
                    $method->created
                );

                if ($brokerMethod['value'] !== $method->value) {
                    throw new \Exception('received value does not equal sent value');
                }

                $method->deleted_at = Utils::getDatetime();
                $method->save();
            } catch (\Throwable $e) {
                \Yii::error([
                    'action' => 'method/move',
                    'error' => $e->getMessage(),
                    'method_id' => $method->uid,
                    'code' => $e->getCode(),
                ]);
                $errorCount++;
            }
        }

        $endTime = microtime(true);

        return [
            'count' => $n,
            'seconds' => (string)round($endTime - $startTime, 3),
            'errors' => $errorCount,
        ];
    }
}
