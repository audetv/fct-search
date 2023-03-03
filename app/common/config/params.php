<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    'frontendHostInfo' => getenv('FRONTEND_URL'),
    'manticore' => [
        'host' => 'manticore',
        'port' => 9308
    ],
    'auth' => [
        'token_ttl' => 'PT1H',
    ],
    'questions' => [
        'pageSize' => 20,
        'current' => [
            'id' => 8162,
            'file' => '30-qa-question-view-8162.json'
        ],
        'url-pattern' => "https://фкт.рф/qa/question/view-",
    ],
];
