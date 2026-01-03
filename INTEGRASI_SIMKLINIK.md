# Panduan Integrasi SIMKlinik dengan SATUSEHAT Service

## Daftar Isi
1. [Prasyarat Integrasi](#prasyarat-integrasi)
2. [Persiapan Data Master](#persiapan-data-master)
3. [Proses Integrasi](#proses-integrasi)
4. [Implementasi Teknis](#implementasi-teknis)
5. [Uji Coba dan Validasi](#uji-coba-dan-validasi)
6. [Pemantauan dan Troubleshooting](#pemantauan-dan-troubleshooting)
7. [Best Practices](#best-practices)

## Prasyarat Integrasi

### 1. Persiapan Infrastruktur
- Pastikan SIMKlinik Anda memiliki akses internet yang stabil
- Pastikan versi PHP Anda kompatibel (PHP 7.4+ direkomendasikan)
- Pastikan sistem mampu melakukan permintaan HTTP (cURL aktif)

### 2. Kredensial SATUSEHAT
- Dapatkan Client ID dan Client Secret dari SATUSEHAT
- Dapatkan Organization ID dari SATUSEHAT
- Pastikan Anda memiliki akses ke lingkungan (DEV/STG/PROD) sesuai kebutuhan

### 3. Kredensial SATUSEHAT Service
- Dapatkan URL SATUSEHAT Service dari penyedia layanan
- Dapatkan Clinic Code dan Clinic Secret dari SATUSEHAT Service
- Pastikan Anda memiliki akses ke endpoint API

## Persiapan Data Master

### 1. Data Pasien
Pastikan data pasien di SIMKlinik memiliki:
- NIK lengkap (16 digit)
- Nama lengkap
- Tanggal lahir
- Jenis kelamin
- Alamat lengkap (termasuk kode provinsi, kabupaten, kecamatan, kelurahan)
- Nomor HP
- Nama ibu kandung
- Golongan darah
- Status pernikahan
- Pekerjaan

### 2. Data Dokter/Praktisi
Pastikan data dokter di SIMKlinik memiliki:
- NIK lengkap (16 digit)
- Nama lengkap
- Nomor SIP (Surat Izin Praktik)
- Nomor STR (Surat Tanda Registrasi)
- Tempat dan tanggal lahir
- Jenis kelamin
- Alamat lengkap
- Nomor HP dan email
- Spesialisasi

### 3. Data Poli
Pastikan data poli di SIMKlinik memiliki:
- Kode poli
- Nama poli
- Deskripsi poli

## Proses Integrasi

### 1. Registrasi Data Master ke SATUSEHAT

#### A. Proses Registrasi Pasien
1. **Cek Ketersediaan ID SATUSEHAT**
   - Gunakan endpoint: `POST /api/satusehat/get-patient-id`
   - Kirim NIK sebagai parameter
   - Jika ditemukan, gunakan ID yang tersedia
   - Jika tidak ditemukan, lanjut ke langkah berikutnya

2. **Registrasi Pasien Baru**
   - Gunakan endpoint: `POST /api/satusehat/register-patient`
   - Kirim semua data pasien dalam format yang sesuai
   - Simpan ID SATUSEHAT yang dihasilkan

3. **Mapping ke Database Lokal**
   - Simpan ID SATUSEHAT di tabel pasien lokal
   - Buat kolom tambahan jika perlu (misal: `satusehat_patient_id`)

#### B. Proses Registrasi Dokter
1. **Cek Ketersediaan ID SATUSEHAT**
   - Gunakan endpoint: `POST /api/satusehat/get-practitioner-id`
   - Kirim SIP atau STR sebagai parameter (lebih disarankan)
   - Alternatif: Kirim NIK sebagai parameter
   - Jika ditemukan, gunakan ID yang tersedia
   - Jika tidak ditemukan, lanjut ke langkah berikutnya

2. **Registrasi Dokter Baru**
   - Gunakan endpoint: `POST /api/satusehat/register-practitioner`
   - Kirim semua data dokter dalam format yang sesuai
   - Simpan ID SATUSEHAT yang dihasilkan

3. **Mapping ke Database Lokal**
   - Simpan ID SATUSEHAT di tabel dokter lokal
   - Buat kolom tambahan jika perlu (misal: `satusehat_practitioner_id`)

#### C. Proses Registrasi Poli
1. **Cek Ketersediaan ID SATUSEHAT**
   - Gunakan endpoint: `POST /api/satusehat/get-healthcare-service-id`
   - Kirim kode poli sebagai parameter
   - Jika ditemukan, gunakan ID yang tersedia
   - Jika tidak ditemukan, perlu mendaftar manual ke SATUSEHAT

### 2. Proses Pengiriman Data Klinis

#### A. Proses Pengiriman Kunjungan (Encounter)
1. **Persiapan Data Kunjungan**
   - SIMKlinik hanya perlu menyediakan NIK pasien (bukan ID SATUSEHAT)
   - SIMKlinik perlu menyediakan ID SATUSEHAT untuk dokter dan poli
   - Siapkan data lengkap kunjungan

2. **Kirim Data Kunjungan**
   - Gunakan endpoint: `POST /api/encounter`
   - Gunakan NIK pasien (bukan ID SATUSEHAT)
   - Gunakan ID SATUSEHAT untuk dokter dan poli
   - Service akan mencari atau membuat ID SATUSEHAT pasien secara otomatis
   - Kirim data kunjungan dalam format yang ditentukan

**Contoh format data kunjungan:**
```json
{
    "nik_pasien": "3201234567890123",
    "tanggal_kunjungan": "2024-01-15T08:30:00Z",
    "tanggal_selesai": "2024-01-15T09:15:00Z",
    "jenis_layanan": "101",
    "jenis_kunjungan": "1",
    "poli": "100001", // ID SATUSEHAT poli
    "dokter": "10001234567", // ID SATUSEHAT dokter
    "penjamin": "1",
    "keluhan_utama": "Sakit kepala berat sejak 3 hari lalu",
    "anamnesa": "Pasien datang dengan keluhan sakit kepala berat...",
    "pemeriksaan_fisik": {
        "tanda_vital": {
            "tekanan_darah": "130/85",
            "nadi": 82,
            "suhu": 37.2,
            "pernapasan": 18,
            "tinggi": 165,
            "berat": 65.5
        }
    }
}
```

3. **Proses Otomatis di Service**
   - Service akan mencari ID SATUSEHAT pasien berdasarkan NIK
   - Jika tidak ditemukan, service akan mencoba mendaftarkan pasien baru
   - Jika registrasi berhasil, service akan menggunakan ID baru
   - Jika registrasi gagal, service akan mengembalikan error

#### B. Proses Pengiriman Diagnosis (Condition)
1. **Ambil ID Kunjungan SATUSEHAT**
   - Dapatkan dari response pengiriman encounter sebelumnya

2. **Kirim Data Diagnosis**
   - Gunakan endpoint: `POST /api/diagnosis`
   - Gunakan ID SATUSEHAT untuk encounter dan pasien (diambil dari encounter sebelumnya)
   - Kirim data diagnosis dalam format ICD-10

#### C. Proses Pengiriman Observasi (Observation)
1. **Ambil ID Kunjungan SATUSEHAT**
   - Dapatkan dari response pengiriman encounter sebelumnya

2. **Kirim Data Observasi**
   - Gunakan endpoint: `POST /api/observation`
   - Gunakan ID SATUSEHAT untuk encounter dan pasien (diambil dari encounter sebelumnya)
   - Kirim data observasi (tanda vital, dll) dalam format FHIR

#### D. Proses Pengiriman Prosedur (Procedure)
1. **Ambil ID Kunjungan SATUSEHAT**
   - Dapatkan dari response pengiriman encounter sebelumnya

2. **Kirim Data Prosedur**
   - Gunakan endpoint: `POST /api/procedure`
   - Gunakan ID SATUSEHAT untuk encounter dan pasien (diambil dari encounter sebelumnya)
   - Kirim data prosedur dalam format ICD-9-CM

## Implementasi Teknis

### 1. Contoh Kode untuk Framework Umum (cURL)

```php
<?php
class SatusehatIntegration {
    private $baseUrl;
    private $clinicCode;
    private $clinicSecret;
    
    public function __construct($baseUrl, $clinicCode, $clinicSecret) {
        $this->baseUrl = $baseUrl;
        $this->clinicCode = $clinicCode;
        $this->clinicSecret = $clinicSecret;
    }
    
    private function sendRequest($endpoint, $data, $method = 'POST') {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Clinic-Code: ' . $this->clinicCode,
            'X-Clinic-Secret: ' . $this->clinicSecret,
            'Content-Type: application/json',
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error: {$httpCode} - {$response}");
        }
        
        return json_decode($response, true);
    }
    
    public function getPatientId($nik) {
        return $this->sendRequest('/api/satusehat/get-patient-id', ['nik' => $nik]);
    }
    
    public function getPractitionerIdByNik($nik) {
        return $this->sendRequest('/api/satusehat/get-practitioner-id', ['nik' => $nik]);
    }
    
    public function getPractitionerIdBySipStr($sip, $str) {
        return $this->sendRequest('/api/satusehat/get-practitioner-id', [
            'sip' => $sip,
            'str' => $str
        ]);
    }
    
    public function registerPatient($patientData) {
        return $this->sendRequest('/api/satusehat/register-patient', $patientData);
    }
    
    public function sendEncounter($encounterData) {
        return $this->sendRequest('/api/encounter', $encounterData);
    }
    
    // Tambahkan method lain sesuai kebutuhan
}
```

### 2. Implementasi untuk CodeIgniter 3

```php
<?php
// application/libraries/Satusehat_lib.php
class Satusehat_lib {
    private $ci;
    private $base_url;
    private $clinic_code;
    private $clinic_secret;
    
    public function __construct() {
        $this->ci =& get_instance();
        $this->ci->config->load('satusehat');
        
        $this->base_url = $this->ci->config->item('satusehat_base_url');
        $this->clinic_code = $this->ci->config->item('satusehat_clinic_code');
        $this->clinic_secret = $this->ci->config->item('satusehat_clinic_secret');
    }
    
    private function _request($endpoint, $data, $method = 'POST') {
        $url = $this->base_url . $endpoint;
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-Clinic-Code: ' . $this->clinic_code,
                'X-Clinic-Secret: ' . $this->clinic_secret,
                'Content-Type: application/json',
            ],
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            log_message('error', 'cURL Error: ' . $error);
            return ['status' => 'error', 'message' => $error];
        }
        
        if ($http_code >= 400) {
            log_message('error', "HTTP Error: {$http_code} - {$response}");
            return ['status' => 'error', 'message' => "HTTP Error: {$http_code}", 'response' => $response];
        }
        
        return json_decode($response, true);
    }
    
    public function getPatientId($nik) {
        return $this->_request('/api/satusehat/get-patient-id', ['nik' => $nik]);
    }
    
    public function getPractitionerIdByNik($nik) {
        return $this->_request('/api/satusehat/get-practitioner-id', ['nik' => $nik]);
    }
    
    public function getPractitionerIdBySipStr($sip, $str) {
        return $this->_request('/api/satusehat/get-practitioner-id', [
            'sip' => $sip,
            'str' => $str
        ]);
    }
    
    public function registerPatient($patientData) {
        return $this->_request('/api/satusehat/register-patient', $patientData);
    }
    
    public function sendEncounter($encounterData) {
        return $this->_request('/api/encounter', $encounterData);
    }
}
```

### 3. Mapping Data ke Tabel Lokal

#### Tabel Pasien (tambahkan kolom baru):
```sql
ALTER TABLE `pasien` ADD COLUMN `satusehat_patient_id` VARCHAR(50) NULL AFTER `no_hp`;
ALTER TABLE `pasien` ADD COLUMN `satusehat_last_sync` TIMESTAMP NULL AFTER `satusehat_patient_id`;
```

#### Tabel Dokter (tambahkan kolom baru):
```sql
ALTER TABLE `dokter` ADD COLUMN `satusehat_practitioner_id` VARCHAR(50) NULL AFTER `email`;
ALTER TABLE `dokter` ADD COLUMN `satusehat_last_sync` TIMESTAMP NULL AFTER `satusehat_practitioner_id`;
```

#### Tabel Kunjungan (tambahkan kolom baru):
```sql
ALTER TABLE `kunjungan` ADD COLUMN `satusehat_encounter_id` VARCHAR(50) NULL AFTER `keluhan_utama`;
ALTER TABLE `kunjungan` ADD COLUMN `satusehat_last_sync` TIMESTAMP NULL AFTER `satusehat_encounter_id`;
```

### 4. Proses Sinkronisasi Otomatis

#### Implementasi Cron Job:
1. **Sinkronisasi Data Master (Pasien/Dokter)**
   - Jadwalkan harian untuk mengecek data baru
   - Registrasi data baru ke SATUSEHAT
   - Update ID SATUSEHAT di tabel lokal

2. **Sinkronisasi Data Klinis**
   - Jadwalkan setiap jam untuk mengecek kunjungan baru
   - Kirim data kunjungan beserta data terkait (diagnosis, observasi, prosedur)
   - Update status pengiriman di tabel lokal

## Uji Coba dan Validasi

### 1. Tahapan Uji Coba
1. **Uji Coba Unit**
   - Uji pengiriman data pasien tunggal
   - Uji pengiriman data dokter tunggal
   - Uji pengiriman data kunjungan tunggal dengan NIK pasien

2. **Uji Coba Integrasi**
   - Uji proses lengkap dari SIMKlinik ke SATUSEHAT
   - Uji penanganan error dan retry mechanism
   - Uji mapping ID SATUSEHAT ke data lokal

3. **Uji Coba Beban**
   - Uji pengiriman batch data
   - Uji performa dan kecepatan pengiriman
   - Uji penanganan timeout dan error jaringan

### 2. Validasi Data
1. **Validasi Format Data**
   - Pastikan semua field dalam format yang benar
   - Pastikan semua data wajib terisi
   - Pastikan validasi NIK, SIP/STR, dll

2. **Validasi ID SATUSEHAT**
   - Pastikan semua ID SATUSEHAT valid dan dapat digunakan
   - Pastikan mapping antara ID lokal dan SATUSEHAT akurat

### 3. Penanganan Error
1. **Error Logging**
   - Implementasikan logging untuk semua error
   - Simpan error detail untuk troubleshooting

2. **Retry Mechanism**
   - Implementasikan mekanisme retry untuk pengiriman gagal
   - Gunakan exponential backoff untuk retry

## Pemantauan dan Troubleshooting

### 1. Monitoring
- Buat dashboard untuk melihat status pengiriman data
- Tampilkan jumlah data yang berhasil/gagal dikirim
- Tampilkan log error dan solusi umum

### 2. Troubleshooting Umum
1. **Error 401 Unauthorized**
   - Periksa Clinic Code dan Clinic Secret
   - Pastikan kredensial masih aktif

2. **Error 422 Validation Error**
   - Periksa format data yang dikirim
   - Pastikan semua field wajib terisi

3. **Error 500 Internal Server Error**
   - Periksa log di SATUSEHAT Service
   - Hubungi administrator service jika diperlukan

## Best Practices

### 1. Praktik Terbaik Pengiriman Data
- Kirim data secara real-time atau batch kecil untuk pengiriman baru
- Gunakan NIK pasien untuk kemudahan integrasi
- Gunakan ID SATUSEHAT yang valid untuk dokter dan poli
- Untuk dokter, lebih baik menggunakan SIP/STR untuk pencarian
- Jika SIP/STR tidak tersedia, baru gunakan NIK sebagai alternatif
- Simpan ID SATUSEHAT di database lokal untuk penggunaan ulang
- Gunakan mapping yang konsisten antara ID lokal dan SATUSEHAT

### 2. Praktik Terbaik Keamanan
- Jangan hardcode Clinic Secret di kode sumber
- Gunakan environment variable atau file konfigurasi terenkripsi
- Pastikan koneksi ke SATUSEHAT Service menggunakan HTTPS
- Batasi akses ke endpoint hanya dari IP yang terpercaya

### 3. Praktik Terbaik Performa
- Gunakan caching untuk ID SATUSEHAT yang sering diakses
- Implementasikan queue untuk pengiriman data
- Gunakan batch processing untuk data yang banyak
- Optimalkan query database untuk pencarian data

### 4. Praktik Terbaik Pemeliharaan
- Lakukan backup data secara berkala
- Buat dokumentasi internal untuk tim SIMKlinik
- Buat SOP untuk penanganan error dan troubleshooting
- Lakukan update rutin untuk library dan framework yang digunakan