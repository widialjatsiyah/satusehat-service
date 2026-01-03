# Dokumentasi Pengiriman Data dari SIMKlinik ke SATUSEHAT Service

## Panduan Umum

Layanan SATUSEHAT ini dirancang untuk menerima data dari berbagai klinik (SIMKlinik) dan mengirimkannya ke platform SATUSEHAT. Setiap klinik harus memiliki kode unik yang digunakan untuk otentikasi dan identifikasi dalam sistem.

## Konfigurasi Klinik

Sebelum mengirim data, pastikan klinik Anda telah terdaftar di sistem dengan informasi berikut:
- `code`: Kode unik klinik (misal: KLINIK-001)
- `name`: Nama klinik
- `satusehat_client_id`: Client ID dari SATUSEHAT
- `satusehat_client_secret`: Client Secret dari SATUSEHAT
- `organization_id`: ID Organisasi di SATUSEHAT

## Format Otentikasi

Setiap permintaan ke layanan SATUSEHAT harus menyertakan header otentikasi klinik:

```
X-Clinic-Code: [kode_klinik]
X-Clinic-Secret: [secret_klinik]
```

## Endpoint API

Semua endpoint menggunakan format dasar: `https://[domain-service]/api/`

## 1. Pengiriman Data Kunjungan (Encounter)

### Endpoint
```
POST /api/encounter
```

### Header
```
X-Clinic-Code: [kode_klinik]
X-Clinic-Secret: [secret_klinik]
Content-Type: application/json
```

### Request Body
```json
{
    "id": "encounter-id-optional",
    "pasien_id": "patient-id-from-satusehat",
    "tanggal_kunjungan": "2024-01-01T10:00:00Z",
    "tanggal_selesai": "2024-01-01T11:00:00Z",
    "jenis_layanan": "101", 
    "jenis_kunjungan": "1",
    "poli": "100001",
    "dokter": "practitioner-id-from-satusehat",
    "penjamin": "1",
    "keluhan_utama": "Sakit kepala",
    "anamnesa": "Pasien datang dengan keluhan...",
    "pemeriksaan_fisik": {
        "tanda_vital": {
            "tekanan_darah": "120/80",
            "nadi": 80,
            "suhu": 36.5,
            "pernapasan": 20,
            "tinggi": 170,
            "berat": 70
        }
    }
}
```

### Deskripsi Field
- `pasien_id`: ID pasien dari SATUSEHAT
- `tanggal_kunjungan`: Tanggal dan waktu kunjungan dalam format ISO 8601
- `tanggal_selesai`: Tanggal dan waktu selesai kunjungan
- `jenis_layanan`: Kode jenis layanan (101=Rawat Jalan, 102=Rawat Inap, dll)
- `jenis_kunjungan`: Kode jenis kunjungan (1=Kunjungan Baru, 2=Kunjungan Lama)
- `poli`: Kode poli dari SATUSEHAT
- `dokter`: ID dokter/praktisi dari SATUSEHAT
- `penjamin`: Kode penjamin pembayaran
- `keluhan_utama`: Keluhan utama pasien
- `anamnesa`: Riwayat penyakit dan informasi klinis tambahan
- `pemeriksaan_fisik`: Data pemeriksaan fisik termasuk tanda vital

## 2. Pengiriman Data Prosedur (Procedure)

### Endpoint
```
POST /api/procedure
```

### Header
```
X-Clinic-Code: [kode_klinik]
X-Clinic-Secret: [secret_klinik]
Content-Type: application/json
```

### Request Body
```json
{
    "id": "procedure-id-optional",
    "encounter_id": "encounter-id-from-satusehat",
    "pasien_id": "patient-id-from-satusehat",
    "tanggal_prosedur": "2024-01-01T10:30:00Z",
    "kode_prosedur": "17.1",
    "deskripsi_prosedur": "Insisi dan ekstraksi dari kista atau abses",
    "kode_metode": "3951000132103",
    "deskripsi_metode": "Metode insisi",
    "kode_alat": "27724004",
    "deskripsi_alat": "Scalpel",
    "kondisi_klinis": "Abses pada lengan",
    "komplikasi": "Tidak ada",
    "hasil": "Prosedur berhasil, tidak ada komplikasi"
}
```

