<?php
namespace tests\helpers;

use yii\helpers\FileHelper;

class EmailUtils
{
    /**
     * @param string $uniqueContent
     * @return bool
     */
    public static function hasEmailFileBeenCreated($uniqueContent)
    {
        $path = self::getEmailFilesPath();
        if ($path) {
            $files = FileHelper::findFiles($path);
            foreach ($files as $file) {
                $contents = file_get_contents($file);
                $contents = quoted_printable_decode($contents);
                if (substr_count($contents, $uniqueContent) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function removeEmailFiles()
    {
        $path = self::getEmailFilesPath();
        if ($path) {
            $files = FileHelper::findFiles($path);
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    public static function getEmailFilesPath()
    {
        return \Yii::getAlias('@runtime/mail');
    }

    public static function getEmailFilesCount()
    {
        $path = self::getEmailFilesPath();
        if ($path) {
            $files = FileHelper::findFiles($path);
            return count($files);
        }

        return 0;
    }
}