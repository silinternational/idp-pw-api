<?php

namespace frontend\controllers;

use frontend\components\BaseRestController;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\Idp\IdBroker\Client\ServiceException;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

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
                        'matchCallback' => function () {
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
     * @return array
     * @throws ServiceException
     */
    public function actionIndex()
    {
        return $this->idBrokerClient->mfaList(\Yii::$app->user->identity->employee_id);
    }

    /**
     * @return array|null
     * @throws BadRequestHttpException
     * @throws HttpException
     */
    public function actionCreate()
    {
        $messages = [
            409 => \Yii::t('app', 'This 2SV already exists'),
        ];

        $type = \Yii::$app->request->getBodyParam('type');
        if ($type === null) {
            throw new BadRequestHttpException(\Yii::t('app', 'Type is required'));
        }

        $label = \Yii::$app->request->getBodyParam('label');

        try {
            $mfa = $this->idBrokerClient->mfaCreate(\Yii::$app->user->identity->employee_id, $type, $label);
        } catch (ServiceException $e) {
            \Yii::error([
                'status' => 'MFA create error',
                'error' => $e->getMessage(),
                'httpStatusCode' => $e->httpStatusCode,
            ], __METHOD__);

            throw new HttpException(
                $e->httpStatusCode,
                $messages[$e->httpStatusCode] ?? 'Unexpected Problem',
                1551192684
            );
        }

        return $mfa;
    }

    /**
     * @param $mfaId
     * @return null
     * @throws ServiceException
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
            if ($e->httpStatusCode == 404) {
                throw new NotFoundHttpException(\Yii::t('app', 'MFA record not found'));
            }

            /*
             * Other status codes will result in a 500 response
             */
            throw $e;
        }
    }

    /**
     * @param $mfaId
     * @return array|bool
     * @throws HttpException
     */
    public function actionVerify($mfaId)
    {
        $messages = [
            400 => \Yii::t('app', 'Invalid code provided'),
            404 => \Yii::t('app', 'MFA verify failure'),
            429 => \Yii::t('app', 'MFA rate limit failure'),
        ];

        $value = \Yii::$app->request->getBodyParam('value');
        if ($value === null) {
            throw new BadRequestHttpException(\Yii::t('app', 'Value is required'));
        }

        try {
            $mfa = $this->idBrokerClient->mfaVerify($mfaId, \Yii::$app->user->identity->employee_id, $value);
        } catch (ServiceException $e) {
            \Yii::error([
                'status' => 'MFA verify error',
                'error' => $e->getMessage(),
                'httpStatusCode' => $e->httpStatusCode,
            ], __METHOD__);

            throw new HttpException(
                $e->httpStatusCode,
                $messages[$e->httpStatusCode] ?? '',
                1551109134
            );
        }

        return $mfa;
    }

    /**
     * @param $mfaId
     * @throws NotFoundHttpException
     * @throws ServiceException
     */
    public function actionUpdate($mfaId)
    {
        $label = \Yii::$app->request->getBodyParam('label');
        if ($label === null) {
            \Yii::$app->response->statusCode = 204;
            return;
        }

        try {
            return $this->idBrokerClient->mfaUpdate($mfaId, \Yii::$app->user->identity->employee_id, $label);
        } catch (ServiceException $e) {
            \Yii::error([
                'status' => 'MFA update error',
                'message' => $e->getMessage(),
            ], __METHOD__);
            if ($e->httpStatusCode == 404) {
                throw new NotFoundHttpException(\Yii::t('app', 'MFA update failure'), $e->getCode());
            }

            /*
             * Other status codes will result in a 500 response
             */
            throw $e;
        }
    }
}