### Deskripsi Field
- `encounter_id`: ID kunjungan dari SATUSEHAT
- `pasien_id`: ID pasien dari SATUSEHAT
- `tanggal_prosedur`: Tanggal dan waktu prosedur dalam format ISO 8601
- `kode_prosedur`: Kode prosedur berdasarkan ICD-9-CM atau standar SATUSEHAT
- `deskripsi_prosedur`: Deskripsi dari prosedur yang dilakukan
- `kode_metode`: Kode metode prosedur
- `deskripsi_metode`: Deskripsi metode yang digunakan
- `kode_alat`: Kode alat yang digunakan dalam prosedur
- `deskripsi_alat`: Deskripsi alat yang digunakan
- `kondisi_klinis`: Kondisi klinis yang menjadi indikasi prosedur
- `komplikasi`: Komplikasi yang terjadi (jika ada)
- `hasil`: Hasil dari prosedur

## 3. Pengiriman Data Observasi (Observation)

### Endpoint
```
POST /api/observation
```

### Header
```
X-Clinic-Code: [kode_klinik]
X-Clinic-Secret: [secret_klinik]
Content-Type: application/json
```

### Request Body
```json
{
    "id": "observation-id-optional",
    "encounter_id": "encounter-id-from-satusehat",
    "pasien_id": "patient-id-from-satusehat",
    "tanggal_observasi": "2024-01-01T10:15:00Z",
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
        "value": 120,
        "unit": "mmHg",
        "system": "http://unitsofmeasure.org",
        "code": "mm[Hg]"
    },
    "nilai_diatas": {
        "value": 80,
        "unit": "mmHg",
        "system": "http://unitsofmeasure.org",
        "code": "mm[Hg]"
    },
    "interpretasi": "Normal",
    "keterangan": "Tekanan darah dalam batas normal"
}
```

### Deskripsi Field
- `encounter_id`: ID kunjungan dari SATUSEHAT
- `pasien_id`: ID pasien dari SATUSEHAT
- `tanggal_observasi`: Tanggal dan waktu observasi dalam format ISO 8601
- `kategori`: Kategori observasi (vital-signs, laboratory, dll)
- `kode`: Kode dan deskripsi observasi (menggunakan LOINC atau standar lainnya)
- `nilai`: Nilai observasi utama (sistolik dalam contoh tekanan darah)
- `nilai_diatas`: Nilai observasi tambahan (diastolik dalam contoh tekanan darah)
- `interpretasi`: Interpretasi hasil (Normal, Abnormal, dll)
- `keterangan`: Keterangan tambahan tentang observasi

## 4. Pengiriman Data Diagnosis (Condition)

### Endpoint
```
POST /api/diagnosis
```

### Header
```
X-Clinic-Code: [kode_klinik]
X-Clinic-Secret: [secret_klinik]
Content-Type: application/json
```

### Request Body
```json
{
    "id": "condition-id-optional",
    "encounter_id": "encounter-id-from-satusehat",
    "pasien_id": "patient-id-from-satusehat",
    "tanggal_diagnosis": "2024-01-01T10:45:00Z",
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
    "onset_date": "2024-01-01T10:45:00Z",
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

### Deskripsi Field
- `encounter_id`: ID kunjungan dari SATUSEHAT
- `pasien_id`: ID pasien dari SATUSEHAT
- `tanggal_diagnosis`: Tanggal dan waktu diagnosis dalam format ISO 8601
- `kode_icd10`: Kode diagnosis berdasarkan ICD-10
- `deskripsi_diagnosis`: Deskripsi dari diagnosis
- `kategori`: Kategori diagnosis (encounter-diagnosis, problem-list-item, dll)
- `klinis_status`: Status klinis (active, inactive, recurrence, dll)
- `verifikasi_status`: Status verifikasi (confirmed, unconfirmed, provisional, dll)
- `onset_date`: Tanggal mulai kondisi
- `keterangan`: Keterangan tambahan tentang diagnosis
- `tingkat_keparahan`: Tingkat keparahan kondisi

## Contoh Implementasi di SIMKlinik

### Contoh Pengiriman Kunjungan
```php
// Dari sistem SIMKlinik
$kunjunganData = [
    'pasien_id' => $pasienSatuSehatId,
    'tanggal_kunjungan' => $kunjungan->tanggal_berobat,
    'tanggal_selesai' => $kunjungan->tanggal_selesai,
    'jenis_layanan' => $kunjungan->jenis_layanan,
    'jenis_kunjungan' => $kunjungan->jenis_kunjungan,
    'poli' => $kunjungan->kode_poli,
    'dokter' => $dokterSatuSehatId,
    'penjamin' => $kunjungan->kode_penjamin,
    'keluhan_utama' => $kunjungan->keluhan_utama,
    'anamnesa' => $kunjungan->anamnesa,
    'pemeriksaan_fisik' => [
        'tanda_vital' => [
            'tekanan_darah' => $kunjungan->tekanan_darah,
            'nadi' => $kunjungan->nadi,
            'suhu' => $kunjungan->suhu,
            'pernapasan' => $kunjungan->pernapasan,
            'tinggi' => $kunjungan->tinggi,
            'berat' => $kunjungan->berat
        ]
    ]
];

