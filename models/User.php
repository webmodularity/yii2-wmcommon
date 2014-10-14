<?php

namespace wmc\models;

use Yii;
use yii\web\IdentityInterface;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "user".
 *
 * @property integer $person_id
 * @property string $username
 * @property string $password
 * @property integer $role_id
 * @property string $auth_key
 * @property integer $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property UserRole $role
 * @property Person $person
 * @property UserKey[] $userKeys
 */
class User extends \wmc\db\ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = -1;
    const STATUS_ACTIVE = 1;
    const STATUS_NEW = 0;

    const ROLE_USER = 1;
    const ROLE_SUPERADMIN = 255;

    public function behaviors() {
        return [
            TimestampBehavior::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['role_id', 'status', 'person_id'], 'integer'],
            [['status'], 'in', 'range' => range(self::STATUS_DELETED, self::STATUS_ACTIVE)],
            [['role_id'], 'in', 'range' => range(self::ROLE_USER, self::ROLE_SUPERADMIN)],
            [['person_id', 'role_id', 'status', 'created_at', 'auth_key'], 'required'],
            [['password'], 'string', 'max' => 255],
            [['username'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'person_id' => 'ID',
            'username' => 'Username',
            'password' => 'Password',
            'role_id' => 'Role ID',
            'auth_key' => 'Auth Key',
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
        return $this->hasOne(\wmc\models\Person::className(), ['id' => 'person_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserKeys()
    {
        return $this->hasMany(UserKey::className(), ['user_id' => 'person_id']);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['person_id' => $id, 'status' => self::STATUS_ACTIVE]);
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
        return static::find()->where(['username' => $username, 'status' => self::STATUS_ACTIVE])->joinWith('person')->one();
    }
    /**
     * Finds user by email
     *
     * @param string $emai;
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::find()->where(['email' => $email, 'status' => self::STATUS_ACTIVE])->joinWith('person')->one();
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
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
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