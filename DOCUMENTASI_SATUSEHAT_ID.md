# Dokumentasi Lengkap SATUSEHAT Service

## Daftar Isi
1. [Pendahuluan](#pendahuluan)
2. [Struktur Data](#struktur-data)
3. [ID SATUSEHAT - Pentingnya Penggunaan ID Global](#id-satusehat---pentingnya-penggunaan-id-global)
4. [Urutan Pengiriman Data](#urutan-pengiriman-data)
5. [Endpoint API](#endpoint-api)
6. [Contoh Penggunaan](#contoh-penggunaan)
7. [Penanganan Error](#penanganan-error)

## Pendahuluan

Layanan SATUSEHAT ini dirancang untuk mengintegrasikan data SIMKlinik dengan platform SATUSEHAT. Layanan ini mendukung beberapa klinik sekaligus, dengan setiap klinik memiliki kredensial sendiri.

### Teknologi yang Digunakan
- Laravel 9 Framework
- PHP 8.0+
- ivanwilliammd/satusehat-integration library
- FHIR R4 Standard

## Struktur Data

### 1. Data Pasien
Data yang diperlukan untuk meregistrasi pasien ke SATUSEHAT:
```php
$patientData = [
    'nik' => '3201234567890123', // Nomor KTP
    'nama' => 'Nama Lengkap Pasien',
    'tanggal_lahir' => '1990-01-01',
    'jenis_kelamin' => 'L', // 'L' atau 'P'
    'alamat' => 'Alamat lengkap pasien',
    'kode_provinsi' => '32',
    'kode_kabupaten' => '3201',
    'kode_kecamatan' => '320101',
    'kode_kelurahan' => '3201012001',
    'no_hp' => '081234567890',
    'nama_ibu' => 'Nama Ibu Kandung',
    'gol_darah' => 'A', // 'A', 'B', 'AB', 'O' atau kosongkan jika tidak tahu
    'status_nikah' => 'Belum Kawin', // 'Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'
    'pekerjaan' => 'Pegawai Swasta', // Kode pekerjaan sesuai standar SATUSEHAT
];
```

### 2. Data Dokter/Praktisi
Data yang diperlukan untuk meregistrasi dokter ke SATUSEHAT:
```php
$practitionerData = [
    'nik' => '3201234567890123', // Nomor KTP
    'nama' => 'dr. Nama Dokter',
    'sip' => '1234567890', // Nomor Surat Izin Praktik
    'str' => '0987654321', // Nomor Surat Tanda Registrasi
    'tempat_lahir' => 'Kota Lahir',
    'tanggal_lahir' => '1980-01-01',
    'jenis_kelamin' => 'L', // 'L' atau 'P'
    'alamat' => 'Alamat dokter',
    'kode_provinsi' => '32',
    'kode_kabupaten' => '3201',
    'kode_kecamatan' => '320101',
    'kode_kelurahan' => '3201012001',
    'no_hp' => '081234567890',
    'email' => 'dokter@example.com',
    'spesialis' => '101', // Kode spesialisasi sesuai standar SATUSEHAT
];
```

### 3. Data Poli/Fasilitas Kesehatan
Data yang diperlukan untuk meregistrasi poli ke SATUSEHAT:
```php
$healthcareServiceData = [
    'kode_poli' => '100001', // Kode poli dari SATUSEHAT
    'nama_poli' => 'Poli Umum',
    'deskripsi' => 'Layanan kesehatan umum',
];
```

## ID SATUSEHAT - Pentingnya Penggunaan ID Global

### Apakah ID SATUSEHAT harus digunakan?

**YA, HARUS DIGUNAKAN**. Setiap data yang dikirim ke SATUSEHAT harus menggunakan ID SATUSEHAT, bukan ID lokal SIMKlinik.

### Alasan Penggunaan ID SATUSEHAT:
1. **Integritas Data**: SATUSEHAT menggunakan ID unik untuk mengidentifikasi entitas
2. **Referensi Konsisten**: Semua data harus merujuk ke entitas yang terdaftar di SATUSEHAT
3. **Kepatuhan FHIR**: Standar FHIR mengharuskan referensi yang valid ke resource yang ada
4. **Sinkronisasi Master Data**: Menghindari duplikasi dan inkonsistensi data

### Jenis ID yang Harus Digunakan:
- **Pasien ID**: Format PXXXXXXXXXX (contoh: P00123456789)
- **Dokter ID**: ID dari Master Nakes SATUSEHAT (contoh: 10001234567)
- **Poli ID**: Kode layanan kesehatan dari SATUSEHAT (contoh: 100001)
- **Encounter ID**: ID dari kunjungan sebelumnya di SATUSEHAT

## Urutan Pengiriman Data

### Urutan yang Harus Diikuti:
1. **Registrasi Pasien** → Dapatkan `pasien_id` SATUSEHAT
2. **Registrasi Dokter/Praktisi** → Dapatkan `dokter_id` SATUSEHAT
3. **Registrasi Poli** → Dapatkan `poli_id` SATUSEHAT
4. **Kirim Kunjungan (Encounter)** → Dapatkan `encounter_id` SATUSEHAT
5. **Kirim Diagnosis (Condition)**
6. **Kirim Observasi (Observation)**
7. **Kirim Prosedur (Procedure)**

## Endpoint API

### 1. Registrasi dan Pencarian ID SATUSEHAT

#### Endpoint: `/api/satusehat/get-patient-id`
**Deskripsi**: Mendapatkan ID SATUSEHAT dari pasien berdasarkan NIK
```http
POST /api/satusehat/get-patient-id
Headers:
  X-Clinic-Code: [kode_klinik]
  X-Clinic-Secret: [secret_klinik]
  Content-Type: application/json
```

**Request Body**:
```json
{
    "nik": "3201234567890123"
}
```

**Response**:
```json
{
    "status": "success",
    "satusehat_id": "P00123456789",
    "message": "Patient ID found"
}
```

#### Endpoint: `/api/satusehat/get-practitioner-id`
**Deskripsi**: Mendapatkan ID SATUSEHAT dari dokter berdasarkan SIP/STR
```http
POST /api/satusehat/get-practitioner-id
Headers:
  X-Clinic-Code: [kode_klinik]
  X-Clinic-Secret: [secret_klinik]
  Content-Type: application/json
```

**Request Body**:
```json
{
    "sip": "1234567890",
    "str": "0987654321"
}
```

**Response**:
```json
{
    "status": "success",
    "satusehat_id": "10001234567",
    "message": "Practitioner ID found"
}
```

#### Endpoint: `/api/satusehat/get-healthcare-service-id`
**Deskripsi**: Mendapatkan ID SATUSEHAT dari poli berdasarkan kode poli
```http
POST /api/satusehat/get-healthcare-service-id
Headers:
  X-Clinic-Code: [kode_klinik]
  X-Clinic-Secret: [secret_klinik]
  Content-Type: application/json
```

**Request Body**:
```json
{
    "kode_poli": "100001"
}
```

**Response**:
```json
{
    "status": "success",
    "satusehat_id": "HCS-100001",
    "message": "Healthcare service ID found"
}
```

### 2. Registrasi Data Baru ke SATUSEHAT

#### Endpoint: `/api/satusehat/register-patient`
**Deskripsi**: Mendaftarkan pasien baru ke SATUSEHAT
```http
POST /api/satusehat/register-patient
Headers:
  X-Clinic-Code: [kode_klinik]
  X-Clinic-Secret: [secret_klinik]
  Content-Type: application/json
```

**Request Body**:
```json
{
    "nik": "3201234567890123",
    "nama": "Nama Lengkap Pasien",
    "tanggal_lahir": "1990-01-01",
    "jenis_kelamin": "L",
    "alamat": "Alamat lengkap pasien",
    "kode_provinsi": "32",
    "kode_kabupaten": "3201",
    "kode_kecamatan": "320101",
    "kode_kelurahan": "3201012001",
    "no_hp": "081234567890",
    "nama_ibu": "Nama Ibu Kandung",
    "gol_darah": "A",
    "status_nikah": "Belum Kawin",
    "pekerjaan": "Pegawai Swasta"
}
```

**Response**:
```json
{
    "status": "success",
    "id": "P00123456789",
    "message": "Patient registered successfully"
}
```

#### Endpoint: `/api/satusehat/register-practitioner`
**Deskripsi**: Mendaftarkan dokter baru ke SATUSEHAT
```http
POST /api/satusehat/register-practitioner
Headers:
  X-Clinic-Code: [kode_klinik]
  X-Clinic-Secret: [secret_klinik]
  Content-Type: application/json
```

**Request Body**:
```json
{
    "nik": "3201234567890123",
    "nama": "dr. Nama Dokter",
    "sip": "1234567890",
    "str": "0987654321",
    "tempat_lahir": "Kota Lahir",
    "tanggal_lahir": "1980-01-01",
    "jenis_kelamin": "L",
    "alamat": "Alamat dokter",
    "kode_provinsi": "32",
    "kode_kabupaten": "3201",
    "kode_kecamatan": "320101",
    "kode_kelurahan": "3201012001",
    "no_hp": "081234567890",
    "email": "dokter@example.com",
    "spesialis": "101"
}
```

**Response**:
```json
{
    "status": "success",
    "id": "10001234567",
    "message": "Practitioner registered successfully"
}
```

### 3. Kirim Data Klinis

#### Endpoint: `/api/encounter`
**Deskripsi**: Kirim data kunjungan ke SATUSEHAT
```http
POST /api/encounter
Headers:
  X-Clinic-Code: [kode_klinik]
  X-Clinic-Secret: [secret_klinik]
  Content-Type: application/json
```

**Request Body**:
```json
{
    "pasien_id": "P00123456789", // ID pasien dari SATUSEHAT
    "tanggal_kunjungan": "2024-01-15T08:30:00Z",
    "tanggal_selesai": "2024-01-15T09:15:00Z",
    "jenis_layanan": "101",
    "jenis_kunjungan": "1",
    "poli": "100001", // ID poli dari SATUSEHAT
    "dokter": "10001234567", // ID dokter dari SATUSEHAT
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

#### Endpoint: `/api/diagnosis`
**Deskripsi**: Kirim data diagnosis ke SATUSEHAT
```http
POST /api/diagnosis
Headers:
  X-Clinic-Code: [kode_klinik]
  X-Clinic-Secret: [secret_klinik]
  Content-Type: application/json
```

**Request Body**:
```json
{
    "encounter_id": "encounter-12345", // ID encounter dari SATUSEHAT
    "pasien_id": "P00123456789", // ID pasien dari SATUSEHAT
    "tanggal_diagnosis": "2024-01-15T09:00:00Z",
    "kode_icd10": "I10",
    "deskripsi_diagnosis": "Hipertensi esensial",
    "kategori": [
        {
            "coding": [
                {
                    "system": "http://terminology.hl7.org/CodeSystem/condition-category",
                    "code": "encounter-diagnosis",
                    "display": "Encounter Diagnosis"
                }
            ]
        }
    ],
    "klinis_status": "active",
    "verifikasi_status": "confirmed",
    "onset_date": "2024-01-15T09:00:00Z",
    "keterangan": "Diagnosis primer berdasarkan hasil pemeriksaan",
    "tingkat_keparahan": {
        "coding": [
            {
                "system": "http://snomed.info/sct",
                "code": "24484000",
                "display": "Severe"
            }
        ],
        "text": "Berat"
    }
}
```

#### Endpoint: `/api/observation`
**Deskripsi**: Kirim data observasi ke SATUSEHAT
```http
POST /api/observation
Headers:
  X-Clinic-Code: [kode_klinik]
  X-Clinic-Secret: [secret_klinik]
  Content-Type: application/json
```

**Request Body**:
```json
{
    "encounter_id": "encounter-12345", // ID encounter dari SATUSEHAT
    "pasien_id": "P00123456789", // ID pasien dari SATUSEHAT
    "tanggal_observasi": "2024-01-15T08:35:00Z",
    "kategori": {
        "system": "http://terminology.hl7.org/CodeSystem/observation-category",
        "code": "vital-signs",
        "display": "Vital Signs"
    },
    "kode": {
        "coding": [
            {
                "system": "http://loinc.org",
                "code": "85354-9",
                "display": "Blood pressure panel with all children optional"
            }
        ],
        "text": "Blood pressure systolic and diastolic"
    },
    "nilai": {
        "value": 130,
        "unit": "mmHg",
        "system": "http://unitsofmeasure.org",
        "code": "mm[Hg]"
    },
    "nilai_diatas": {
        "value": 85,
        "unit": "mmHg",
        "system": "http://unitsofmeasure.org",
        "code": "mm[Hg]"
    },
    "interpretasi": "Normal",
    "keterangan": "Tekanan darah dalam batas normal"
}
```

#### Endpoint: `/api/procedure`
**Deskripsi**: Kirim data prosedur ke SATUSEHAT
```http
POST /api/procedure
Headers:
  X-Clinic-Code: [kode_klinik]
  X-Clinic-Secret: [secret_klinik]
  Content-Type: application/json
```

**Request Body**:
```json
{
    "encounter_id": "encounter-12345", // ID encounter dari SATUSEHAT
    "pasien_id": "P00123456789", // ID pasien dari SATUSEHAT
    "tanggal_prosedur": "2024-01-15T08:45:00Z",
    "kode_prosedur": "17.1",
    "deskripsi_prosedur": "Insisi dan ekstraksi dari kista atau abses",
    "kode_metode": "3951000132103",
    "deskripsi_metode": "Metode insesi",
    "kode_alat": "27724004",
    "deskripsi_alat": "Scalpel",
    "kondisi_klinis": "Abses pada lengan kanan",
    "komplikasi": "Tidak ada",
    "hasil": "Prosedur berhasil, tidak ada komplikasi"
}
```

## Contoh Penggunaan

### Contoh Lengkap Pengiriman Kunjungan

```php
<?php
// 1. Cek apakah pasien sudah terdaftar di SATUSEHAT
$patientId = kirimRequest('POST', '/api/satusehat/get-patient-id', [
    'nik' => '3201234567890123'
]);

if (!$patientId) {
    // 2. Jika belum terdaftar, registrasi pasien baru
    $patientRegistration = kirimRequest('POST', '/api/satusehat/register-patient', [
        'nik' => '3201234567890123',
        'nama' => 'Nama Lengkap Pasien',
        // ... data pasien lainnya
    ]);
    $patientId = $patientRegistration['id'];
}

// 3. Cek apakah dokter sudah terdaftar di SATUSEHAT
$practitionerId = kirimRequest('POST', '/api/satusehat/get-practitioner-id', [
    'sip' => '1234567890'
]);

if (!$practitionerId) {
    // 4. Jika belum terdaftar, registrasi dokter baru
    $practitionerRegistration = kirimRequest('POST', '/api/satusehat/register-practitioner', [
        'nik' => '3201234567890123',
        'nama' => 'dr. Nama Dokter',
        'sip' => '1234567890',
        // ... data dokter lainnya
    ]);
    $practitionerId = $practitionerRegistration['id'];
}

// 5. Kirim data kunjungan
$encounterResponse = kirimRequest('POST', '/api/encounter', [
    'pasien_id' => $patientId, // ID SATUSEHAT
    'tanggal_kunjungan' => '2024-01-15T08:30:00Z',
    'jenis_layanan' => '101',
    'jenis_kunjungan' => '1',
    'poli' => '100001', // ID SATUSEHAT
    'dokter' => $practitionerId, // ID SATUSEHAT
    'keluhan_utama' => 'Sakit kepala'
]);

$encounterId = $encounterResponse['id'];

// 6. Kirim data diagnosis
kirimRequest('POST', '/api/diagnosis', [
    'encounter_id' => $encounterId, // ID SATUSEHAT
    'pasien_id' => $patientId, // ID SATUSEHAT
    'tanggal_diagnosis' => '2024-01-15T09:00:00Z',
    'kode_icd10' => 'I10',
    'deskripsi_diagnosis' => 'Hipertensi esensial'
]);
```

## Penanganan Error

### Kode Status HTTP
- `200`: Permintaan berhasil
- `202`: Data diterima dan diproses (Accepted)
- `400`: Permintaan tidak valid (Bad Request)
- `401`: Tidak diotentikasi (Unauthorized)
- `403`: Tidak diotorisasi (Forbidden)
- `422`: Data tidak valid (Unprocessable Entity)
- `500`: Kesalahan server (Internal Server Error)

### Contoh Error Response
```json
{
    "status": "error",
    "message": "Patient not found in SATUSEHAT",
    "code": "PATIENT_NOT_FOUND"
}
```

### Kode Error Umum
- `PATIENT_NOT_FOUND`: Pasien tidak ditemukan di SATUSEHAT
- `PRACTITIONER_NOT_FOUND`: Dokter tidak ditemukan di SATUSEHAT
- `HEALTHCARE_SERVICE_NOT_FOUND`: Poli tidak ditemukan di SATUSEHAT
- `INVALID_CREDENTIALS`: Kredensial klinik tidak valid
- `MISSING_REQUIRED_FIELDS`: Data yang dikirim tidak lengkap
- `FHIR_VALIDATION_ERROR`: Data tidak sesuai standar FHIR