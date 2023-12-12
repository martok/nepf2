<?php
/**
 * Nepf2 Framework - server runner
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

// if the server is started with -t, respect that
chdir($_SERVER['DOCUMENT_ROOT']);

// resolve the query string
$queryString = $_SERVER['QUERY_STRING'] ?? null;
$filePath = realpath(ltrim(($queryString ? $_SERVER["SCRIPT_NAME"] : $_SERVER["REQUEST_URI"]), '/'));

// if it is a directory, serve the index file if present
if ($filePath && is_dir($filePath)) {
    foreach (['index.php', 'index.html'] as $indexFile) {
        if ($filePath = realpath($filePath . DIRECTORY_SEPARATOR . $indexFile)){
            break;
        }
    }
}

// closure to isolate the content scope from the wrapper
$importer = \Closure::bind(static function ($__server_include) {
    // change directory for php root includes
    chdir(dirname($__server_include));
    include $__server_include;
}, null, null);

$startTime = microtime(true);
$staticResource = false;
$exception = null;
try {
    // if the file exists, serve it
    if ($filePath && is_file($filePath)) {
        // 1. check that file is not outside (behind) of this directory for security
        // 2. check for circular reference to server.php
        // 3. don't serve dotfiles
        if (str_starts_with($filePath, getcwd()) &&
            $filePath != __FILE__ &&
            !str_starts_with(basename($filePath), '.')) {
            if (str_ends_with(strtolower($filePath), '.php')) {
                // php file; serve through interpreter
                $importer($filePath);
            } else {
                // asset file; serve from filesystem
                $staticResource = true;
                return false;
            }
        } else {
            // disallowed file
            header("HTTP/1.1 404 Not Found");
            echo "404 Not Found";
        }
    } else {
        // rewrite to our global router
        $importer(getcwd() . DIRECTORY_SEPARATOR . 'index.php');
    }
} catch(\Throwable $throwable) {
    // save exception to print it after the log line
    $exception = $throwable;
} finally {
    // the built-in server only writes the request log if it served the file itself
    if (!$staticResource) {
        $finTime = microtime(true);
        $stdErr = fopen("php://stderr",'w+');
        $msg = sprintf('[%s] %s:%d [%03d]: %s %s (%.1fms)',
                       date('D M j H:i:s Y', $_SERVER['REQUEST_TIME']),
                       $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'],
                       http_response_code(), $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'],
                       ($finTime-$startTime)*1000);
        fwrite($stdErr, $msg . PHP_EOL);
        if (!is_null($exception))
            fwrite($stdErr, (string)$exception . PHP_EOL);
        fclose($stdErr);
    }
}