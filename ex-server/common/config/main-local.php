<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=rm-eau7zg3j.mysql.rds.aliyuncs.com;dbname=jinglanex9_tw',
            'username' => 'master_dba',
            'password' => 'wu3OS5xPBGoB2QDUy4Cw',
            'charset' => 'utf8',
            'tablePrefix' => 'jl_',
        ],
        /**
        'cache' => [
            // redis缓存
            'class' => 'yii\redis\Cache',
        ],
        // session写入缓存配置
        'session' => [
            'class' => 'yii\redis\Session',
            'redis' => [
                'class' => 'yii\redis\Connection',
                'hostname' => 'localhost',
                'port' => 6379,
                'database' => 0,
            ],
        ],
         */
//        'mailer' => [
//            'class' => 'yii\swiftmailer\Mailer',
//            'viewPath' => '@common/mail',
//            // send all mails to a file by default. You have to set
//            // 'useFileTransport' to false and configure a transport
//            // for the mailer to send real emails.
//            'useFileTransport' => true,
//        ],
    ],
];
