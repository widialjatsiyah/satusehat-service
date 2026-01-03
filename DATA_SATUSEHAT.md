# Panduan ID SATUSEHAT dan Data yang Dikirim ke Layanan

## Pentingnya ID SATUSEHAT

### Apakah ID SATUSEHAT harus digunakan?

**Tergantung pada jenis entitas**:

1. **Pasien ID (`nik_pasien`)**: Harus menggunakan NIK pasien (format: 16-digit angka) yang akan digunakan oleh SATUSEHAT untuk mencari atau membuat ID SATUSEHAT
2. **Dokter ID (`dokter`)**: Harus menggunakan ID SATUSEHAT dari Praktisi (format: biasanya nomor SIP/STR) yang terdaftar di SATUSEHAT
3. **Poli ID (`poli`)**: Harus menggunakan kode layanan kesehatan (Healthcare Service) dari SATUSEHAT
4. **Encounter ID (`encounter_id`)**: Jika merujuk ke encounter sebelumnya, harus menggunakan ID encounter dari SATUSEHAT

### Alasan Mengapa ID SATUSEHAT Diperlukan

1. **Integritas Data**: SATUSEHAT menggunakan ID unik untuk mengidentifikasi entitas (pasien, dokter, fasilitas)
2. **Referensi Konsisten**: Semua data harus merujuk ke entitas yang terdaftar di SATUSEHAT
3. **Kepatuhan FHIR**: Standar FHIR mengharuskan referensi yang valid ke resource yang ada
4. **Sinkronisasi Master Data**: Menghindari duplikasi dan inkonsistensi data

## Data yang Harus Dikirimkan ke Layanan

### 1. Data Kunjungan (akan dikirim ke endpoint `/api/encounter`)

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

### 2. Data Dokter/Praktisi (akan dikirim ke endpoint `/api/practitioner`)

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

### 3. Data Fasilitas Kesehatan (akan dikirim ke endpoint `/api/healthcare_service` atau `/api/organization`)

```php
$healthcareServiceData = [
    'kode_poli' => '100001', // Kode poli dari SATUSEHAT
    'nama_poli' => 'Poli Umum',
    'deskripsi' => 'Layanan kesehatan umum',
];
```

### 2. Data Kunjungan (akan dikirim ke endpoint `/api/encounter`)

```php
$encounterData = [
    'id' => 'encounter-id-optional', // ID encounter dari SATUSEHAT (jika update)
    'nik_pasien' => '3201234567890123', // NIK pasien (bukan ID SATUSEHAT) (WAJIB)
    'tanggal_kunjungan' => '2024-01-15T08:30:00Z', // Format ISO 8601
    'tanggal_selesai' => '2024-01-15T09:15:00Z',
    'jenis_layanan' => '101', // 101=Rawat Jalan, 102=Rawat Inap, dll
    'jenis_kunjungan' => '1', // 1=Kunjungan Baru, 2=Kunjungan Lama
    'poli' => '100001', // Kode poli dari SATUSEHAT (WAJIB)
    'dokter' => '10001234567', // ID dokter dari SATUSEHAT (WAJIB)
    'penjamin' => '1', // Kode penjamin pembayaran
    'keluhan_utama' => 'Sakit kepala berat sejak 3 hari lalu',
    'anamnesa' => 'Pasien datang dengan keluhan sakit kepala berat...',
    'pemeriksaan_fisik' => [
        'tanda_vital' => [
            'tekanan_darah' => '130/85',
            'nadi' => 82,
            'suhu' => 37.2,
            'pernapasan' => 18,
            'tinggi' => 165,
            'berat' => 65.5,
            'lingkar_perut' => 80
        ]
    ]
];
```

### 5. Data Prosedur Medis (akan dikirim ke endpoint `/api/procedure`)

