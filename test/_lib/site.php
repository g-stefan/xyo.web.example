<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Test site helpers:
//   site_build($name)   - assemble a fresh site in temp/test/<name>/site from
//                         the xyo.web vendor archive plus the source folder
//                         (what "fabricare make" does, without Tailwind CSS)
//   site_server_start() - run it on the PHP built-in web server
//   Client              - minimal HTTP client with a cookie jar
//   site_db()           - PDO handle on the example SQLite database
//   site_service()      - run service-example.php from the command line

require_once(__DIR__ . "/harness.php");

define("TEST_PROJECT_PATH", realpath(__DIR__ . "/../..") . "/");

function test_fatal($message)
{
    echo "FATAL: " . $message . "\n";
    echo "\nRESULT: 0 passed, 1 failed\n";
    exit(1);
}

function test_xyo_web_version()
{
    $json = json_decode(file_get_contents(TEST_PROJECT_PATH . "fabricare.json"), true);
    return $json["solution"]["vendor"]["xyo.web"];
}

function test_remove_dir($path)
{
    if (!file_exists($path)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        if ($item->isDir() && !$item->isLink()) {
            rmdir($item->getPathname());
            continue;
        }
        unlink($item->getPathname());
    }
    rmdir($path);
}

function test_copy_dir($source, $target)
{
    if (!is_dir($target)) {
        mkdir($target, 0777, true);
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($items as $item) {
        $destination = $target . "/" . substr($item->getPathname(), strlen($source) + 1);
        if ($item->isDir()) {
            if (!is_dir($destination)) {
                mkdir($destination, 0777, true);
            }
            continue;
        }
        copy($item->getPathname(), $destination);
    }
}

function test_7z_extract($archive, $target)
{
    foreach (["7zr", "7z", "7za"] as $tool) {
        $output = [];
        $code = 0;
        exec($tool . " x -y " . escapeshellarg($archive) . " " . escapeshellarg("-o" . $target) . " 2>&1", $output, $code);
        if ($code === 0) {
            return true;
        }
    }
    // 7zr reads only .7z archives; the Windows tar (bsdtar) and unzip read .zip,
    // GNU tar (Git Bash puts it first on PATH) does not
    $commands = ["unzip -q -o " . escapeshellarg($archive) . " -d " . escapeshellarg($target)];
    if (PHP_OS_FAMILY === "Windows") {
        $tar = getenv("SystemRoot") . "\\System32\\tar.exe";
        array_unshift($commands, escapeshellarg($tar) . " -xf " . escapeshellarg($archive) . " -C " . escapeshellarg($target));
    }
    foreach ($commands as $command) {
        $output = [];
        $code = 0;
        exec($command . " 2>&1", $output, $code);
        if ($code === 0) {
            return true;
        }
    }
    return false;
}

// Build a fresh copy of the site, returns its path.
function site_build($name)
{
    $vendor = TEST_PROJECT_PATH . "vendor/xyo.web.v" . test_xyo_web_version() . ".zip";
    if (!file_exists($vendor)) {
        test_fatal("missing " . $vendor . " - run \"fabricare vendor\" first");
    }

    $base = TEST_PROJECT_PATH . "temp/test/" . $name;
    test_remove_dir($base);
    $site = $base . "/site";
    mkdir($site, 0777, true);

    if (!test_7z_extract($vendor, $site)) {
        test_fatal("unable to extract " . $vendor . " (7z / tar / unzip not found?)");
    }
    test_copy_dir(TEST_PROJECT_PATH . "source", $site);

    return str_replace("\\", "/", realpath($site));
}

function test_free_port()
{
    $server = stream_socket_server("tcp://127.0.0.1:0", $errno, $errstr);
    if (!$server) {
        test_fatal("unable to find a free port: " . $errstr);
    }
    $name = stream_socket_get_name($server, false);
    fclose($server);
    return intval(substr($name, strrpos($name, ":") + 1));
}

$GLOBALS["__site_server"] = [];

// Start the PHP built-in web server on the site, returns the base URL. Each
// call starts one more server on the same site: the built-in server answers
// one request at a time, several servers give concurrent requests.
function site_server_start($site)
{
    $port = test_free_port();
    $log = dirname($site) . "/server.log";
    $command = [
        PHP_BINARY,
        "-S",
        "127.0.0.1:" . $port,
        "-t",
        $site,
        __DIR__ . "/router.php"
    ];
    $process = proc_open($command, [
        0 => ["pipe", "r"],
        1 => ["file", $log, "a"],
        2 => ["file", $log, "a"]
    ], $pipes, $site);
    if (!is_resource($process)) {
        test_fatal("unable to start the PHP built-in web server");
    }
    if (count($GLOBALS["__site_server"]) == 0) {
        register_shutdown_function("site_server_stop");
    }
    $GLOBALS["__site_server"][] = [$process, $pipes];

    $deadline = microtime(true) + 10;
    while (microtime(true) < $deadline) {
        $socket = @fsockopen("127.0.0.1", $port, $errno, $errstr, 0.2);
        if ($socket) {
            fclose($socket);
            return "http://127.0.0.1:" . $port . "/";
        }
        usleep(100000);
    }
    test_fatal("the PHP built-in web server did not start, see " . $log);
}

function site_server_stop()
{
    $servers = $GLOBALS["__site_server"];
    $GLOBALS["__site_server"] = [];
    foreach ($servers as $server) {
        list($process, $pipes) = $server;
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        proc_terminate($process);
        proc_close($process);
    }
}

// Minimal HTTP client that keeps cookies (the session and the _token cookie).
class Client
{
    public $baseURL;
    public $cookies = [];
    public $status = 0;
    public $headers = [];
    public $body = "";

    public function __construct($baseURL)
    {
        $this->baseURL = $baseURL;
    }

    public function request($method, $path, $fields = null)
    {
        $header = "";
        if (count($this->cookies)) {
            $list = [];
            foreach ($this->cookies as $name => $value) {
                $list[] = $name . "=" . $value;
            }
            $header .= "Cookie: " . implode("; ", $list) . "\r\n";
        }
        $content = "";
        if (!is_null($fields)) {
            $content = http_build_query($fields);
            $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        }
        $context = stream_context_create([
            "http" => [
                "method" => $method,
                "header" => $header,
                "content" => $content,
                "ignore_errors" => true,
                "follow_location" => 0,
                "timeout" => 30
            ]
        ]);
        $body = @file_get_contents($this->baseURL . ltrim($path, "/"), false, $context);
        $this->body = ($body === false) ? "" : $body;
        $this->status = 0;
        $this->headers = [];
        $responseHeaders = function_exists("http_get_last_response_headers") ? http_get_last_response_headers() : ($http_response_header ?? []);
        foreach ($responseHeaders ?? [] as $line) {
            if (preg_match("/^HTTP\\/\\S+\\s+(\\d+)/", $line, $match)) {
                $this->status = intval($match[1]);
                continue;
            }
            $pos = strpos($line, ":");
            if ($pos === false) {
                continue;
            }
            $name = strtolower(trim(substr($line, 0, $pos)));
            $value = trim(substr($line, $pos + 1));
            $this->headers[$name][] = $value;
            if ($name === "set-cookie") {
                $cookie = explode(";", $value)[0];
                $eq = strpos($cookie, "=");
                if ($eq !== false) {
                    $this->cookies[substr($cookie, 0, $eq)] = substr($cookie, $eq + 1);
                }
            }
        }
        return $this->body;
    }

    public function get($path)
    {
        return $this->request("GET", $path);
    }

    public function post($path, $fields)
    {
        return $this->request("POST", $path, $fields);
    }

    public function header($name)
    {
        $name = strtolower($name);
        if (!array_key_exists($name, $this->headers)) {
            return null;
        }
        return $this->headers[$name][0];
    }

    // The CSRF token of the session, as rendered in the page.
    public function token()
    {
        if (preg_match("/name=\"_token\" value=\"([0-9a-f]+)\"/", $this->body, $match)) {
            return $match[1];
        }
        return null;
    }
}

// All "Counter: N" values of a response, in document order.
function test_counters($html)
{
    preg_match_all("/Counter: (\\d+)/", $html, $match);
    return array_map("intval", $match[1]);
}

function site_db($site)
{
    $db = new PDO("sqlite:" . $site . "/_repository/example.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

// Run service-example.php from the site folder, returns [exitCode, output].
function site_service($site)
{
    $cwd = getcwd();
    chdir($site);
    $output = [];
    $code = 0;
    exec(escapeshellarg(PHP_BINARY) . " service-example.php 2>&1", $output, $code);
    chdir($cwd);
    return [$code, implode("\n", $output)];
}

// Start all commands (argument arrays) at once in $cwd and wait for them,
// returns [exitCode, output] for each. Outputs go through files in $logPath,
// so a process that writes a lot cannot block on a full pipe.
function test_run_parallel($commands, $cwd, $logPath)
{
    if (!is_dir($logPath)) {
        mkdir($logPath, 0777, true);
    }
    $running = [];
    foreach ($commands as $index => $command) {
        $log = $logPath . "/process-" . $index . ".log";
        $process = proc_open($command, [
            0 => ["pipe", "r"],
            1 => ["file", $log, "w"],
            2 => ["file", $log, "a"]
        ], $pipes, $cwd);
        if (!is_resource($process)) {
            test_fatal("unable to start " . implode(" ", $command));
        }
        fclose($pipes[0]);
        $running[$index] = [$process, $log];
    }
    $results = [];
    foreach ($running as $index => list($process, $log)) {
        $code = proc_close($process);
        $results[$index] = [$code, file_get_contents($log)];
    }
    return $results;
}
