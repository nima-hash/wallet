<?php
$dotenv = file(__DIR__ . "/databankconfig.env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($dotenv as $line) {
    putenv(trim($line));
}

// Load database variables
$DB_HOST = getenv("DB_HOST");
$DB_USERNAME = getenv("DB_USERNAME");
$DB_PASSWORD = getenv("DB_PASSWORD");
$DB_DATABASE_NAME = getenv("DB_DATABASE_NAME");
$DB_PORT = getenv("DB_PORT");

// Load JWT secret key
$JWT_SECRET = getenv("JWT_SECRET");
?>