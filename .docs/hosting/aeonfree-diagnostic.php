<?php

/**
 * GEARORA — AeonFree Hosting Diagnostic (SAFE)
 * -------------------------------------------------
 * Script ini HANYA menampilkan informasi non-rahasia yang dibutuhkan
 * untuk memvalidasi kompatibilitas hosting dengan Laravel 12.
 *
 * TIDAK menampilkan:
 *  - environment variable / isi .env
 *  - password / kredensial database
 *  - isi file apapun
 *  - server secret
 *
 * CARA PAKAI
 *  1. Upload file ini ke public_html/ akun AeonFree.
 *  2. Buka https://<domain-anda>/aeonfree-diagnostic.php di browser.
 *  3. Salin hasilnya (bukan kredensial) ke dokumen validasi.
 *  4. HAPUS file ini dari hosting setelah selesai.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

/** Ekstensi yang dibutuhkan / disarankan Laravel 12. */
$required = ['pdo_mysql', 'mbstring', 'fileinfo', 'openssl', 'ctype', 'curl', 'dom', 'xml', 'tokenizer', 'json'];
$recommended = ['bcmath', 'intl', 'gd', 'zip', 'sodium', 'session'];

function line(string $label, string $value): void
{
    echo str_pad($label, 28) . ': ' . $value . PHP_EOL;
}

echo "GEARORA — AeonFree Diagnostic" . PHP_EOL;
echo str_repeat('=', 52) . PHP_EOL . PHP_EOL;

echo "[RUNTIME]" . PHP_EOL;
line('PHP version', PHP_VERSION);
line('SAPI', PHP_SAPI);
line('OS', PHP_OS_FAMILY);
line('64-bit', PHP_INT_SIZE === 8 ? 'yes' : 'no');
line('memory_limit', (string) ini_get('memory_limit'));
line('max_execution_time', (string) ini_get('max_execution_time'));
line('upload_max_filesize', (string) ini_get('upload_max_filesize'));
line('post_max_size', (string) ini_get('post_max_size'));
echo PHP_EOL;

echo "[EXTENSIONS — REQUIRED]" . PHP_EOL;
foreach ($required as $ext) {
    line($ext, extension_loaded($ext) ? 'OK' : 'MISSING');
}
echo PHP_EOL;

echo "[EXTENSIONS — RECOMMENDED]" . PHP_EOL;
foreach ($recommended as $ext) {
    line($ext, extension_loaded($ext) ? 'OK' : 'missing (optional)');
}
echo PHP_EOL;

echo "[DATABASE]" . PHP_EOL;
line('PDO drivers', implode(', ', PDO::getAvailableDrivers()) ?: '(none)');
if (extension_loaded('mysqli')) {
    line('mysqli client', mysqli_get_client_info());
}
// Catatan: versi server MySQL hanya tampil bila koneksi berhasil,
// dan script ini sengaja TIDAK memakai kredensial apa pun.
echo PHP_EOL;

echo "[SERVER CAPABILITIES]" . PHP_EOL;
line('mod_rewrite (bila Apache)', function_exists('apache_get_modules')
    ? (in_array('mod_rewrite', apache_get_modules(), true) ? 'ON' : 'OFF/unknown')
    : 'unknown (non-Apache SAPI)');
line('symlink() function', function_exists('symlink') ? 'available' : 'disabled');
line('proc_open()', function_exists('proc_open') ? 'available' : 'disabled');
line('shell_exec()', function_exists('shell_exec') ? 'available' : 'disabled');
echo PHP_EOL;

echo "[WRITABLE DIRECTORIES]" . PHP_EOL;
$dirs = [
    'current dir (public_html)' => __DIR__,
    'parent dir' => dirname(__DIR__),
];
foreach ($dirs as $label => $dir) {
    line($label, is_dir($dir) ? (is_writable($dir) ? 'writable' : 'NOT writable') : 'not found');
}
line('temp dir', sys_get_temp_dir() === '' ? 'unknown' : (is_writable(sys_get_temp_dir()) ? 'writable' : 'NOT writable'));
echo PHP_EOL;

echo "[TIMEZONE / LOCALE]" . PHP_EOL;
line('date.timezone', (string) (ini_get('date.timezone') ?: '(default)'));
line('default timezone', date_default_timezone_get());
line('current time', date('Y-m-d H:i:s P'));
echo PHP_EOL;

echo str_repeat('=', 52) . PHP_EOL;
echo "HAPUS file ini setelah selesai." . PHP_EOL;
