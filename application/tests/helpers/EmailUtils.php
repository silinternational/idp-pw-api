<?php
namespace tests\helpers;

class EmailUtils
{
    /**
     * @param \Codeception\Module\Yii2 $tester
     * @param string $uniqueContent
     * @return bool
     * @throws
     */
    public static function hasEmailFileBeenCreated($uniqueContent)
    {
        $fakeEmailsSent = \Yii::$app->emailer->getFakeEmailsSent();

        foreach ($fakeEmailsSent as $fakeEmail) {
            if (substr_count(implode(' ', $fakeEmail), $uniqueContent) > 0) {
                return true;
            }
        }

        return false;
    }

    public static function getEmailFilesCount()
    {
        return count(\Yii::$app->emailer->getFakeEmailsSent());
    }
}
