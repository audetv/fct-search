<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'useFileTransport' => false,
            'transport' => [
                'scheme' => 'smtps',
                'host' => getenv('MAILER_HOST'),
                'username' => getenv('MAILER_USER'),
                'password' => trim(file_get_contents(getenv('MAILER_PASSWORD_FILE'))),
                'port' => (int)getenv('MAILER_PORT'),
            ],

        ],
    ],
];
