<?php
namespace common\models;

use common\helpers\ZxcvbnPasswordValidator;
use common\components\passwordStore\PasswordReuseException;
use GuzzleHttp\Exception\GuzzleException;
use Icawebdesign\Hibp\Password\PwnedPassword;
use yii\base\Model;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ServerErrorHttpException;

class Password extends Model
{
    /** @var string */
    public $password;

    /** @var  string */
    public $employeeId;

    /** @var \common\components\passwordStore\PasswordStoreInterface */
    public $passwordStore;

    /** @var array */
    public $config;

    /** @var common\models\User **/
    public $user;

    public function init()
    {
        $this->passwordStore = \Yii::$app->passwordStore;
        $this->config = \Yii::$app->params['passwordRules'];
    }

    public function rules()
    {
        return [
            [
                'password', 'string', 'min' => $this->config['minLength'],
                'skipOnError' => false,
                'tooShort' => \Yii::t(
                    'app',
                    'Your password does not meet the minimum length of {minLength} (code 100)',
                    ['minLength' => $this->config['minLength']]
                ),
            ],
            [
                'password', 'string', 'max' => $this->config['maxLength'],
                'skipOnError' => false,
                'tooLong' => \Yii::t(
                    'app',
                    'Your password exceeds the maximum length of {maxLength} (code 110)',
                    ['maxLength' => $this->config['maxLength']]
                ),
            ],
            [
                'password', ZxcvbnPasswordValidator::class, 'minScore' => $this->config['minScore'],
                'skipOnError' => true,
                'message' => \Yii::t(
                    'app',
                    'Your password does not meet the minimum strength of {minScore} (code 150)',
                    ['minScore' => $this->config['minScore']]
                ),
            ],
            [
                'password', 'validateNotUserAttributes',
                'params'=>['first_name', 'last_name', 'idp_username', 'email'],
                'skipOnError' => false,
            ],
            [
                'password', 'validateNotBeenPwned',
                'skipOnError' => true,
            ],
            [
                'password', 'validateNotPublicPassword',
                'skipOnError' => false,
            ],
        ];
    }

    public function validateNotUserAttributes($attribute, $params = null)
    {
        /* Ensure the password instance has a user attribute */
        if ( ! isset($this->user)) {

            /* Log error */
            $log = [
                'status' => 'error',
                'error' => 'No User instance has been assigned to the new password. ' .
                    "Cannot validate it against the user's attributes.",
            ];
            \Yii::error($log);

            /* Throw exception based on exception type */
            throw new ServerErrorHttpException(
                \Yii::t(
                    'app',
                    'Unable to update password. Please contact support.'
                ),
                1511195430
            );
        }
        $userAttributeLabels = $this->user->attributeLabels();
        $labelList = [];
        foreach ($params as $disallowedAttribute) {
            $labelList[] = $userAttributeLabels[$disallowedAttribute];
        }

        foreach ($params as $disallowedAttribute) {
            if (mb_strpos(mb_strtolower($this->{$attribute}),
                mb_strtolower($this->user->$disallowedAttribute)) !== false) {
                $this->addError($attribute, \Yii::t(
                    'app',
                    'Your password may not contain any of these: {labelList} (code 180)',
                    ['labelList' => join(', ', $labelList)]
                ));
            }
        }
    }

    /**
     * Shortcut method to initialize a Password object
     * @param string $employeeId
     * @param string $newPassword
     * @return Password
     */
    public static function create($employeeId, $newPassword)
    {
        $password = new Password();
        $password->password = $newPassword;
        $password->employeeId = $employeeId;

        return $password;
    }

    /**
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public function save()
    {

        if ( ! $this->validate()) {
            $errors = join(', ', $this->getErrors('password'));
            \Yii::warning([
                'action' => 'save password',
                'status' => 'error',
                'employee_id' => $this->employeeId,
                'error' => $this->getErrors('password'),
            ]);
            throw new BadRequestHttpException(\Yii::t(
                'app',
                'New password validation failed: {errors}',
                ['errors' => $errors]
            ));
        }
        
        $log = [
            'action' => 'save password',
            'employee_id' => $this->employeeId,
        ];

        /*
         * If validation fails, return just the first error
         */
        if ( ! $this->validate()) {
            $errors = $this->getFirstErrors();
            $log['status'] = 'error';
            $log['error'] = $errors;
            \Yii::error($log);
            throw new BadRequestHttpException($errors[0], 1463164336);
        }

        /*
         * Update password
         */
        try {
            $this->passwordStore->set($this->employeeId, $this->password);
            $log['status'] = 'success';
            \Yii::warning($log);
        } catch (\Exception $e) {
            /*
             * Log error
             */
            $log['status'] = 'error';
            $log['error'] = $e->getMessage();
            $previous = $e->getPrevious();
            if ($previous instanceof \Exception) {
                $log['previous'] = [
                    'code' => $previous->getCode(),
                    'message' => $previous->getMessage(),
                ];
            }

            /*
             * Throw exception based on exception type
             */
            if ($e instanceof  PasswordReuseException) {
                \Yii::warning($log);
                throw new ConflictHttpException(
                    \Yii::t(
                        'app',
                        'Unable to update password. ' .
                            'If this password has been used before please use something different.'
                    ),
                    1469194882
                );
            } else {
                \Yii::error($log);
                throw new ServerErrorHttpException(
                    \Yii::t(
                        'app',
                        'Unable to update password, please wait a minute and try again. If this problem ' .
                            'persists, please contact support.'
                    ), 
                    1463165209
                );
            }

        }
    }

    public function validateNotPublicPassword($attribute)
    {
        /*
         * block passwords provided in https://youtu.be/WTMZYuoztoM?list=PLu5OsENIeX656zXJ96FCL169WNmvnPveo
         */
        $publicPasswords = [
            'one4amzn',
            'one4ggle',
            'one4ebay',
            '$$Ymh7Hp3dfgQr9L#!>s;',
            '7startpenguins!snap',
            'deserty.domes.slide2',
            'about-slithers-quakely.',
        ];

        foreach ($publicPasswords as $publicPassword) {
            if ($this->$attribute == $publicPassword) {
                $this->addError($attribute, \Yii::t(
                    'app',
                    "Ha ha, good one. No, you shouldn't use a password you saw "
                    . 'in a video about creating good passwords.'
                ));
            }
        }
    }

    /**
     * @param string $attribute The name of the attribute being validated, typically
     * 'password'.
     */
    public function validateNotBeenPwned($attribute)
    {
        $hash = sha1($this->$attribute);
        $hashPrefix = substr($hash, 0, 5);

        $pwnedPassword = new PwnedPassword();

        try {
            $count = $pwnedPassword->range($hashPrefix, $hash);
        } catch (GuzzleException $e) {
            \Yii::error('HaveIBeenPwned API error: ' . $e->getMessage());
            return;
        }

        if ($count > 0) {
            $this->addError($attribute, \Yii::t(
                'app',
                'This password is not secure. It has been revealed {count} times in '
                . 'password breaches. Please create a new password.',
                ['count' => $count]
            ));
        }
    }
}
