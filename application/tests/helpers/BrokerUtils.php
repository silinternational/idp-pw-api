<?php
namespace tests\helpers;

use Sil\Idp\IdBroker\Client\IdBrokerClient;

class BrokerUtils
{

    public static function insertFakeUsers()
    {
        $data = include __DIR__ . '/BrokerFakeData.php';

        $baseUrl = \Yii::$app->params['idBrokerConfig']['baseUrl'];
        $accessToken = \Yii::$app->params['idBrokerConfig']['accessToken'];
        $idBrokerClient = new IdBrokerClient($baseUrl, $accessToken, [
            IdBrokerClient::ASSERT_VALID_BROKER_IP_CONFIG => false,
        ]);

        $userExistsCode = 1490802526;

        foreach ($data as $userInfo) {
            try {
                $idBrokerClient->createUser($userInfo);
            } catch (\Exception $e) {
                if ($e->getCode() == $userExistsCode) {
                    $idBrokerClient->updateUser($userInfo);
                } else {
                    throw $e;
                }
            }
        }
    }

    public static function insertFakeMethods()
    {
        $data = include __DIR__ . '/BrokerFakeMethods.php';

        $baseUrl = \Yii::$app->params['idBrokerConfig']['baseUrl'];
        $accessToken = \Yii::$app->params['idBrokerConfig']['accessToken'];
        $idBrokerClient = new IdBrokerClient($baseUrl, $accessToken, [
            IdBrokerClient::ASSERT_VALID_BROKER_IP_CONFIG => false,
        ]);

        foreach ($data as $methodInfo) {
            $idBrokerClient->createMethod($methodInfo['employee_id'], $methodInfo['value']);
        }
    }
}
