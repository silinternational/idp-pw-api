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
    public static function hasEmailFileBeenCreated($tester, $uniqueContent)
    {
        try {
            /** @var \yii\mail\MessageInterface[] $messages */
            $messages = $tester->grabSentEmails();
        } catch (\Exception $e) {
            throw $e;
        }

        /** @var \yii\mail\MessageInterface $message */
        foreach ($messages as $message) {
            $contents = quoted_printable_decode($message->toString());
            if (substr_count($contents, $uniqueContent) > 0) {
                return true;
            }
        }

        return false;
    }

    public static function getEmailFilesCount($tester)
    {
        return count($tester->grabSentEmails());
    }
}