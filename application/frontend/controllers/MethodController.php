<?php
namespace frontend\controllers;

use common\exception\InvalidCodeException;
use common\models\Method;
use common\models\Reset;
use common\models\User;
use frontend\components\BaseRestController;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
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
                        'matchCallback' => function() {
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
     * @return array
     */
    public function getVerifiedMethods()
    {
        return $this->idBrokerClient->listMethod($employeeId);
    }

    /**
     * Return list of available reset methods for user.
     * @return array
     */
    public function actionIndex()
    {
        /** @var User $user */
        $user = \Yii::$app->user->identity;

        return $user->getVerifiedMethodsAndPersonnelEmails();
    }

    /**
     * View single method
     * @param string $uid
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionView($uid)
    {
        $employeeId = \Yii::$app->user->identity->employee_id;

        $method = $this->idBrokerClient->getMethod($uid, $employeeId);
        $method['type'] = 'email';
        return $method;
    }

    /**
     * Create new unverified method and send verification
     * @return array
     * @throws BadRequestHttpException
     * @throws \Exception
     */
    public function actionCreate()
    {
        $request = \Yii::$app->request;

        $value = $request->post('value');
        if ($value === null) {
            throw new BadRequestHttpException(\Yii::t('app', 'Value is required'));
        }

        $employeeId = \Yii::$app->user->identity->employee_id;

        $method = $this->idBrokerClient->createMethod($employeeId, $value);
        $method['type'] = 'email';
        return $method;
    }

    /**
     * Validates user submitted code and marks method as verified if valid
     * @param string $uid
     * @return array
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws TooManyRequestsHttpException
     * @throws \Exception
     */
    public function actionUpdate($uid)
    {
        $code = \Yii::$app->request->getBodyParam('code');
        if ($code === null) {
            throw new BadRequestHttpException(\Yii::t('app', 'Code is required'));
        }

        $employeeId = \Yii::$app->user->identity->employee_id;

        try {
            $method = $this->idBrokerClient->verifyMethod($uid, $employeeId, $code);
        } catch (BadRequestHttpException $e) {
            throw $e;
        }
        $method['type'] = 'email';
        return $method;
    }

    /**
     * Delete method
     * @param string $uid
     * @return array
     */
    public function actionDelete($uid)
    {
        $employeeId = \Yii::$app->user->identity->employee_id;

        $this->idBrokerClient->deleteMethod($uid, $employeeId);

        return [];
    }

    /**
     * @param string $uid
     * @return \stdClass
     */
    public function actionResend($uid)
    {
        $employeeId = \Yii::$app->user->identity->employee_id;

        $this->idBrokerClient->resendMethod($uid, $employeeId);

        /*
         * Return empty object
         */
        return new \stdClass();
    }

}
