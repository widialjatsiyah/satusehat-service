# Contoh Kode Umum untuk Mengirim Data ke SATUSEHAT Service

## Pendahuluan

Dokumentasi ini berisi contoh kode umum yang dapat digunakan di berbagai framework PHP seperti CodeIgniter 3 (CI3) atau Yii 1. Tujuannya agar SIMKlinik yang dibangun dengan berbagai framework dapat dengan mudah mengintegrasikan layanan SATUSEHAT.

## Persiapan Umum

Sebelum mengirim data, pastikan:
1. Sistem memiliki akses internet ke layanan SATUSEHAT
2. Kode klinik dan secret sudah didapatkan
3. Data pasien, dokter, dan lainnya sudah terdaftar di SATUSEHAT

## 1. Contoh Umum Menggunakan cURL

### Fungsi Dasar Pengiriman Data

```php
<?php
/**
 * Fungsi umum untuk mengirim data ke SATUSEHAT service
 * @param string $baseUrl URL dasar service SATUSEHAT
 * @param string $clinicCode Kode klinik
 * @param string $clinicSecret Secret klinik
 * @param string $endpoint Endpoint API
 * @param array $data Data yang akan dikirim
 * @return array Response dari service
 */
function kirimDataKeSatuSehat($baseUrl, $clinicCode, $clinicSecret, $endpoint, $data) {
    $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Clinic-Code: ' . $clinicCode,
        'X-Clinic-Secret: ' . $clinicSecret,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Timeout 60 detik
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Aktifkan verifikasi SSL
    
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

/**
 * Fungsi untuk mengirim data kunjungan
 */
function kirimEncounter($baseUrl, $clinicCode, $clinicSecret, $data) {
    return kirimDataKeSatuSehat($baseUrl, $clinicCode, $clinicSecret, '/api/encounter', $data);
}

/**
 * Fungsi untuk mengirim data prosedur
 */
function kirimProcedure($baseUrl, $clinicCode, $clinicSecret, $data) {
    return kirimDataKeSatuSehat($baseUrl, $clinicCode, $clinicSecret, '/api/procedure', $data);
}

/**
 * Fungsi untuk mengirim data observasi
 */
function kirimObservation($baseUrl, $clinicCode, $clinicSecret, $data) {
    return kirimDataKeSatuSehat($baseUrl, $clinicCode, $clinicSecret, '/api/observation', $data);
}

/**
 * Fungsi untuk mengirim data diagnosis
 */
function kirimDiagnosis($baseUrl, $clinicCode, $clinicSecret, $data) {
    return kirimDataKeSatuSehat($baseUrl, $clinicCode, $clinicSecret, '/api/diagnosis', $data);
}
```

### Contoh Penggunaan Fungsi Umum

```php
<?php
// Konfigurasi
$config = [
    'base_url' => 'https://your-satusehat-service.com',
    'clinic_code' => 'KLINIK-001',
    'clinic_secret' => 'your_clinic_secret_here'
];

// Data kunjungan dari SIMKlinik
$encounterData = [
    'pasien_id' => 'P001234567890',
    'tanggal_kunjungan' => date('c'), // Format ISO 8601
    'tanggal_selesai' => date('c', strtotime('+30 minutes')),
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

try {
    $response = kirimEncounter($config['base_url'], $config['clinic_code'], $config['clinic_secret'], $encounterData);
    echo "Berhasil mengirim encounter: " . json_encode($response, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## 2. Implementasi di CodeIgniter 3

### Model untuk Pengiriman Data

```php
<?php
// application/models/Satusehat_model.php
defined('BASEPATH') OR exit('No direct script access allowed');

class Satusehat_model extends CI_Model {
    
    private $base_url;
    private $clinic_code;
    private $clinic_secret;
    
    public function __construct() {
        parent::__construct();
        
        // Ambil dari konfigurasi
        $this->base_url = $this->config->item('satusehat_base_url');
        $this->clinic_code = $this->config->item('satusehat_clinic_code');
        $this->clinic_secret = $this->config->item('satusehat_clinic_secret');
    }
    
