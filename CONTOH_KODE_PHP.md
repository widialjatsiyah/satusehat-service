# Contoh Kode PHP untuk Mengirim Data ke SATUSEHAT Service

## Persiapan

Sebelum mengirim data, pastikan Anda telah:
1. Menginstal cURL atau Guzzle HTTP client di PHP Anda
2. Mendapatkan kode klinik dan secret dari sistem
3. Menginstal dan mengkonfigurasi library yang diperlukan

## Instalasi Dependencies

Jika Anda menggunakan Guzzle (disarankan):
```bash
composer require guzzlehttp/guzzle
```

Atau jika Anda ingin menggunakan cURL bawaan PHP, tidak perlu instalasi tambahan.

## Contoh Kode PHP Lengkap

### 1. Kelas Helper untuk Koneksi ke SATUSEHAT Service

```php
<?php

class SatuSehatApiClient
{
    private $baseUrl;
    private $clinicCode;
    private $clinicSecret;
    
    public function __construct($baseUrl, $clinicCode, $clinicSecret)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->clinicCode = $clinicCode;
        $this->clinicSecret = $clinicSecret;
    }
    
    private function sendRequest($endpoint, $data, $method = 'POST')
    {
        $client = new \GuzzleHttp\Client();
        
        try {
            $response = $client->request($method, $this->baseUrl . $endpoint, [
                'headers' => [
                    'X-Clinic-Code' => $this->clinicCode,
                    'X-Clinic-Secret' => $this->clinicSecret,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            $errorMessage = $response ? $response->getBody()->getContents() : $e->getMessage();
            throw new Exception("Error: " . $errorMessage);
        }
    }
    
    // Method untuk mengirim data kunjungan
    public function kirimEncounter($data)
    {
        return $this->sendRequest('/api/encounter', $data);
    }
    
    // Method untuk mengirim data prosedur
    public function kirimProcedure($data)
    {
        return $this->sendRequest('/api/procedure', $data);
    }
    
    // Method untuk mengirim data observasi
    public function kirimObservation($data)
    {
        return $this->sendRequest('/api/observation', $data);
    }
    
    // Method untuk mengirim data diagnosis
    public function kirimDiagnosis($data)
    {
        return $this->sendRequest('/api/diagnosis', $data);
    }
}
```

### 2. Contoh Pengiriman Data Kunjungan (Encounter)

```php
<?php
require_once 'vendor/autoload.php'; // Jika menggunakan Composer

// Konfigurasi
$baseUrl = 'https://your-satusehat-service.com';
$clinicCode = 'KLINIK-001';
$clinicSecret = 'your_clinic_secret_here';

// Inisialisasi klien
$client = new SatuSehatApiClient($baseUrl, $clinicCode, $clinicSecret);

// Data kunjungan dari SIMKlinik
$encounterData = [
    'pasien_id' => 'P001234567890', // ID pasien dari SATUSEHAT
    'tanggal_kunjungan' => '2024-01-15T08:30:00Z',
    'tanggal_selesai' => '2024-01-15T09:15:00Z',
    'jenis_layanan' => '101', // 101=Rawat Jalan
    'jenis_kunjungan' => '1', // 1=Kunjungan Baru
    'poli' => '100001', // Kode poli dari SATUSEHAT
    'dokter' => '10001234567', // ID dokter dari SATUSEHAT
    'penjamin' => '1', // Kode penjamin
    'keluhan_utama' => 'Sakit kepala berat sejak 3 hari lalu',
    'anamnesa' => 'Pasien datang dengan keluhan sakit kepala berat...',
    'pemeriksaan_fisik' => [
        'tanda_vital' => [
            'tekanan_darah' => '130/85',
            'nadi' => 82,
            'suhu' => 37.2,
            'pernapasan' => 18,
            'tinggi' => 165,
            'berat' => 65.5
        ]
    ]
];

try {
    $response = $client->kirimEncounter($encounterData);
    echo "Encounter berhasil dikirim: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error mengirim encounter: " . $e->getMessage() . "\n";
}
```

### 3. Contoh Pengiriman Data Prosedur Medis

