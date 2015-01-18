<?php

namespace wmc\modules\user\models;

use Yii;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property integer $id
 * @property integer $person_id
 * @property string $username
 * @property string $password
 * @property integer $role_id
 * @property integer $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property UserRole $role
 * @property Person $person
 * @property UserKey[] $userKeys
 * @property UserLog[] $userLogs
 */
class User extends \wmc\db\ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = -1;
    const STATUS_ACTIVE = 1;
    const STATUS_NEW = 0;

    const ROLE_USER = 1;
    const ROLE_ADMIN = 100;
    const ROLE_SUPERADMIN = 255;

    public $password_confirm;

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'value' => new \yii\db\Expression('NOW()')
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['role_id', 'status', 'person_id'], 'integer', 'except' => ['register', 'register-username']],
            [['username', 'password','password_confirm'], 'trim'],
            [['username'], 'default', 'value' => null, 'except' => ['register-username']],
            [['status'], 'in', 'range' => range(self::STATUS_DELETED, self::STATUS_ACTIVE), 'except' => ['register', 'register-username']],
            [['role_id'], 'in', 'range' => range(self::ROLE_USER, self::ROLE_SUPERADMIN), 'except' => ['register', 'register-username']],
            [['role_id', 'status', 'person_id', 'password'], 'required', 'except' => ['register', 'register-username']],
            [['password', 'password_confirm'], 'required', 'on' => ['register', 'register-username']],
            [['password', 'password_confirm'], 'string', 'length' => [5, 100], 'on' => ['register', 'register-username']],
            [['username'], 'string', 'length' => [3, 50], 'on' => 'register-username'],
            [['username'], 'unique', 'on' => 'register-username'],
            [['username'], 'required', 'on' => 'register-username'],
            [['username'], 'match', 'pattern' => '/^[A-Za-z0-9_]+$/u', 'message' => "{attribute} can contain only letters, numbers or underscores.", 'on' => ['register-username']],
            [['username'], 'unique', 'targetClass' => '\wmu\models\User', 'message' => 'This username is already in use.', 'on' => ['register-username']],
            [['password_confirm'], 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.', 'on' => ['register', 'register-username']]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'person_id' => 'Person',
            'username' => 'Username',
            'password' => 'Password',
            'password_confirm' => 'Confirm Password',
            'role_id' => 'Role',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(UserRole::className(), ['id' => 'role_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPerson()
    {
        return $this->hasOne(Person::className(), ['id' => 'person_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserKeys()
    {
        return $this->hasMany(UserKey::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserLogs()
    {
        return $this->hasMany(UserLog::className(), ['user_id' => 'id']);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }
    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }
    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }
    /**
     * Finds user by email
     *
     * @param string $emai;
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::find()->where(['person.email' => $email, 'status' => self::STATUS_ACTIVE])->joinWith('person')->one();
    }
    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        throw new NotSupportedException('"findIdentityByPasswordResetToken" is not implemented.');
        /*
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
        if ($timestamp + $expire < time()) {
            // token expired
            return null;
        }
        return static::findOne([
                'password_reset_token' => $token,
                'status' => self::STATUS_ACTIVE,
            ]);
        */
    }
    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }
    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }
    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }
    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }
    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        throw new NotSupportedException('generateAuthKey() is not implemented.');
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        throw new NotSupportedException('"generatePasswordResetToken" is not implemented.');
        /*
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
        */
    }
    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        throw new NotSupportedException('"removePasswordResetToken" is not implemented.');
        /*
        $this->password_reset_token = null;
        */
    }

}