    private function _kirim_request($endpoint, $data) {
        $url = rtrim($this->base_url, '/') . '/' . ltrim($endpoint, '/');
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-Clinic-Code: ' . $this->clinic_code,
                'X-Clinic-Secret: ' . $this->clinic_secret,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true
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
    
    public function kirim_encounter($data) {
        return $this->_kirim_request('/api/encounter', $data);
    }
    
    public function kirim_procedure($data) {
        return $this->_kirim_request('/api/procedure', $data);
    }
    
    public function kirim_observation($data) {
        return $this->_kirim_request('/api/observation', $data);
    }
    
    public function kirim_diagnosis($data) {
        return $this->_kirim_request('/api/diagnosis', $data);
    }
}
```

### Controller untuk Pengujian

```php
<?php
// application/controllers/Satusehat.php
defined('BASEPATH') OR exit('No direct script access allowed');

class Satusehat extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Satusehat_model');
    }
    
    public function kirim_encounter() {
        $data = [
            'pasien_id' => $this->input->post('pasien_id'),
            'tanggal_kunjungan' => $this->input->post('tanggal_kunjungan'),
            'jenis_layanan' => $this->input->post('jenis_layanan'),
            'jenis_kunjungan' => $this->input->post('jenis_kunjungan'),
            'poli' => $this->input->post('poli'),
            'dokter' => $this->input->post('dokter'),
            'keluhan_utama' => $this->input->post('keluhan_utama')
        ];
        
        $result = $this->Satusehat_model->kirim_encounter($data);
        
        if ($result['status'] === 'success' || isset($result['log_id'])) {
            echo json_encode(['status' => 'success', 'data' => $result]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $result['message']]);
        }
    }
}
```

### Konfigurasi di CodeIgniter

```php
<?php
// application/config/satusehat.php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['satusehat_base_url'] = 'https://your-satusehat-service.com';
$config['satusehat_clinic_code'] = 'KLINIK-001';
$config['satusehat_clinic_secret'] = 'your_clinic_secret_here';
```

## 3. Implementasi di Yii 1

### Component untuk Pengiriman Data

```php
<?php
// protected/components/SatusehatComponent.php
class SatusehatComponent extends CComponent {
    
    public $baseUrl;
    public $clinicCode;
    public $clinicSecret;
    
    /**
     * Kirim data ke SATUSEHAT service
     */
    private function kirimRequest($endpoint, $data) {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        $options = [
            'http' => [
                'header' => [
                    "X-Clinic-Code: {$this->clinicCode}",
                    "X-Clinic-Secret: {$this->clinicSecret}",
                    "Content-Type: application/json",
                    "Accept: application/json"
                ],
                'method' => 'POST',
                'content' => json_encode($data),
                'timeout' => 60
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            throw new Exception("Gagal mengirim data ke SATUSEHAT");
        }
        
        return json_decode($result, true);
    }
    
    public function kirimEncounter($data) {
        return $this->kirimRequest('/api/encounter', $data);
    }
    
    public function kirimProcedure($data) {
        return $this->kirimRequest('/api/procedure', $data);
    }
    
    public function kirimObservation($data) {
        return $this->kirimRequest('/api/observation', $data);
    }
    
    public function kirimDiagnosis($data) {
        return $this->kirimRequest('/api/diagnosis', $data);
    }
}
```

### Konfigurasi Component di Yii

```php
<?php
// protected/config/main.php
return array(
    // ... konfigurasi lainnya
    'components' => array(
        // ... komponen lainnya
        'satusehat' => array(
            'class' => 'application.components.SatusehatComponent',
            'baseUrl' => 'https://your-satusehat-service.com',
            'clinicCode' => 'KLINIK-001',
            'clinicSecret' => 'your_clinic_secret_here'
        ),
    ),
);
```

### Penggunaan di Controller Yii

```php
<?php
// protected/controllers/PasienController.php
class PasienController extends Controller {
    
