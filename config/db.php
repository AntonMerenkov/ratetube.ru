<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=127.0.0.1;dbname=rate_stat',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
] + (YII_DEBUG ? [] : [
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
]);
