<?php

// File untuk debugging endpoint SATUSEHAT
require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Models\Clinic;
use App\Services\SatuSehatService;
use Illuminate\Support\Facades\Log;

// Coba inisialisasi service container Laravel
$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Menambahkan handler log untuk menulis ke file
// $app->configureMonologUsing(function ($monolog) {
//     // Log level: debug
//     $monolog->pushHandler(new \Monolog\Handler\StreamHandler(
//         __DIR__.'/storage/logs/debug.log',
//         \Monolog\Logger::DEBUG
//     ));
// });

// Test endpoint
try {
    Log::info("Testing SATUSEHAT endpoint...");
    echo "Testing SATUSEHAT endpoint...\n";

    // Ambil klinik pertama
    $clinic = Clinic::first();
    if (!$clinic) {
        $msg = "Tidak ditemukan klinik. Silakan buat klinik terlebih dahulu.";
        echo $msg . "\n";
        Log::error($msg);
        exit;
    }

    $msg = "Klinik ditemukan: " . $clinic->name . " (ID: " . $clinic->id . ")";
    echo $msg . "\n";
    Log::info($msg);
    Log::info("Organization ID: " . $clinic->organization_id);
    Log::info("Client ID: " . $clinic->satusehat_client_id);
    Log::info("Client Secret exists: " . (!empty($clinic->satusehat_client_secret) ? "Yes" : "No"));

    // Coba inisialisasi SatuSehatService
    echo "Mencoba inisialisasi SatuSehatService...\n";
    $service = new SatuSehatService($clinic);
    $msg = "SatuSehatService berhasil diinisialisasi";
    echo $msg . "\n";
    Log::info($msg);

    // Coba ambil token
    echo "Mencoba mengambil token...\n";
    $token = $service->getAccessToken();
    $msg = "Token berhasil diambil: " . substr($token, 0, 20) . "...";
    echo $msg . "\n";
    Log::info($msg);

    echo "Proses selesai tanpa error.\n";
    Log::info("Proses selesai tanpa error.");

} catch (Exception $e) {
    $errorMsg = "Error: " . $e->getMessage() . "\nFile: " . $e->getFile() . "\nLine: " . $e->getLine() . "\nTrace: \n" . $e->getTraceAsString();
    echo $errorMsg . "\n";
    Log::error($errorMsg);
}
// Simple script to validate PHP syntax of our files

$files = [
    'c:\laragon\www\service-satusehat\app\Services\SatuSehatService.php',
    'c:\laragon\www\service-satusehat\app\Http\Controllers\EncounterController.php',
    'c:\laragon\www\service-satusehat\app\Http\Controllers\SatusehatIdController.php',
    'c:\laragon\www\service-satusehat\app\Http\Middleware\AuthenticateClinic.php',
    'c:\laragon\www\service-satusehat\app\Jobs\SendEncounterToSatusehat.php',
    'c:\laragon\www\service-satusehat\app\Models\SatusehatLog.php'
];

foreach ($files as $file) {
    $output = shell_exec('php -l ' . escapeshellarg($file) . ' 2>&1');
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✓ $file - OK\n";
    } else {
        echo "✗ $file - ERROR\n";
        echo "  Output: $output\n";
    }
}

echo "Syntax check completed.\n";
