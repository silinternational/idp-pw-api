<?php

namespace frontend\controllers;

use frontend\components\BaseRestController;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

class MfaController extends BaseRestController
{

    /**
     * @var IdBrokerClient
     */
    public $idBrokerClient;

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ]
            ]
        ]);
    }

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

    public function actionIndex()
    {
        try {
            return $this->idBrokerClient->mfaList(\Yii::$app->user->identity->employee_id);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function actionCreate()
    {
        $type = \Yii::$app->request->getBodyParam('type');
        if ($type === null) {
            throw new BadRequestHttpException('Type is required');
        }

        $label = \Yii::$app->request->getBodyParam('label');

        try {
            return $this->idBrokerClient->mfaCreate(\Yii::$app->user->identity->employee_id, $type, $label);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function actionDelete($mfaId)
    {
        try {
            return $this->idBrokerClient->mfaDelete($mfaId, \Yii::$app->user->identity->employee_id);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function actionVerify($mfaId)
    {
        $value = \Yii::$app->request->getBodyParam('value');
        if ($value === null) {
            throw new BadRequestHttpException('Value is required');
        }

        try {
            if ($this->idBrokerClient->mfaVerify($mfaId, \Yii::$app->user->identity->employee_id, $value)) {
                \Yii::$app->response->statusCode = 204;
                return;
            }
        } catch (\Exception $e) {
            \Yii::error([
                'action' => 'verify mfa',
                'status' => 'error',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'user' => \Yii::$app->user->identity->email,
            ]);
            throw new ServerErrorHttpException('Unable to verify MFA code, error code: ' . $e->getCode());
        }

        throw new BadRequestHttpException('Invalid code provided');
    }

}