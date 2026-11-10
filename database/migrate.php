<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$env = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW);
$dbName = $env['DB_NAME'] ?? '';

if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
    exit("DB_NAME kwenye .env si sahihi.\n");
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $server = new PDO(
        "mysql:host={$env['DB_HOST']};charset=utf8mb4",
        $env['DB_USER'],
        $env['DB_PASS'],
        $options
    );
    $server->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};dbname=$dbName;charset=utf8mb4",
        $env['DB_USER'],
        $env['DB_PASS'],
        $options
    );
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    exit("Imeshindwa kuunganisha MySQL. Hakikisha MySQL imewashwa kwenye XAMPP na .env ni sahihi.\n");
}

echo "Database '$dbName' iko tayari.\n";

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        filename   VARCHAR(190) NOT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_migrations_filename (filename)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = $pdo->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/[0-9][0-9][0-9]_*.sql') ?: [];
sort($files);

$ran = 0;

foreach ($files as $file) {
    $filename = basename($file);

    if (in_array($filename, $applied, true)) {
        continue;
    }

    echo "Inaendesha: $filename\n";

    $statements = array_filter(array_map('trim', explode(';', file_get_contents($file))));

    foreach ($statements as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $ex) {
            if ((int) ($ex->errorInfo[1] ?? 0) === 1050) {
                echo "  Meza tayari ipo, nimeruka.\n";
                continue;
            }
            error_log($ex->getMessage());
            exit("  Imeshindwa: " . $ex->getMessage() . "\n");
        }
    }

    $pdo->prepare('INSERT INTO migrations (filename) VALUES (:f)')->execute([':f' => $filename]);
    echo "  Imekamilika.\n";
    $ran++;
}

echo $ran === 0 ? "Hakuna migration mpya.\n" : "Migration zilizoendeshwa: $ran\n";