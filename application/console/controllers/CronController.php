<?php
namespace console\controllers;

use common\helpers\Utils;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use common\models\Method;
use common\models\Reset;
use yii\console\Controller;

class CronController extends Controller
{
    /**
     * @return IdBrokerClient
     * @throws \Exception
     */
    protected function getIdBrokerClient()
    {
        $config = \Yii::$app->params['idBrokerConfig'];
        return new IdBrokerClient(
            $config['baseUrl'],
            $config['accessToken'],
            [
                IdBrokerClient::TRUSTED_IPS_CONFIG              => $config['validIpRanges']       ?? [],
                IdBrokerClient::ASSERT_VALID_BROKER_IP_CONFIG   => $config['assertValidBrokerIp']   ?? true,
            ]
        );
    }

    /**
     * Move data from local Method table to id-broker Method table
     */
    public function actionMoveMethodData()
    {
        $startTime = microtime(true);
        echo 'starting cron/move-method-data' . PHP_EOL;

        try {
            Method::deleteExpiredUnverifiedMethods();
        } catch (\Exception $e) {
            echo 'error deleting expired records' . PHP_EOL;
            \Yii::error([
                'action' => 'cron/moveMethodData',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return;
        }

        $methods = Method::find()
            ->where(['verified' => 1, 'type' => Method::TYPE_EMAIL, 'deleted_at' => null])
            ->limit(100)
            ->all();

        if (empty($methods)) {
            echo 'no remaining email method records' . PHP_EOL;

            return;
        }

        echo 'moving ' . count($methods) . ' records' . PHP_EOL;
        $n = count($methods);

        $idBroker = self::getIdBrokerClient();

        foreach ($methods as $method) {
            try {
                $brokerMethod = $idBroker->createMethod(
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
                echo 'error moving method, uid=' . $method->uid . PHP_EOL;
                \Yii::error([
                    'action' => 'cron/moveMethodData',
                    'error' => $e->getMessage(),
                    'method_id' => $method->uid,
                    'code' => $e->getCode(),
                ]);
            }
        }

        $endTime = microtime(true);
        $msecPerRecord = ($endTime - $startTime) / $n * 1000;

        echo (string)round($endTime - $startTime, 3) . ' sec, '
            . (string)round($msecPerRecord, 0) . ' ms per record' . PHP_EOL;
        echo 'finished cron/move-method-data' . PHP_EOL;
    }
}
