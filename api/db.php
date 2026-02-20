<?php
// api/db.php - Final Robust Connection
error_reporting(0);
$db_error = null;
$pdo = null;
$use_json = false;

try {
    // Try every possible way to get the connection string
    $urlStr = getenv('DATABASE_URL') ?: $_ENV['DATABASE_URL'] ?: getenv('POSTGRES_URL') ?: $_ENV['POSTGRES_URL'];

    if ($urlStr && strpos($urlStr, 'postgres') === 0) {
        $url = parse_url($urlStr);
        $host = $url["host"];
        $port = $url["port"] ?? 5432;
        $user = $url["user"];
        $pass = $url["pass"];
        $db   = ltrim(explode('?', $url["path"])[0], "/");
        
        $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
        
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10
        ]);

        $pdo->exec("CREATE TABLE IF NOT EXISTS keys (
            id SERIAL PRIMARY KEY,
            key_value TEXT UNIQUE NOT NULL,
            status TEXT DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $db_error = "No DATABASE_URL found. Please connect Neon in Vercel Storage settings.";
    }
} catch (Exception $e) {
    $db_error = $e->getMessage();
}
