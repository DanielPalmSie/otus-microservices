<?php
$pdo = new PDO('pgsql:host=' . getenv('DATABASE_HOST') . ';port=' . getenv('DATABASE_PORT') . ';dbname=' . getenv('DATABASE_NAME'), getenv('DATABASE_USER'), getenv('DATABASE_PASSWORD'));
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL
)");
echo "Migration completed.";
