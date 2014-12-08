<?php

namespace Zelenin\yii\widgets\Recaptcha\validators;

use Yii;
use yii\validators\Validator;

class RecaptchaValidator extends Validator
{
    /** @var string */
    public $secret;
    /** @inheritdoc */
    public $skipOnEmpty = false;
    /** @var string */
    private $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    /** @var string */
    public $emptyMessage = 'The captcha is empty.';
    /** @var string */
    public $incorrectMessage = 'The captcha is incorrect.';

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $request = Yii::$app->getRequest();
        $response = $request->post('g-recaptcha-response');

        if (!$response) {
            $model->addError($attribute, $this->emptyMessage);
        } else {
            if (!$this->verifyResponse($request->getUserIP(), $response)) {
                $model->addError($attribute, $this->incorrectMessage);
            }
        }
    }

    /**
     * @param string $userIp
     * @param string $response
     * @return bool
     */
    private function verifyResponse($userIp, $response)
    {
        $response = $this->request($this->verifyUrl, [
            'secret' => $this->secret,
            'remoteip' => $userIp,
            'response' => $response
        ]);
        $response = json_decode($response);
        return $response->success == true;
    }

    /**
     * @param string $url
     * @param array $params
     * @return string
     */
    private function request($url, $params)
    {
        return file_get_contents($url . '?' . http_build_query($params));
    }

    /**
     * @inheritdoc
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {
        return parent::clientValidateAttribute($model, $attribute, $view);
    }
}