    public function actionKirimEncounter() {
        $data = [
            'pasien_id' => $_POST['pasien_id'],
            'tanggal_kunjungan' => $_POST['tanggal_kunjungan'],
            'jenis_layanan' => $_POST['jenis_layanan'],
            'jenis_kunjungan' => $_POST['jenis_kunjungan'],
            'poli' => $_POST['poli'],
            'dokter' => $_POST['dokter'],
            'keluhan_utama' => $_POST['keluhan_utama']
        ];
        
        try {
            $result = Yii::app()->satusehat->kirimEncounter($data);
            
            if (isset($result['status']) && $result['status'] === 'success') {
                echo CJSON::encode(['status' => 'success', 'data' => $result]);
            } else {
                echo CJSON::encode(['status' => 'error', 'message' => 'Gagal mengirim data']);
            }
        } catch (Exception $e) {
            echo CJSON::encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
```

## 4. Implementasi Umum Tanpa Framework

Jika Anda menggunakan PHP murni tanpa framework:

```php
<?php
// satusehat_helper.php

class SatusehatHelper {
    
    private $baseUrl;
    private $clinicCode;
    private $clinicSecret;
    
    public function __construct($baseUrl, $clinicCode, $clinicSecret) {
        $this->baseUrl = $baseUrl;
        $this->clinicCode = $clinicCode;
        $this->clinicSecret = $clinicSecret;
    }
    
    private function request($endpoint, $data, $method = 'POST') {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => [
                    "X-Clinic-Code: {$this->clinicCode}",
                    "X-Clinic-Secret: {$this->clinicSecret}",
                    "Content-Type: application/json",
                    "Accept: application/json"
                ],
                'content' => json_encode($data),
                'timeout' => 60
            ]
        ]);
        
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            throw new Exception("Gagal mengakses SATUSEHAT service");
        }
        
        return json_decode($result, true);
    }
    
    public function kirimEncounter($data) {
        return $this->request('/api/encounter', $data);
    }
    
    public function kirimProcedure($data) {
        return $this->request('/api/procedure', $data);
    }
    
    public function kirimObservation($data) {
        return $this->request('/api/observation', $data);
    }
    
    public function kirimDiagnosis($data) {
        return $this->request('/api/diagnosis', $data);
    }
}

// Contoh penggunaan
$ssHelper = new SatusehatHelper(
    'https://your-satusehat-service.com',
    'KLINIK-001',
    'your_clinic_secret_here'
);

try {
    $response = $ssHelper->kirimEncounter([
        'pasien_id' => 'P001234567890',
        'tanggal_kunjungan' => date('c'),
        'jenis_layanan' => '101',
        'jenis_kunjungan' => '1',
        'poli' => '100001',
        'dokter' => '10001234567',
        'keluhan_utama' => 'Sakit kepala'
    ]);
    
    echo "Berhasil: " . json_encode($response);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## 5. Contoh Data Lengkap untuk Setiap Jenis

### Data Kunjungan (Encounter)
```php
$encounterData = [
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
```

### Data Prosedur (Procedure)
```php
$procedureData = [
    'encounter_id' => 'encounter-12345',
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
```

### Data Observasi (Observation)
```php
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
```

### Data Diagnosis (Condition)
```php
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
```

## 6. Penanganan Error dan Logging

Untuk semua implementasi, penting untuk menangani error dan membuat log:

```php
// Contoh penanganan error umum
function kirimDataDenganRetry($baseUrl, $clinicCode, $clinicSecret, $endpoint, $data, $maxRetries = 3) {
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            return kirimDataKeSatuSehat($baseUrl, $clinicCode, $clinicSecret, $endpoint, $data);
        } catch (Exception $e) {
            $attempt++;
            $errorMsg = "Gagal mengirim data ke SATUSEHAT (usaha ke-" . $attempt . " dari " . $maxRetries . "): " . $e->getMessage();
            
            // Log error
            error_log($errorMsg);
            
            if ($attempt >= $maxRetries) {
                // Simpan data untuk dikirim ulang nanti
                simpanDataGagal($endpoint, $data, $e->getMessage());
                throw $e;
            }
            
            // Tunggu sebelum mencoba lagi
            sleep(5);
        }
    }
}

// Fungsi untuk menyimpan data yang gagal dikirim
function simpanDataGagal($endpoint, $data, $error) {
    // Simpan ke database atau file log
    $logData = [
        'endpoint' => $endpoint,
        'data' => json_encode($data),
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'pending'
    ];
    
    // Implementasikan sesuai dengan sistem Anda
    // misalnya: insert ke tabel failed_requests
}
```

Dengan contoh-contoh ini, SIMKlinik yang dibangun dengan berbagai framework PHP dapat dengan mudah mengintegrasikan layanan SATUSEHAT tanpa harus mengubah banyak kode inti sistem mereka.