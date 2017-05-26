<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=rate_stat',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
] + (YII_DEBUG ? [] : [
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
]);
