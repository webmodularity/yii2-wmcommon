<?php

namespace wmc\swiftmailer;

use wmc\helpers\ArrayHelper;

class Mailer extends \yii\swiftmailer\Mailer
{
    public $messageClass = 'wmc\swiftmailer\Message';
    public $viewPath = '@wma/mail';

    public function compose($view = null, array $params = []) {
        if (isset($params['title'])) {
            $this->messageConfig = ArrayHelper::merge(
                $this->messageConfig,
                [
                    'title' => ArrayHelper::remove($params, 'title')
                ]
            );
        }

        return parent::compose($view, $params);
    }
}