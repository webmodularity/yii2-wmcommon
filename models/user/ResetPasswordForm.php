<?php

namespace wmc\models\user;

use yii\base\InvalidParamException;
use Yii;

/**
 * Password reset form
 */
class ResetPasswordForm extends \yii\base\Model
{
    public $password;
    public $password_confirm;
    /**
     * @var \wmu\models\User
     */
    private $_user;
    /**
     * Creates a form model given a token.
     *
     * @param string $key 32 character user key
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @throws \yii\base\InvalidParamException if token is empty or not valid
     */
    public function __construct($key, $config = []) {
        if (empty($key) || !UserKey::isValidKey($key)) {
            throw new InvalidParamException('Password reset key cannot be blank.');
        }
        $this->_user = User::findByResetPasswordKey($key);
        if (!$this->_user) {
            throw new InvalidParamException('Wrong password reset key.');
        }
        parent::__construct($config);
    }
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['password', 'password_confirm'], 'required'],
            [['password', 'password_confirm'], 'string', 'length' => [5, 255]],
            [['password_confirm'], 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.']
        ];
    }
    /**
     * Resets password.
     *
     * @return boolean if password was reset.
     */
    public function resetPassword() {
        $this->_user->setPassword($this->password);
        UserLog::add(UserLog::ACTION_RESET_PASSWORD, UserLog::RESULT_SUCCESS, $this->_user->id);
        $this->_user->resetPasswordUserKey->delete();
        return $this->_user->save();
    }

    public function getUser() {
        return $this->_user;
    }
}
