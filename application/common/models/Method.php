<?php
namespace common\models;

use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\Idp\IdBroker\Client\ServiceException;
use common\helpers\Utils;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class Method
 * @package common\models
 * @method Method self::findOne([])
 */
class Method extends MethodBase
{

    const TYPE_EMAIL = 'email';
    const TYPE_PHONE = 'phone';

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
     * Delete all method records that are not verified and verification_expires date is in the past
     * @throws \Exception
     * @throws \Throwable
     */
    public static function deleteExpiredUnverifiedMethods()
    {
        $methods = self::find()->where(['verified' => 0])
                                ->andWhere(['<', 'verification_expires', Utils::getDatetime()])
                                ->all();

        foreach ($methods as $method) {
            try {
                $deleted = $method->delete();
                if ($deleted === 0 || $deleted === false) {
                    throw new \Exception('Expired method delete call failed', 1470324506);
                }
            } catch (\Exception $e) {
                \Yii::error([
                    'action' => 'delete expired unverified methods',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'method_id' => $method->id,
                ]);
            }
        }
    }

    /**
     * Gets all methods for user specified by $employeeId
     * @param string $employeeId
     * @return String[]
     * @throws ServerErrorHttpException
     * @throws ServiceException
     */
    public static function getMethods($employeeId)
    {
        $method = new Method;

        try {
            return $method->idBrokerClient->listMethod($employeeId);
        } catch (ServiceException $e) {
            if ($e->httpStatusCode === 400) {
                throw new ServerErrorHttpException(\Yii::t('app', 'Error locating personnel record'), 1542752270);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Gets all verified methods for user specified by $employeeId
     * @param string $employeeId
     * @return array[]
     * @throws ServiceException
     * @throws ServerErrorHttpException
     */
    public static function getVerifiedMethods($employeeId)
    {
        $methods = self::getMethods($employeeId);

        $verifiedMethods = [];

        if (is_iterable($methods)) {
            foreach ($methods as $method) {
                if ($method['verified'] ?? false) {
                    $verifiedMethods[] = $method;
                }
            }
        }

        return $verifiedMethods;
    }

    /**
     * Gets a specific verified method for user specified by $employeeId
     * @param string $uid
     * @param string $employeeId
     * @return null|String[]
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public static function getOneVerifiedMethod($uid, $employeeId)
    {
        $method = new Method;
        try {
            return $method->idBrokerClient->getMethod($uid, $employeeId);
        } catch (ServiceException $e) {
            if ($e->httpStatusCode === 404) {
                throw new NotFoundHttpException(
                    \Yii::t('app', 'Method not found'),
                    1462989221
                );
            } else {
                throw new \Exception($e->getMessage());
            }
        }
    }
}
