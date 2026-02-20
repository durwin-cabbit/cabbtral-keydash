<?php
// api/db.php - Optimized for Neon & Easiest Setup
error_reporting(0); // Prevent warnings from breaking JSON
$db_error = null;
$use_json = false;
$pdo = null;

try {
    // Check various common env variable names
    $urlStr = getenv('DATABASE_URL') ?: getenv('POSTGRES_URL') ?: getenv('PRISMA_DATABASE_URL');

    if ($urlStr && (strpos($urlStr, 'postgres') === 0)) {
        $url = parse_url($urlStr);
        if ($url) {
            $host = $url["host"] ?? '';
            $port = $url["port"] ?? 5432;
            $user = $url["user"] ?? '';
            $pass = $url["pass"] ?? '';
            $path = ltrim($url["path"] ?? '', "/");
            $db   = explode('?', $path)[0];
            
            $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
            
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5
            ]);

            $pdo->exec("CREATE TABLE IF NOT EXISTS keys (
                id SERIAL PRIMARY KEY,
                key_value TEXT UNIQUE NOT NULL,
                status TEXT DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        } else {
            $use_json = true;
        }
    } else {
        // Fallback to JSON if no database is found
        $use_json = true;
    }
} catch (Exception $e) {
    $db_error = $e->getMessage();
    $use_json = true; 
}

function getJsonKeys() {
    $json_db = __DIR__ . '/../database.json';
    if (!file_exists($json_db)) return [];
    $content = file_get_contents($json_db);
    return json_decode($content, true) ?: [];
}

function saveJsonKeys($keys) {
    $json_db = __DIR__ . '/../database.json';
    @file_put_contents($json_db, json_encode(array_values($keys)));
}
