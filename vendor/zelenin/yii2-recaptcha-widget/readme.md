# Yii2 reCAPTCHA widget

[Yii2](http://www.yiiframework.com) [reCAPTCHA](https://www.google.com/recaptcha/intro/index.html) widget.

## Installation

### Composer

The preferred way to install this extension is through [Composer](http://getcomposer.org/).

Either run

```php composer.phar require zelenin/yii2-recaptcha-widget "*"```

or add

```"zelenin/yii2-recaptcha-widget": "*"```

to the require section of your composer.json

## Usage

[Register](https://www.google.com/recaptcha/admin) a new site.

Add captcha attribute to model:

```php
public $captcha;

public function rules()
{
    return [
        [
            'captcha',
            'Zelenin\yii\widgets\Recaptcha\validators\RecaptchaValidator',
            'secret' => '<your-secret>'
        ]
    ];
}
```

Add field to view:

```php
<?= $form->field($model, 'captcha')->widget('Zelenin\yii\widgets\Recaptcha\widgets\Recaptcha', [
    'clientOptions' => [
        'data-sitekey' => '<your-sitekey>'
    ]
]) ?>
```

## Info
See [reCAPTCHA documentation](https://developers.google.com/recaptcha)

## Author

[Aleksandr Zelenin](https://github.com/zelenin/), e-mail: [aleksandr@zelenin.me](mailto:aleksandr@zelenin.me)
