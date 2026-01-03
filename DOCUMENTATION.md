# Multi-Klinik SATUSEHAT Service

Dokumentasi untuk layanan SATUSEHAT yang dapat digunakan oleh beberapa klinik sekaligus.

## Deskripsi

Layanan SATUSEHAT ini dirancang untuk menangani integrasi dengan SATUSEHAT bagi beberapa klinik sekaligus. Setiap klinik memiliki kredensial dan organisasi SATUSEHAT masing-masing.

## Struktur Database

Tabel `clinics` memiliki kolom-kolom berikut:
- `code` - Kode unik klinik (misal: KLINIK-001)
- `name` - Nama klinik
- `satusehat_client_id` - Client ID dari SATUSEHAT
- `satusehat_client_secret` - Client Secret dari SATUSEHAT (terenkripsi)
- `satusehat_access_token` - Akses token SATUSEHAT saat ini (terenkripsi)
- `satusehat_token_expires_at` - Waktu kadaluarsa token
- `organization_id` - ID organisasi di SATUSEHAT
- `api_shared_secret` - Secret untuk HMAC (jika digunakan)
- `active` - Status aktif/nonaktif klinik

## Penggunaan Layanan

### Instansiasi dan Pengaturan Klinik

```php
use App\Services\SatuSehatService;
use App\Models\Clinic;

// Ambil klinik dari database
$clinic = Clinic::where('code', 'KLINIK-001')->first();

// Buat instance layanan dan atur klinik
$satuSehatService = new SatuSehatService();
$satuSehatService->setClinic($clinic);
```

Atau secara langsung:

```php
$clinic = Clinic::where('code', 'KLINIK-001')->first();
$satuSehatService = new SatuSehatService($clinic);
```

### Mengambil Token Akses

```php
$token = $satuSehatService->getAccessToken();
```

### Mengirim Data ke SATUSEHAT

#### Mengirim Data Pasien

```php
$patientData = [
    'resourceType' => 'Patient',
    'id' => 'some-id',
    'name' => [
        [
            'use' => 'official',
            'family' => 'Lastname',
            'given' => ['Firstname']
        ]
    ],
    'gender' => 'male',
    'birthDate' => '1990-01-01',
    // ... tambahan data pasien sesuai FHIR
];

$response = $satuSehatService->sendPatient($patientData);
```

#### Mengirim Data Praktisi

```php
$practitionerData = [
    'resourceType' => 'Practitioner',
    'id' => 'some-practitioner-id',
    'name' => [
        [
            'use' => 'official',
            'family' => 'Dokter',
            'given' => ['Nama', 'Lengkap']
        ]
    ],
    'gender' => 'male',
    // ... tambahan data praktisi sesuai FHIR
];

$response = $satuSehatService->sendPractitioner($practitionerData);
```

#### Mengirim Data Kunjungan

```php
$encounterData = [
    'resourceType' => 'Encounter',
    'id' => 'some-encounter-id',
    'status' => 'finished',
    'class' => [
        'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
        'code' => 'AMB',
        'display' => 'ambulatory'
    ],
    'subject' => [
        'reference' => 'Patient/some-patient-id'
    ],
    // ... tambahan data kunjungan sesuai FHIR
];

$response = $satuSehatService->sendEncounter($encounterData);
```

## Contoh Penggunaan dalam Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Services\SatuSehatService;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function store(Request $request, $clinicCode)
    {
        $clinic = Clinic::where('code', $clinicCode)->firstOrFail();
        
        $data = $request->validate([
            'name' => 'required|array',
            'name.0.text' => 'required',
            'gender' => 'required|in:male,female,other',
            'birthDate' => 'required|date',
            // ... validasi lainnya
        ]);

        $satuSehatService = new SatuSehatService();
        $satuSehatService->setClinic($clinic);

        try {
            $response = $satuSehatService->sendPatient($data);
            return response()->json(['status' => 'success', 'data' => $response]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
```

## Konfigurasi Environment

Tambahkan variabel-variabel berikut ke file `.env` Anda:

```
# SATUSEHAT Configuration
SATUSEHAT_ENV=DEV
SATUSEHAT_TIMEOUT=60
SS_PARAMETER_OVERRIDE=false

# Development Environment
SATUSEHAT_BASE_URL_DEV=https://api-satusehat-dev.dto.kemkes.go.id
CLIENTID_DEV=your_dev_client_id
CLIENTSECRET_DEV=your_dev_client_secret
ORGID_DEV=your_dev_organization_id

# Staging Environment
SATUSEHAT_BASE_URL_STG=https://api-satusehat-stg.dto.kemkes.go.id
CLIENTID_STG=your_stg_client_id
CLIENTSECRET_STG=your_stg_client_secret
ORGID_STG=your_stg_organization_id

# Production Environment
SATUSEHAT_BASE_URL_PROD=https://api-satusehat.kemkes.go.id
CLIENTID_PROD=your_prod_client_id
CLIENTSECRET_PROD=your_prod_client_secret
ORGID_PROD=your_prod_organization_id
```

## Migrasi Database

Jalankan perintah berikut untuk menjalankan migrasi yang menambahkan kolom organization_id:

```bash
php artisan migrate
```

Perlu dicatat bahwa migrasi untuk menambahkan kolom organization_id ke tabel clinics sudah disertakan dalam aplikasi.