```php
<?php
// ... (inisialisasi klien seperti di atas)

$procedureData = [
    'encounter_id' => 'encounter-12345', // ID kunjungan dari SATUSEHAT
    'pasien_id' => 'P001234567890',
    'tanggal_prosedur' => '2024-01-15T08:45:00Z',
    'kode_prosedur' => '17.1',
    'deskripsi_prosedur' => 'Insisi dan ekstraksi dari kista atau abses',
    'kode_metode' => '3951000132103',
    'deskripsi_metode' => 'Metode insesi',
    'kode_alat' => '27724004',
    'deskripsi_alat' => 'Scalpel',
    'kondisi_klinis' => 'Abses pada lengan kanan',
    'komplikasi' => 'Tidak ada',
    'hasil' => 'Prosedur berhasil, tidak ada komplikasi'
];

try {
    $response = $client->kirimProcedure($procedureData);
    echo "Procedure berhasil dikirim: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error mengirim procedure: " . $e->getMessage() . "\n";
}
```

### 4. Contoh Pengiriman Data Observasi

```php
<?php
// ... (inisialisasi klien seperti di atas)

$observationData = [
    'encounter_id' => 'encounter-12345',
    'pasien_id' => 'P001234567890',
    'tanggal_observasi' => '2024-01-15T08:35:00Z',
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

try {
    $response = $client->kirimObservation($observationData);
    echo "Observation berhasil dikirim: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error mengirim observation: " . $e->getMessage() . "\n";
}
```

### 5. Contoh Pengiriman Data Diagnosis (Condition)

```php
<?php
// ... (inisialisasi klien seperti di atas)

$conditionData = [
    'encounter_id' => 'encounter-12345',
    'pasien_id' => 'P001234567890',
    'tanggal_diagnosis' => '2024-01-15T09:00:00Z',
    'kode_icd10' => 'I10',
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
    'klinis_status' => 'active',
    'verifikasi_status' => 'confirmed',
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

try {
    $response = $client->kirimDiagnosis($conditionData);
    echo "Diagnosis berhasil dikirim: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error mengirim diagnosis: " . $e->getMessage() . "\n";
}
```

### 6. Contoh Alternatif Menggunakan cURL Bawaan PHP

Jika Anda tidak ingin menggunakan Guzzle, berikut adalah contoh menggunakan cURL bawaan PHP:

```php
<?php

class SatuSehatApiCurlClient
{
    private $baseUrl;
    private $clinicCode;
    private $clinicSecret;
    
    public function __construct($baseUrl, $clinicCode, $clinicSecret)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->clinicCode = $clinicCode;
        $this->clinicSecret = $clinicSecret;
    }
    
    private function sendRequest($endpoint, $data, $method = 'POST')
    {
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
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error: {$httpCode} - {$response}");
        }
        
        return json_decode($response, true);
    }
    
    public function kirimEncounter($data)
    {
        return $this->sendRequest('/api/encounter', $data);
    }
    
    public function kirimProcedure($data)
    {
        return $this->sendRequest('/api/procedure', $data);
    }
    
    public function kirimObservation($data)
    {
        return $this->sendRequest('/api/observation', $data);
    }
    
    public function kirimDiagnosis($data)
    {
        return $this->sendRequest('/api/diagnosis', $data);
    }
}

// Contoh penggunaan
$client = new SatuSehatApiCurlClient(
    'https://your-satusehat-service.com',
    'KLINIK-001',
    'your_clinic_secret_here'
);

$encounterData = [
    'pasien_id' => 'P001234567890',
    'tanggal_kunjungan' => '2024-01-15T08:30:00Z',
    'jenis_layanan' => '101',
    'jenis_kunjungan' => '1',
    'poli' => '100001',
    'dokter' => '10001234567',
    'keluhan_utama' => 'Sakit kepala'
];

try {
    $response = $client->kirimEncounter($encounterData);
    echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### 7. Contoh Implementasi Lengkap dalam Fungsi

```php
<?php

