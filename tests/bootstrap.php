<?php

require __DIR__.'/../vendor/autoload.php';

// The application container bakes DB_DATABASE/APP_ENV into $_SERVER via its
// environment file, and PHPUnit's <env> directives only populate getenv() and
// $_ENV. Laravel's env() helper reads $_SERVER first, so without this sync the
// test suite would run against the development database. Mirror the values
// PHPUnit set (testing env) into $_SERVER so the test database is isolated.
foreach ([
    'APP_ENV',
    'APP_KEY',
    'DB_CONNECTION',
    'DB_DATABASE',
    'CACHE_STORE',
    'SESSION_DRIVER',
    'QUEUE_CONNECTION',
    'MAIL_MAILER',
] as $key) {
    $value = getenv($key);

    if ($value !== false) {
        $_SERVER[$key] = $value;
    }
}