```php
$procedureData = [
    'id' => 'procedure-id-optional', // ID procedure dari SATUSEHAT (jika update)
    'encounter_id' => 'encounter-12345', // ID encounter dari SATUSEHAT (WAJIB)
    'pasien_id' => 'P001234567890', // ID pasien dari SATUSEHAT (WAJIB)
    'tanggal_prosedur' => '2024-01-15T08:45:00Z', // Format ISO 8601
    'kode_prosedur' => '17.1', // Kode prosedur ICD-9-CM
    'deskripsi_prosedur' => 'Insisi dan ekstraksi dari kista atau abses',
    'kode_metode' => '3951000132103',
    'deskripsi_metode' => 'Metode insisi',
    'kode_alat' => '27724004',
    'deskripsi_alat' => 'Scalpel',
    'kondisi_klinis' => 'Abses pada lengan kanan',
    'komplikasi' => 'Tidak ada',
    'hasil' => 'Prosedur berhasil, tidak ada komplikasi'
];
```

### 6. Data Observasi (akan dikirim ke endpoint `/api/observation`)

```php
$observationData = [
    'id' => 'observation-id-optional', // ID observation dari SATUSEHAT (jika update)
    'encounter_id' => 'encounter-12345', // ID encounter dari SATUSEHAT (WAJIB)
    'pasien_id' => 'P001234567890', // ID pasien dari SATUSEHAT (WAJIB)
    'tanggal_observasi' => '2024-01-15T08:35:00Z', // Format ISO 8601
    'kategori' => [
        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
        'code' => 'vital-signs',
        'display' => 'Vital Signs'
    ],
    'kode' => [
        'coding' => [
            [
                'system' => 'http://loinc.org',
                'code' => '85354-9',
                'display' => 'Blood pressure panel with all children optional'
            ]
        ],
        'text' => 'Blood pressure systolic and diastolic'
    ],
    'nilai' => [
        'value' => 130,
        'unit' => 'mmHg',
        'system' => 'http://unitsofmeasure.org',
        'code' => 'mm[Hg]'
    ],
    'nilai_diatas' => [
        'value' => 85,
        'unit' => 'mmHg',
        'system' => 'http://unitsofmeasure.org',
        'code' => 'mm[Hg]'
    ],
    'interpretasi' => 'Normal',
    'keterangan' => 'Tekanan darah dalam batas normal'
];
```

### 3. Data Diagnosis (akan dikirim ke endpoint `/api/diagnosis`)

```php
$conditionData = [
    'id' => 'condition-id-optional', // ID condition dari SATUSEHAT (jika update)
    'encounter_id' => 'encounter-12345', // ID encounter dari SATUSEHAT (WAJIB)
    'pasien_id' => 'P001234567890', // ID pasien dari SATUSEHAT (diambil dari encounter)
    'tanggal_diagnosis' => '2024-01-15T09:00:00Z', // Format ISO 8601
    'kode_icd10' => 'I10', // Kode diagnosis ICD-10
    'deskripsi_diagnosis' => 'Hipertensi esensial',
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
    'klinis_status' => 'active', // active, inactive, recurrence, remission, resolved
    'verifikasi_status' => 'confirmed', // unconfirmed, provisional, differential, confirmed, refuted, entered-in-error
    'onset_date' => '2024-01-15T09:00:00Z',
    'keterangan' => 'Diagnosis primer berdasarkan hasil pemeriksaan',
    'tingkat_keparahan' => [
        'coding' => [
            [
                'system' => 'http://snomed.info/sct',
                'code' => '24484000',
                'display' => 'Severe'
            ]
        ],
        'text' => 'Berat'
    ]
];
```

## Proses Registrasi Data ke SATUSEHAT

### Urutan yang Harus Diikuti:

1. **Registrasi Dokter/Praktisi** → Dapatkan `dokter_id` SATUSEHAT
2. **Registrasi Poli** → Dapatkan `poli_id` SATUSEHAT
4. **Kirim Kunjungan (Encounter)** → Dapatkan `encounter_id` SATUSEHAT
5. **Kirim Diagnosis (Condition)**
6. **Kirim Observasi (Observation)**
7. **Kirim Prosedur (Procedure)**

