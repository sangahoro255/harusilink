<?php
if (PHP_SAPI !== 'cli') {
    exit;
}

$root = dirname(__DIR__);
$skip = ['vendor', 'storage', '.git', 'node_modules'];

$iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    function (SplFileInfo $file) use ($skip): bool {
        return !($file->isDir() && in_array($file->getFilename(), $skip, true));
    }
));

$checked = 0;
$failed = 0;

foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $checked++;

    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1';

    if (DIRECTORY_SEPARATOR === '\\') {
        $command = '"' . $command . '"';
    }

    $output = [];
    $code = 0;
    exec($command, $output, $code);

    if ($code !== 0) {
        $failed++;
        echo implode("\n", $output) . "\n";
    }
}

echo "Faili zilizokaguliwa: $checked\n";
echo "Zenye makosa: $failed\n";

exit($failed === 0 ? 0 : 1);