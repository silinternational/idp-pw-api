<?php
namespace frontend\controllers;

use frontend\components\BaseRestController;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class MfaController extends BaseRestController
{
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

    public function actionIndex()
    {
        $config = \Yii::$app->params['mfa'];
        $idBrokerClient = new IdBrokerClient($config['idBrokerBaseUrl'], $config['accessToken']);
        return $idBrokerClient->mfaList(\Yii::$app->user->identity->employee_id);
    }

    public function actionCreate()
    {
        $type = \Yii::$app->request->getBodyParam('type');
        if ($type === null) {
            throw new BadRequestHttpException('Type is required');
        }

        $config = \Yii::$app->params['mfa'];
        $idBrokerClient = new IdBrokerClient($config['idBrokerBaseUrl'], $config['accessToken']);
        return $idBrokerClient->mfaCreate(\Yii::$app->user->identity->employee_id, $type);
    }

    public function actionDelete($mfaId)
    {
        $config = \Yii::$app->params['mfa'];
        $idBrokerClient = new IdBrokerClient($config['idBrokerBaseUrl'], $config['accessToken']);
        $mfaList = $idBrokerClient->mfaList(\Yii::$app->user->identity->employee_id);
        foreach ($mfaList as $mfa) {
            if ($mfa['id'] === $mfaId) {
                return $idBrokerClient->mfaDelete($mfaId);
            }
        }

        throw new NotFoundHttpException();
    }

    public function actionVerify($mfaId)
    {
        $value = \Yii::$app->request->getBodyParam('value');
        if ($value === null) {
            throw new BadRequestHttpException('Value is required');
        }

        $config = \Yii::$app->params['mfa'];
        $idBrokerClient = new IdBrokerClient($config['idBrokerBaseUrl'], $config['accessToken']);
        $mfaList = $idBrokerClient->mfaList(\Yii::$app->user->identity->employee_id);
        foreach ($mfaList as $mfa) {
            if ($mfa['id'] === $mfaId) {
                return $idBrokerClient->mfaVerify($mfaId, $value);
            }
        }

        throw new NotFoundHttpException();
    }

}