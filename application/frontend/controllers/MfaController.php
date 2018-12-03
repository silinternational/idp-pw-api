<?php

namespace frontend\controllers;

use frontend\components\BaseRestController;
use Sil\Idp\IdBroker\Client\exceptions\MfaRateLimitException;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\Idp\IdBroker\Client\ServiceException;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\TooManyRequestsHttpException;

class MfaController extends BaseRestController
{

    /**
     * @var IdBrokerClient
     */
    public $idBrokerClient;

    /**
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
                        'matchCallback' => function() {
                            $user = \Yii::$app->user->identity;
                            return $user->isAuthScopeFull();
                        }
                    ],
                ]
            ]
        ]);
    }

    /**
     * @throws \Exception
     */
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
     * @return array
     * @throws \Exception
     */
    public function actionIndex()
    {
        try {
            return $this->idBrokerClient->mfaList(\Yii::$app->user->identity->employee_id);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return array|null
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public function actionCreate()
    {
        $type = \Yii::$app->request->getBodyParam('type');
        if ($type === null) {
            throw new BadRequestHttpException(\Yii::t('app', 'Type is required'));
        }

        $label = \Yii::$app->request->getBodyParam('label');

        try {
            return $this->idBrokerClient->mfaCreate(\Yii::$app->user->identity->employee_id, $type, $label);
        } catch (\Exception $e) {
            \Yii::error([
                'status' => 'MFA create error',
                'message' => $e->getMessage(),
            ], __METHOD__);
            if ($e instanceof ServiceException && $e->httpStatusCode == 400) {
                throw new ServerErrorHttpException(\Yii::t('app', 'Error creating MFA'), 1543860506);
            }
        }
    }

    /**
     * @param $mfaId
     * @return null
     * @throws ServerErrorHttpException
     * @throws NotFoundHttpException
     */
    public function actionDelete($mfaId)
    {
        try {
            return $this->idBrokerClient->mfaDelete($mfaId, \Yii::$app->user->identity->employee_id);
        } catch (ServiceException $e) {
            \Yii::error([
                'status' => 'MFA delete error',
                'message' => $e->getMessage(),
            ], __METHOD__);
            if ($e instanceof ServiceException) {
                if ($e->httpStatusCode == 400) {
                    throw new ServerErrorHttpException(\Yii::t('app', 'Error deleting MFA'), 1543861308);
                } elseif ($e->httpStatusCode == 404) {
                    throw new NotFoundHttpException(\Yii::t('app', 'Error deleting MFA'), 1543861309);
                }
            }
        }
    }

    /**
     * @param $mfaId
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws TooManyRequestsHttpException
     */
    public function actionVerify($mfaId)
    {
        $value = \Yii::$app->request->getBodyParam('value');
        if ($value === null) {
            throw new BadRequestHttpException(\Yii::t('app', 'Value is required'));
        }

        try {
            if ($this->idBrokerClient->mfaVerify($mfaId, \Yii::$app->user->identity->employee_id, $value)) {
                \Yii::$app->response->statusCode = 204;
                return;
            }
        } catch (\Exception $e) {
            \Yii::error([
                'status' => 'MFA verify error',
                'message' => $e->getMessage(),
            ], __METHOD__);
            if ($e instanceof ServiceException && $e->httpStatusCode == 404) {
                throw new NotFoundHttpException('MFA verify failure', $e->getCode());
            } elseif ($e instanceof MfaRateLimitException) {
                throw new TooManyRequestsHttpException('MFA rate limit failure', $e->getCode());
            }
            throw new ServerErrorHttpException('Unable to verify MFA code, error code: ' . $e->getCode());
        }

        throw new BadRequestHttpException(\Yii::t('app', 'Invalid code provided'));
    }

    public function actionUpdate($mfaId)
    {
        $label = \Yii::$app->request->getBodyParam('label');
        if ($label === null) {
            return;
        }

        try {
            $this->idBrokerClient->mfaUpdate($mfaId, \Yii::$app->user->identity->employee_id, $label);
        } catch (\Exception $e) {
            \Yii::error([
                'status' => 'MFA update error',
                'message' => $e->getMessage(),
            ], __METHOD__);
            if ($e instanceof ServiceException && $e->httpStatusCode == 404) {
                throw new NotFoundHttpException(\Yii::t('app', 'MFA update failure'), $e->getCode());
            }
            throw new ServerErrorHttpException('Error updating MFA code', $e->getCode());
        }
    }
}
