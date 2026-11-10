<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/database.php';

function ask(string $question): string
{
    echo $question;
    return rtrim((string) fgets(STDIN), "\r\n");
}

$name     = trim(ask('Jina kamili: '));
$email    = strtolower(trim(ask('Barua pepe: ')));
$password = ask('Nenosiri (angalau herufi 12): ');

if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    exit("Taarifa si sahihi. Hakuna kilichohifadhiwa.\n");
}

try {
    $stmt = db()->prepare(
        "INSERT INTO users (name, email, password_hash, role)
         VALUES (:name, :email, :hash, 'super_admin')"
    );
    $stmt->execute([
        ':name'  => $name,
        ':email' => $email,
        ':hash'  => password_hash($password, PASSWORD_DEFAULT),
    ]);
    echo "Super Admin ametengenezwa.\n";
} catch (PDOException $ex) {
    if ($ex->getCode() === '23000') {
        exit("Barua pepe hii tayari ipo.\n");
    }
    error_log($ex->getMessage());
    exit("Imeshindwa kuhifadhi. Angalia log.\n");
}