<?php

namespace wmc\models\user;

use Yii;
use yii\web\IdentityInterface;
use wmc\behaviors\TimestampBehavior;
use wmc\models\Person;
use yii\helpers\VarDumper;
use himiklab\yii2\recaptcha\ReCaptchaValidator;
use wmc\behaviors\RelatedModelBehavior;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property integer $id
 * @property integer $person_id
 * @property string $email
 * @property string $username
 * @property string $password
 * @property integer $group_id
 * @property integer $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Person $person
 * @property UserGroup $group
 * @property UserKey[] $userKeys
 * @property UserLog[] $userLogs
 */
class User extends \wmc\db\ActiveRecord implements IdentityInterface
{
    public $captcha;
    public $email_confirm;
    public $password_confirm;
    public $old_password;

    const STATUS_DELETED = -1;
    const STATUS_ACTIVE = 1;
    const STATUS_NEW = 0;

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className()
            ],
            'relatedModel' =>
                [
                    'class' => RelatedModelBehavior::className(),
                    'relations' => [
                        'person' => [
                            'class' => Person::className(),
                            'identifying' => true
                        ]
                    ]
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
            [['group_id', 'status', 'person_id'], 'integer'],
            [['username', 'email'], 'trim'],
            [['email'], 'email'],
            [['email', 'email_confirm'], 'string', 'max' => 255],
            [['email'], 'unique', 'except' => ['forgotPassword']],
            [['username'], 'default', 'value' => null],
            [['status'], 'filter', 'filter' => 'intval'],
            [['status'], 'default', 'value' => static::STATUS_NEW],
            [['status'], 'in', 'range' => range(self::STATUS_DELETED, self::STATUS_ACTIVE)],
            [['group_id'], 'filter', 'filter' => 'intval'],
            [['group_id'], 'default', 'value' => UserGroup::USER],
            [['group_id'], 'in', 'range' => range(UserGroup::USER, UserGroup::SU)],
            [['group_id', 'status', 'password', 'email'], 'required'],
            [['username'], 'string', 'length' => [3, 50]],
            [['username'], 'required', 'on' => ['registerUsername']],
            [['username'], 'match', 'pattern' => '/^[A-Za-z0-9_]+$/u',
                'message' => "{attribute} can contain only letters, numbers or underscores."],
            [['username'], 'unique', 'message' => 'This username is already in use.'],
            [['password', 'password_confirm', 'old_password'], 'string', 'length' => [5, 255]],
            [['old_password'], 'required', 'on' => ['changePassword', 'changeEmail']],
            [['password_confirm'], 'required', 'on' => ['registerEmail', 'registerEmailConfirm', 'registerUsername', 'resetPassword', 'changePassword']],
            [['password_confirm'], 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.',
                'on' => ['registerEmail', 'registerEmailConfirm', 'registerUsername', 'resetPassword', 'changePassword']],
            [['email_confirm'], 'required', 'on' => ['registerEmailConfirm', 'changeEmail']],
            [['email_confirm'], 'compare', 'compareAttribute' => 'email', 'message' => 'Email Addresses do not match.',
                'on' => ['registerEmailConfirm', 'changeEmail']],
            [['captcha'], ReCaptchaValidator::className(), 'on' => ['registerEmail', 'registerEmailConfirm', 'registerUsername']],
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
            'group_id' => 'Group',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'password_confirm' => 'Confirm Password',
            'old_password' => 'Current Password',
            'email_confirm' => 'Confirm Email',
            'captcha' => 'ReCaptcha'
        ];
    }

    /**
     * @inheritdoc
     * @return UserQuery the active query used by this AR class.
     */
    public static function find()
    {
        $userQuery = new UserQuery(get_called_class());
        return $userQuery->joinWith(['person','group']);
    }


    public function beforeDelete() {
        if (parent::beforeDelete()) {
            if ($this->isCurrentUserId()) {
                // Prevent removing own record
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            if ($insert !== true && $this->isCurrentUserId() && !empty($this->getDirtyAttributes(['group_id', 'status']))) {
                // Prevent modifying own record group_id and status fields
                Yii::error("User attempting to modify group_id or status of own record! " . var_dump($this->getDirtyAttributes(['group_id', 'status'])) . "");
                return false;
            } else if ($insert === true) {
                $this->setPassword($this->password);
                $this->person_id = $this->person->id;
                return true;
            }
            return true;
        } else {
            return false;
        }
    }

    public function afterSave($insert, $changedAttributes) {
        if ($insert === true) {
            $this->generateAuthKey();
            UserLog::add(UserLog::ACTION_CREATE, UserLog::RESULT_SUCCESS, $this->id);
        } else {
            $filteredChangedAttributes = array_diff($changedAttributes, ['updated_at', 'password']);
            if (!empty($filteredChangedAttributes)) {
                UserLog::add(UserLog::ACTION_UPDATE, UserLog::RESULT_SUCCESS, $this->id, 'User Model: ' . VarDumper::dumpAsString($filteredChangedAttributes));
            }
            if (in_array('status', array_keys($changedAttributes)) && $changedAttributes['status'] == static::STATUS_NEW && $this->status == static::STATUS_ACTIVE) {
                UserLog::add(UserLog::ACTION_EMAIL, UserLog::RESULT_SUCCESS, $this->id, "Sent user activation email to ".$this->email.".");
                // Send user email
                Yii::$app->mailer->compose('@wma/mail/user-active', ['user' => $this])
                    ->setFrom(Yii::$app->params['noReplyEmail'])
                    ->setTo($this->email)
                    ->setSubject(Yii::$app->params['siteName'] . ' User Account Activated')
                    ->send();
            }
        }

        parent::afterSave($insert, $changedAttributes);
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
    public function getGroup()
    {
        return $this->hasOne(UserGroup::className(), ['id' => 'group_id']);
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
    public function getAuthUserKey() {
        return $this->hasOne(UserKey::className(), ['user_id' => 'id'])->where(['type' => UserKey::TYPE_AUTH]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getResetPasswordUserKey() {
        return $this->hasOne(UserKey::className(), ['user_id' => 'id'])->where(['type' => UserKey::TYPE_RESET_PASSWORD]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChangeEmailUserKey() {
        return $this->hasOne(UserKey::className(), ['user_id' => 'id'])->where(['type' => UserKey::TYPE_CHANGE_EMAIL]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserLogs() {
        return $this->hasMany(UserLog::className(), ['user_id' => 'id']);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::find()->andWhere([static::tableName() . '.id' => $id])->active()->one();
    }

    /**
     * @param string $key
     * @return static|null
     */
    public static function findByResetPasswordKey($key) {
        return static::find()->andWhere(['user_key' => $key])->joinWith('resetPasswordUserKey')->active()->one();
    }

    /**
     * @param string $key
     * @return static|null
     */
    public static function findByChangeEmailKey($key) {
        return static::find()->andWhere(['user_key' => $key])->joinWith('changeEmailUserKey')->active()->one();
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }
    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password) {
        return Yii::$app->security->validatePassword($password, $this->password);
    }
    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }
    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->getPrimaryKey();
    }
    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        return $this->getAuthUserKey()->one()->user_key;
    }
    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        return $this->getAuthKey() === $authKey;
    }
    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey() {
        $userKey = new UserKey([
            'user_id' => $this->id,
            'type' => UserKey::TYPE_AUTH,
            'user_key' => UserKey::generateKey()
        ]);
        if (!$userKey->save()) {
            Yii::error("Failed to generated User Auth Key! " . VarDumper::dumpAsString($userKey->getErrors()), 'user');
            return false;
        } else {
            return true;
        }
    }

    public function getUserIdentifier() {
        return !empty($this->username) ? $this->username : $this->email;
    }

    public static function getUserStatusList() {
        return [
            static::STATUS_ACTIVE => 'Active',
            static::STATUS_NEW => 'Pending',
            static::STATUS_DELETED => 'Deleted'
        ];
    }

    protected function isCurrentUserId() {
        $userId = !Yii::$app->user->isGuest ? Yii::$app->user->id : false;
        return $this->id == $userId;
    }

}