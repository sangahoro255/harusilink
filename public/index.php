<?php
require __DIR__ . '/../app/bootstrap.php';

$router = new App\Core\Router();
require BASE_PATH . '/routes/web.php';
$router->dispatch();