### Contoh Implementasi Proses Lengkap:

```php
<?php
// Contoh fungsi untuk mengirim kunjungan lengkap ke SATUSEHAT
function kirimKunjunganLengkap($baseUrl, $clinicCode, $clinicSecret, $kunjunganData) {
    $client = new SatusehatHelper($baseUrl, $clinicCode, $clinicSecret);
    
    try {
        // 1. Kirim dokter jika belum terdaftar di SATUSEHAT
        if (!cekDokterTerkirim($kunjunganData['dokter_sip'])) {
            $practitionerData = [
                'nik' => $kunjunganData['dokter_nik'],
                'nama' => $kunjunganData['dokter_nama'],
                'sip' => $kunjunganData['dokter_sip'],
                'str' => $kunjunganData['dokter_str'],
                // ... data dokter lainnya
            ];
            
            $practitionerResponse = $client->kirimPractitioner($practitionerData);
            $dokterId = $practitionerResponse['id']; // Dapatkan ID SATUSEHAT
        } else {
            $dokterId = getIdSatuSehatDokter($kunjunganData['dokter_sip']);
        }
        
        // 3. Kirim encounter
        $encounterData = [
            'pasien_id' => $pasienId, // ID SATUSEHAT
            'tanggal_kunjungan' => $kunjunganData['tanggal_kunjungan'],
            'jenis_layanan' => $kunjunganData['jenis_layanan'],
            'jenis_kunjungan' => $kunjunganData['jenis_kunjungan'],
            'poli' => $kunjunganData['kode_poli'], // Kode poli SATUSEHAT
            'dokter' => $dokterId, // ID SATUSEHAT
            'keluhan_utama' => $kunjunganData['keluhan_utama']
        ];
        
        $encounterResponse = $client->kirimEncounter($encounterData);
        $encounterId = $encounterResponse['id']; // Dapatkan ID SATUSEHAT
        
        // 4. Kirim diagnosis
        if (isset($kunjunganData['diagnosis'])) {
            $conditionData = [
                'encounter_id' => $encounterId, // ID SATUSEHAT
                'pasien_id' => $pasienId, // ID SATUSEHAT (diambil dari encounter)
                'tanggal_diagnosis' => $kunjunganData['tanggal_kunjungan'],
                'kode_icd10' => $kunjunganData['diagnosis']['kode'],
                'deskripsi_diagnosis' => $kunjunganData['diagnosis']['deskripsi']
            ];
            
            $client->kirimDiagnosis($conditionData);
        }
        
        // 5. Kirim observasi
        if (isset($kunjunganData['observasi'])) {
            $observationData = [
                'encounter_id' => $encounterId, // ID SATUSEHAT
                'pasien_id' => $pasienId, // ID SATUSEHAT
                'tanggal_observasi' => $kunjunganData['tanggal_kunjungan'],
                'kategori' => [
                    'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                    'code' => 'vital-signs',
                    'display' => 'Vital Signs'
                ],
                // ... data observasi
            ];
            
            $client->kirimObservation($observationData);
        }
        
        return [
            'status' => 'success',
            'pasien_id' => $pasienId,
            'dokter_id' => $dokterId,
            'encounter_id' => $encounterId
        ];
    } catch (Exception $e) {
        error_log("Error mengirim kunjungan ke SATUSEHAT: " . $e->getMessage());
        throw $e;
    }
}
```

## Kesimpulan

Penting untuk diingat bahwa:
1. **Semua ID harus merupakan ID SATUSEHAT** yang valid
2. **Proses harus diurutkan** sesuai alur SATUSEHAT
3. **Data harus sesuai standar FHIR** dan format SATUSEHAT
4. **Harus ada pengecekan duplikasi** sebelum mengirim data
5. **Harus ada mekanisme penyimpanan ID SATUSEHAT** untuk penggunaan ulang

Dengan mengikuti panduan ini, SIMKlinik dapat mengintegrasikan data ke SATUSEHAT secara efektif dan sesuai standar.