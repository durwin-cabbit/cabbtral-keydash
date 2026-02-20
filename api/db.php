<?php
// api/db.php - Optimized for KEYS_DATABASE_URL
error_reporting(0);
$db_error = null;
$pdo = null;

try {
    // Look for your specific Vercel variable name
    $urlStr = getenv('KEYS_DATABASE_URL') ?: getenv('DATABASE_URL') ?: getenv('POSTGRES_URL');

    if ($urlStr) {
        // Standardize the protocol for PHP's parse_url
        $dbUrl = str_replace('postgresql://', 'postgres://', $urlStr);
        $url = parse_url($dbUrl);
        
        if ($url) {
            $host = $url["host"];
            $port = $url["port"] ?? 5432;
            $user = $url["user"];
            $pass = $url["pass"];
            $db   = ltrim(explode('?', $url["path"])[0], "/");
            
            $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
            
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 15
            ]);

            // Table setup
            $pdo->exec("CREATE TABLE IF NOT EXISTS keys (
                id SERIAL PRIMARY KEY,
                key_value TEXT UNIQUE NOT NULL,
                status TEXT DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        } else {
            $db_error = "Invalid URL format in KEYS_DATABASE_URL.";
        }
    } else {
        $db_error = "KEYS_DATABASE_URL not found in Vercel Environment Variables.";
    }
} catch (Exception $e) {
    $db_error = $e->getMessage();
}
