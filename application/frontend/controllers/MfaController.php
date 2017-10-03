<?php

namespace frontend\controllers;

use frontend\components\BaseRestController;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;

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

    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub
        $config = \Yii::$app->params['mfa'];
        $this->idBrokerClient = new IdBrokerClient($config['idBrokerBaseUrl'], $config['accessToken'], $config);
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

        try {
            return $this->idBrokerClient->mfaCreate(\Yii::$app->user->identity->employee_id, $type);
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
            $this->idBrokerClient->mfaVerify($mfaId, \Yii::$app->user->identity->employee_id, $value);
            \Yii::$app->response->statusCode = 204;
            return;
        } catch (\Exception $e) {
            throw new BadRequestHttpException();
        }
    }

}