<?php
$CONFIG = [
    "BASEDIR" => "/var/www/retro",
    "LOGIN_PATH" => 'login',
    "DEV" => false,

    "DB" => [
        "DBNAME" => "retro",
        "DBUSER" => "root",
        "DBPASS" => ""
    ],
    "USER" => [
        "NAME" => "supersecretloginname",
        "PASS" => "asdf",
        "KEY" => "123-123-123" // Key used for API
    ],
    "MEMCACHED" => [
        "SERVER" => "127.0.0.1",
        "PORT" => 11211
    ]
];