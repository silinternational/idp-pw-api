<?php

namespace common\components\passwordStore;

class UserPasswordMeta
{
    /** @var string */
    public $passwordExpireDate;

    /** @var string */
    public $passwordLastChangeDate;

    /**
     * @param string $passwordExpireDate
     * @param string $passwordLastChangeDate
     * @return UserPasswordMeta
     */
    public static function create($passwordExpireDate, $passwordLastChangeDate)
    {
        $userPasswordMeta = new UserPasswordMeta();
        $userPasswordMeta->passwordExpireDate = $passwordExpireDate;
        $userPasswordMeta->passwordLastChangeDate = $passwordLastChangeDate;

        return $userPasswordMeta;
    }
}