function kirimSemuaDataKunjungan($baseUrl, $clinicCode, $clinicSecret, $kunjunganId)
{
    // Ambil data dari database SIMKlinik (contoh)
    $dataKunjungan = [
        'pasien_id' => 'P001234567890',
        'tanggal_kunjungan' => '2024-01-15T08:30:00Z',
        'tanggal_selesai' => '2024-01-15T09:15:00Z',
        'jenis_layanan' => '101',
        'jenis_kunjungan' => '1',
        'poli' => '100001',
        'dokter' => '10001234567',
        'penjamin' => '1',
        'keluhan_utama' => 'Sakit kepala berat sejak 3 hari lalu',
        'anamnesa' => 'Pasien datang dengan keluhan sakit kepala berat...',
        'pemeriksaan_fisik' => [
            'tanda_vital' => [
                'tekanan_darah' => '130/85',
                'nadi' => 82,
                'suhu' => 37.2,
                'pernapasan' => 18,
                'tinggi' => 165,
                'berat' => 65.5
            ]
        ]
    ];
    
    $client = new SatuSehatApiClient($baseUrl, $clinicCode, $clinicSecret);
    
    try {
        // Kirim data kunjungan
        $encounterResponse = $client->kirimEncounter($dataKunjungan);
        $encounterId = $encounterResponse['data']['id'] ?? null;
        
        // Kirim data diagnosis
        $diagnosisData = [
            'encounter_id' => $encounterId,
            'pasien_id' => $dataKunjungan['pasien_id'],
            'tanggal_diagnosis' => '2024-01-15T09:00:00Z',
            'kode_icd10' => 'I10',
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
            'klinis_status' => 'active',
            'verifikasi_status' => 'confirmed',
            'onset_date' => '2024-01-15T09:00:00Z',
            'keterangan' => 'Diagnosis primer berdasarkan hasil pemeriksaan'
        ];
        
        $conditionResponse = $client->kirimDiagnosis($diagnosisData);
        
        // Kirim data observasi
        $observationData = [
            'encounter_id' => $encounterId,
            'pasien_id' => $dataKunjungan['pasien_id'],
            'tanggal_observasi' => '2024-01-15T08:35:00Z',
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
        
        $observationResponse = $client->kirimObservation($observationData);
        
        return [
            'encounter' => $encounterResponse,
            'diagnosis' => $conditionResponse,
            'observation' => $observationResponse
        ];
    } catch (Exception $e) {
        error_log("Error mengirim data kunjungan {$kunjunganId}: " . $e->getMessage());
        throw $e;
    }
}

// Contoh penggunaan fungsi
try {
    $hasil = kirimSemuaDataKunjungan(
        'https://your-satusehat-service.com',
        'KLINIK-001',
        'your_clinic_secret_here',
        'kunjungan-123'
    );
    
    echo "Semua data berhasil dikirim:\n";
    echo json_encode($hasil, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## Tips Implementasi

1. **Penanganan Error**: Selalu tangani error saat mengirim data ke SATUSEHAT service
2. **Logging**: Buat log untuk setiap permintaan dan respons untuk keperluan debugging
3. **Retry Logic**: Implementasikan mekanisme retry untuk permintaan yang gagal
4. **Batch Processing**: Untuk kinerja lebih baik, kirim data dalam batch jika memungkinkan
5. **Validasi Data**: Validasi data lokal sebelum mengirim ke SATUSEHAT service
6. **Keamanan**: Jangan hardcode secret di kode sumber, gunakan environment variable

## Penjadwalan Pengiriman Otomatis

Anda juga bisa membuat cron job untuk mengirim data secara otomatis:

```php
<?php
// cron_kirim_satusehat.php

// Ambil data yang belum dikirim dari database
$kunjunganBelumDikirim = getUnsentEncounters(); // fungsi untuk mengambil data belum dikirim

foreach ($kunjunganBelumDikirim as $kunjungan) {
    try {
        // Kirim data ke SATUSEHAT
        $client = new SatuSehatApiClient($baseUrl, $clinicCode, $clinicSecret);
        $response = $client->kirimEncounter($kunjungan);
        
        // Tandai sebagai telah dikirim
        markAsSent($kunjungan['id']);
        
        echo "Kunjungan {$kunjungan['id']} berhasil dikirim\n";
    } catch (Exception $e) {
        echo "Gagal mengirim kunjungan {$kunjungan['id']}: " . $e->getMessage() . "\n";
    }
}
```

Dengan contoh kode ini, Anda dapat mengintegrasikan SIMKlinik Anda dengan layanan SATUSEHAT secara efektif dan efisien.