<?php
namespace tests\helpers;

use Sil\Idp\IdBroker\Client\IdBrokerClient;

class BrokerUtils {

    public static function insertFakeUsers()
    {
        $data = include __DIR__ . '/BrokerFakeData.php';

        $baseUrl = \Yii::$app->personnel->baseUrl;
        $accessToken = \Yii::$app->personnel->accessToken;
        $idBrokerClient = new IdBrokerClient($baseUrl, $accessToken, [
            IdBrokerClient::ASSERT_VALID_BROKER_IP_CONFIG => false,
        ]);

        $userExistsCode = 1490802526;

        foreach($data as $userInfo) {
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
}