// Kirim ke SATUSEHAT service
$response = Http::withHeaders([
    'X-Clinic-Code' => $clinicCode,
    'X-Clinic-Secret' => $clinicSecret,
])->post('https://[domain-service]/api/encounter', $kunjunganData);
```

### Contoh Pengiriman Diagnosis
```php
// Dari sistem SIMKlinik
$diagnosisData = [
    'encounter_id' => $encounterSatuSehatId,
    'pasien_id' => $pasienSatuSehatId,
    'tanggal_diagnosis' => $diagnosis->tanggal_diagnosis,
    'kode_icd10' => $diagnosis->kode_icd10,
    'deskripsi_diagnosis' => $diagnosis->deskripsi,
    'kategori' => [
        [
            'coding' => [
                [
                    'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                    'code' => 'encounter-diagnosis',
                    'display' => 'Encounter Diagnosis'
                ]
            ]
        ]
    ],
    'klinis_status' => 'active',
    'verifikasi_status' => 'confirmed',
    'onset_date' => $diagnosis->tanggal_diagnosis,
    'keterangan' => $diagnosis->keterangan
];

// Kirim ke SATUSEHAT service
$response = Http::withHeaders([
    'X-Clinic-Code' => $clinicCode,
    'X-Clinic-Secret' => $clinicSecret,
])->post('https://[domain-service]/api/diagnosis', $diagnosisData);
```

## Penanganan Error dan Status

### Response Format
```json
{
    "status": "queued|success|error",
    "log_id": "log-id-untuk-tracing",
    "message": "optional message",
    "data": {}
}
```

### Kode Status HTTP
- `202`: Request diterima dan diproses (Accepted)
- `400`: Request tidak valid (Bad Request)
- `401`: Tidak diotentikasi (Unauthorized)
- `403`: Tidak diotorisasi (Forbidden)
- `422`: Data tidak valid (Unprocessable Entity)
- `500`: Kesalahan server (Internal Server Error)

## Penanganan Kesalahan dan Retry

Layanan ini memiliki mekanisme retry otomatis untuk permintaan yang gagal. Namun, untuk pengiriman data penting, SIMKlinik sebaiknya:

1. Menyimpan status pengiriman lokal
2. Mengecek status pengiriman secara berkala
3. Melakukan retry manual jika diperlukan
4. Mencatat log kesalahan untuk ditindaklanjuti

## Tips Implementasi

1. Pastikan data pasien sudah dikirim dan terdaftar di SATUSEHAT sebelum mengirim data kunjungan
2. Gunakan ID unik dari SATUSEHAT untuk referensi (bukan ID lokal)
3. Simpan response dari setiap pengiriman untuk keperluan tracking
4. Gunakan endpoint staging untuk pengujian sebelum ke production
5. Pastikan semua data yang dikirim dalam format yang sesuai standar FHIR
6. Gunakan endpoint `/satusehat/token/{clinicCode}` untuk menguji otentikasi klinik

## Testing Endpoint

Untuk keperluan pengujian:
- Endpoint token: `GET /api/satusehat/token/{clinicCode}`
- Endpoint uji pasien: `POST /api/satusehat/test-patient/{clinicCode}`