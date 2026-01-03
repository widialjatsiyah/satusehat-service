# Panduan Penggunaan SATUSEHAT Service untuk Klinik

## Daftar Isi
1. [Pendahuluan](#pendahuluan)
2. [Persiapan Awal](#persiapan-awal)
3. [Struktur Data](#struktur-data)
4. [Endpoint API](#endpoint-api)
5. [Contoh Kode Implementasi](#contoh-kode-implementasi)
6. [Proses Pengiriman Data](#proses-pengiriman-data)
7. [Penanganan Error](#penanganan-error)
8. [Best Practices](#best-practices)

## Pendahuluan

SATUSEHAT Service adalah layanan terpusat yang memungkinkan klinik mengirimkan data ke platform SATUSEHAT dengan mudah dan aman. Layanan ini menyediakan endpoint API yang dapat diakses oleh klinik untuk mengirimkan data pasien, dokter, kunjungan, diagnosis, observasi, dan prosedur medis.

### Fitur Utama:
- Otentikasi berbasis klinik
- Pengiriman data pasien hanya dengan NIK
- Pengambilan ID SATUSEHAT otomatis
- Penanganan error dan retry mechanism
- Logging aktivitas pengiriman

## Persiapan Awal

### 1. Kredensial Klinik
Sebelum menggunakan layanan, pastikan Anda memiliki:
- `CLINIC_CODE`: Kode unik klinik
- `CLINIC_SECRET`: Secret untuk otentikasi
- `BASE_URL`: URL layanan SATUSEHAT Service

### 2. Persyaratan Data
Pastikan data yang akan dikirim sudah lengkap dan valid:
- NIK pasien (16 digit)
- Data dokter (ID SATUSEHAT, SIP/STR)
- Data poli (ID SATUSEHAT)
- Data kunjungan lengkap

## Struktur Data

### 1. Data Kunjungan
Endpoint: `POST /api/encounter`

```json
{
  "nik_pasien": "3201234567890123",
  "tanggal_kunjungan": "2024-01-15T08:30:00Z",
  "tanggal_selesai": "2024-01-15T09:15:00Z",
  "jenis_layanan": "101",
  "jenis_kunjungan": "1",
  "poli": "100001",
  "dokter": "10001234567",
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

### 2. Data Diagnosis
Endpoint: `POST /api/diagnosis`

```json
{
  "encounter_id": "encounter-12345",
  "pasien_id": "P00123456789",
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
  "keterangan": "Diagnosis primer berdasarkan hasil pemeriksaan"
}
```

### 3. Data Observasi
Endpoint: `POST /api/observation`

```json
{
  "encounter_id": "encounter-12345",
  "pasien_id": "P00123456789",
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

### 4. Data Prosedur
Endpoint: `POST /api/procedure`

```json
{
  "encounter_id": "encounter-12345",
  "pasien_id": "P00123456789",
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

## Endpoint API

### 1. Pengiriman Data

#### `/api/encounter` - Kirim Data Kunjungan
- Method: `POST`
- Header: `X-Clinic-Code`, `X-Clinic-Secret`
- Request Body: Data kunjungan (lihat struktur di atas)

#### `/api/diagnosis` - Kirim Data Diagnosis
- Method: `POST`
- Header: `X-Clinic-Code`, `X-Clinic-Secret`
- Request Body: Data diagnosis (lihat struktur di atas)

#### `/api/observation` - Kirim Data Observasi
- Method: `POST`
- Header: `X-Clinic-Code`, `X-Clinic-Secret`
- Request Body: Data observasi (lihat struktur di atas)

#### `/api/procedure` - Kirim Data Prosedur
- Method: `POST`
- Header: `X-Clinic-Code`, `X-Clinic-Secret`
- Request Body: Data prosedur (lihat struktur di atas)

### 2. Pencarian dan Registrasi Data

#### `/api/satusehat/get-patient-id` - Dapatkan ID SATUSEHAT Pasien
- Method: `POST`
- Request Body:
```json
{
  "nik": "3201234567890123"
}
```

#### `/api/satusehat/get-practitioner-id` - Dapatkan ID SATUSEHAT Dokter
- Method: `POST`
- Request Body:
```json
{
  "nik": "3201234567890123",
  "sip": "1234567890",
  "str": "0987654321"
}
```

#### `/api/satusehat/get-healthcare-service-id` - Dapatkan ID SATUSEHAT Poli
- Method: `POST`
- Request Body:
```json
{
  "kode_poli": "100001"
}
```

#### `/api/satusehat/register-patient` - Registrasi Pasien Baru
- Method: `POST`
- Request Body: Data pasien lengkap (lihat dokumentasi sebelumnya)

#### `/api/satusehat/register-practitioner` - Registrasi Dokter Baru
- Method: `POST`
- Request Body: Data dokter lengkap (lihat dokumentasi sebelumnya)

## Contoh Kode Implementasi

### 1. PHP (cURL)

```php
<?php
class SatusehatClient {
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
    
    // Kirim data kunjungan
    public function kirimEncounter($data) {
        return $this->sendRequest('/api/encounter', $data);
    }
    
    // Kirim data diagnosis
    public function kirimDiagnosis($data) {
        return $this->sendRequest('/api/diagnosis', $data);
    }
    
    // Kirim data observasi
    public function kirimObservation($data) {
        return $this->sendRequest('/api/observation', $data);
    }
    
    // Kirim data prosedur
    public function kirimProcedure($data) {
        return $this->sendRequest('/api/procedure', $data);
    }
    
    // Dapatkan ID SATUSEHAT pasien berdasarkan NIK
    public function getPatientId($nik) {
        return $this->sendRequest('/api/satusehat/get-patient-id', ['nik' => $nik]);
    }
    
    // Dapatkan ID SATUSEHAT dokter
    public function getPractitionerId($nik, $sip = null, $str = null) {
        $data = ['nik' => $nik];
        if ($sip) $data['sip'] = $sip;
        if ($str) $data['str'] = $str;
        
        return $this->sendRequest('/api/satusehat/get-practitioner-id', $data);
    }
    
    // Dapatkan ID SATUSEHAT poli
    public function getHealthcareServiceId($kodePoli) {
        return $this->sendRequest('/api/satusehat/get-healthcare-service-id', ['kode_poli' => $kodePoli]);
    }
    
    // Registrasi pasien baru
    public function registerPatient($data) {
        return $this->sendRequest('/api/satusehat/register-patient', $data);
    }
    
    // Registrasi dokter baru
    public function registerPractitioner($data) {
        return $this->sendRequest('/api/satusehat/register-practitioner', $data);
    }
}

// Contoh penggunaan
try {
    $client = new SatusehatClient(
        'https://your-satusehat-service.com',
        'KLINIK-001',
        'your_clinic_secret'
    );
    
    // Kirim data kunjungan (hanya dengan NIK pasien)
    $encounterData = [
        'nik_pasien' => '3201234567890123',
        'tanggal_kunjungan' => '2024-01-15T08:30:00Z',
        'tanggal_selesai' => '2024-01-15T09:15:00Z',
        'jenis_layanan' => '101',
        'jenis_kunjungan' => '1',
        'poli' => '100001',
        'dokter' => '10001234567',
        'penjamin' => '1',
        'keluhan_utama' => 'Sakit kepala berat',
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
    
    $response = $client->kirimEncounter($encounterData);
    echo "Encounter berhasil dikirim: " . json_encode($response, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### 2. CodeIgniter 3

// application/libraries/Satusehat_lib.php
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Satusehat_lib {
    private $ci;
    private $base_url;
    private $clinic_code;
    private $clinic_secret;
    
    public function __construct() {
        $this->ci =& get_instance();
        $this->ci->load->library('curl');
        
        // Ambil dari konfigurasi
        $this->base_url = $this->ci->config->item('satusehat_base_url');
        $this->clinic_code = $this->ci->config->item('satusehat_clinic_code');
        $this->clinic_secret = $this->ci->config->item('satusehat_clinic_secret');
    }
    
    private function _request($endpoint, $data, $method = 'POST') {
        $url = $this->base_url . $endpoint;
        
        $headers = [
            'X-Clinic-Code: ' . $this->clinic_code,
            'X-Clinic-Secret: ' . $this->clinic_secret,
            'Content-Type: application/json'
        ];
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
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
    
    public function kirimEncounter($data) {
        return $this->_request('/api/encounter', $data);
    }
    
    public function kirimDiagnosis($data) {
        return $this->_request('/api/diagnosis', $data);
    }
    
    public function kirimObservation($data) {
        return $this->_request('/api/observation', $data);
    }
    
    public function kirimProcedure($data) {
        return $this->_request('/api/procedure', $data);
    }
    
    public function getPatientId($nik) {
        return $this->_request('/api/satusehat/get-patient-id', ['nik' => $nik]);
    }
    
    public function getPractitionerId($nik, $sip = null, $str = null) {
        $data = ['nik' => $nik];
        if ($sip) $data['sip'] = $sip;
        if ($str) $data['str'] = $str;
        
        return $this->_request('/api/satusehat/get-practitioner-id', $data);
    }
    
    public function getHealthcareServiceId($kodePoli) {
        return $this->_request('/api/satusehat/get-healthcare-service-id', ['kode_poli' => $kodePoli]);
    }
    
    public function registerPatient($data) {
        return $this->_request('/api/satusehat/register-patient', $data);
    }
    
    public function registerPractitioner($data) {
        return $this->_request('/api/satusehat/register-practitioner', $data);
    }
}
```

// application/config/satusehat.php
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['satusehat_base_url'] = 'https://your-satusehat-service.com';
$config['satusehat_clinic_code'] = 'KLINIK-001';
$config['satusehat_clinic_secret'] = 'your_clinic_secret';
```

// application/controllers/Kunjungan.php
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kunjungan extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('satusehat_lib');
        $this->load->model('pasien_model'); // Asumsi ada model pasien
        $this->load->model('dokter_model'); // Asumsi ada model dokter
    }
    
    public function kirim_encounter() {
        // Ambil data dari input POST
        $input = json_decode($this->input->raw_input_stream, true);
        
        // Validasi data
        $this->form_validation->set_rules('nik_pasien', 'NIK Pasien', 'required|exact_length[16]');
        $this->form_validation->set_rules('tanggal_kunjungan', 'Tanggal Kunjungan', 'required|valid_date');
        $this->form_validation->set_rules('jenis_layanan', 'Jenis Layanan', 'required');
        $this->form_validation->set_rules('jenis_kunjungan', 'Jenis Kunjungan', 'required');
        $this->form_validation->set_rules('poli', 'Poli', 'required');
        $this->form_validation->set_rules('dokter', 'Dokter', 'required');
        
        if ($this->form_validation->run() == FALSE) {
            $response = [
                'status' => 'error',
                'message' => validation_errors()
            ];
            echo json_encode($response);
            return;
        }
        
        // Siapkan data untuk dikirim ke SATUSEHAT
        $encounterData = [
            'nik_pasien' => $input['nik_pasien'],
            'tanggal_kunjungan' => $input['tanggal_kunjungan'],
            'tanggal_selesai' => isset($input['tanggal_selesai']) ? $input['tanggal_selesai'] : date('c'),
            'jenis_layanan' => $input['jenis_layanan'],
            'jenis_kunjungan' => $input['jenis_kunjungan'],
            'poli' => $input['poli'], // ID SATUSEHAT
            'dokter' => $input['dokter'], // ID SATUSEHAT
            'penjamin' => isset($input['penjamin']) ? $input['penjamin'] : '1',
            'keluhan_utama' => $input['keluhan_utama'],
            'anamnesa' => isset($input['anamnesa']) ? $input['anamnesa'] : '',
            'pemeriksaan_fisik' => $input['pemeriksaan_fisik']
        ];
        
        try {
            $result = $this->satusehat_lib->kirimEncounter($encounterData);
            
            if (isset($result['status']) && ($result['status'] === 'success' || $result['status'] === 'queued')) {
                // Update status di database lokal
                $this->db->where('nik', $input['nik_pasien']);
                $this->db->update('pasien', ['sync_status' => 'sent']);
                
                echo json_encode([
                    'status' => 'success',
                    'data' => $result
                ]);
            } else {
                log_message('error', 'Gagal mengirim encounter: ' . json_encode($result));
                
                echo json_encode([
                    'status' => 'error',
                    'message' => $result['message'] ?? 'Unknown error occurred'
                ]);
            }
        } catch (Exception $e) {
            log_message('error', 'Exception saat mengirim encounter: ' . $e->getMessage());
            
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function kirim_diagnosis() {
        $input = json_decode($this->input->raw_input_stream, true);
        
        $this->form_validation->set_rules('encounter_id', 'Encounter ID', 'required');
        $this->form_validation->set_rules('pasien_id', 'Pasien ID', 'required');
        $this->form_validation->set_rules('tanggal_diagnosis', 'Tanggal Diagnosis', 'required');
        $this->form_validation->set_rules('kode_icd10', 'Kode ICD-10', 'required');
        $this->form_validation->set_rules('deskripsi_diagnosis', 'Deskripsi Diagnosis', 'required');
        
        if ($this->form_validation->run() == FALSE) {
            $response = [
                'status' => 'error',
                'message' => validation_errors()
            ];
            echo json_encode($response);
            return;
        }
        
        $diagnosisData = [
            'encounter_id' => $input['encounter_id'],
            'pasien_id' => $input['pasien_id'],
            'tanggal_diagnosis' => $input['tanggal_diagnosis'],
            'kode_icd10' => $input['kode_icd10'],
            'deskripsi_diagnosis' => $input['deskripsi_diagnosis'],
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
            'klinis_status' => $input['klinis_status'] ?? 'active',
            'verifikasi_status' => $input['verifikasi_status'] ?? 'confirmed',
            'keterangan' => $input['keterangan'] ?? ''
        ];
        
        try {
            $result = $this->satusehat_lib->kirimDiagnosis($diagnosisData);
            
            if (isset($result['status']) && $result['status'] === 'success') {
                echo json_encode([
                    'status' => 'success',
                    'data' => $result
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => $result['message'] ?? 'Unknown error occurred'
                ]);
            }
        } catch (Exception $e) {
            log_message('error', 'Exception saat mengirim diagnosis: ' . $e->getMessage());
            
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function kirim_observasi() {
        $input = json_decode($this->input->raw_input_stream, true);
        
        $this->form_validation->set_rules('encounter_id', 'Encounter ID', 'required');
        $this->form_validation->set_rules('pasien_id', 'Pasien ID', 'required');
        $this->form_validation->set_rules('tanggal_observasi', 'Tanggal Observasi', 'required');
        $this->form_validation->set_rules('kategori', 'Kategori', 'required');
        $this->form_validation->set_rules('kode', 'Kode', 'required');
        $this->form_validation->set_rules('nilai', 'Nilai', 'required');
        
        if ($this->form_validation->run() == FALSE) {
            $response = [
                'status' => 'error',
                'message' => validation_errors()
            ];
            echo json_encode($response);
            return;
        }
        
        $observationData = [
            'encounter_id' => $input['encounter_id'],
            'pasien_id' => $input['pasien_id'],
            'tanggal_observasi' => $input['tanggal_observasi'],
            'kategori' => $input['kategori'],
            'kode' => $input['kode'],
            'nilai' => $input['nilai'],
            'nilai_diatas' => $input['nilai_diatas'] ?? null,
            'interpretasi' => $input['interpretasi'] ?? null,
            'keterangan' => $input['keterangan'] ?? ''
        ];
        
        try {
            $result = $this->satusehat_lib->kirimObservation($observationData);
            
            if (isset($result['status']) && $result['status'] === 'success') {
                echo json_encode([
                    'status' => 'success',
                    'data' => $result
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => $result['message'] ?? 'Unknown error occurred'
                ]);
            }
        } catch (Exception $e) {
            log_message('error', 'Exception saat mengirim observasi: ' . $e->getMessage());
            
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function kirim_procedure() {
        $input = json_decode($this->input->raw_input_stream, true);
        
        $this->form_validation->set_rules('encounter_id', 'Encounter ID', 'required');
        $this->form_validation->set_rules('pasien_id', 'Pasien ID', 'required');
        $this->form_validation->set_rules('tanggal_prosedur', 'Tanggal Prosedur', 'required');
        $this->form_validation->set_rules('kode_prosedur', 'Kode Prosedur', 'required');
        $this->form_validation->set_rules('deskripsi_prosedur', 'Deskripsi Prosedur', 'required');
        
        if ($this->form_validation->run() == FALSE) {
            $response = [
                'status' => 'error',
                'message' => validation_errors()
            ];
            echo json_encode($response);
            return;
        }
        
        $procedureData = [
            'encounter_id' => $input['encounter_id'],
            'pasien_id' => $input['pasien_id'],
            'tanggal_prosedur' => $input['tanggal_prosedur'],
            'kode_prosedur' => $input['kode_prosedur'],
            'deskripsi_prosedur' => $input['deskripsi_prosedur'],
            'kode_metode' => $input['kode_metode'] ?? null,
            'deskripsi_metode' => $input['deskripsi_metode'] ?? null,
            'kode_alat' => $input['kode_alat'] ?? null,
            'deskripsi_alat' => $input['deskripsi_alat'] ?? null,
            'kondisi_klinis' => $input['kondisi_klinis'] ?? null,
            'komplikasi' => $input['komplikasi'] ?? null,
            'hasil' => $input['hasil'] ?? null
        ];
        
        try {
            $result = $this->satusehat_lib->kirimProcedure($procedureData);
            
            if (isset($result['status']) && $result['status'] === 'success') {
                echo json_encode([
                    'status' => 'success',
                    'data' => $result
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => $result['message'] ?? 'Unknown error occurred'
                ]);
            }
        } catch (Exception $e) {
            log_message('error', 'Exception saat mengirim procedure: ' . $e->getMessage());
            
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function cek_pasien() {
        $nik = $this->input->post('nik');
        
        if (empty($nik) || strlen($nik) != 16) {
            echo json_encode([
                'status' => 'error',
                'message' => 'NIK harus terdiri dari 16 digit'
            ]);
            return;
        }
        
        try {
            $result = $this->satusehat_lib->getPatientId($nik);
            
            if (isset($result['satusehat_id'])) {
                echo json_encode([
                    'status' => 'success',
                    'satusehat_id' => $result['satusehat_id']
                ]);
            } else {
                echo json_encode([
                    'status' => 'not_found',
                    'message' => $result['message'] ?? 'Pasien tidak ditemukan di SATUSEHAT'
                ]);
            }
        } catch (Exception $e) {
            log_message('error', 'Exception saat cek pasien: ' . $e->getMessage());
            
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
```

// application/models/Pasien_model.php
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pasien_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    public function get_by_nik($nik) {
        return $this->db->get_where('pasien', ['nik' => $nik])->row();
    }
    
    public function update_satusehat_id($nik, $satusehat_id) {
        $this->db->where('nik', $nik);
        return $this->db->update('pasien', [
            'satusehat_id' => $satusehat_id,
            'sync_status' => 'synced',
            'last_sync' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function insert_pasien($data) {
        return $this->db->insert('pasien', $data);
    }
}
```

// application/models/Dokter_model.php
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dokter_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    public function get_by_sip($sip) {
        return $this->db->get_where('dokter', ['sip' => $sip])->row();
    }
    
    public function update_satusehat_id($sip, $satusehat_id) {
        $this->db->where('sip', $sip);
        return $this->db->update('dokter', [
            'satusehat_id' => $satusehat_id,
            'sync_status' => 'synced',
            'last_sync' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function insert_dokter($data) {
        return $this->db->insert('dokter', $data);
    }
}
```

### 3. Yii1

// protected/components/SatusehatComponent.php
```php
<?php
class SatusehatComponent extends CComponent {
    
    public $baseUrl;
    public $clinicCode;
    public $clinicSecret;
    
    /**
     * Kirim data ke SATUSEHAT service
     */
    private function sendRequest($endpoint, $data, $method = 'POST') {
        $url = $this->baseUrl . $endpoint;
        
        $options = array(
            'http' => array(
                'header' => array(
                    "X-Clinic-Code: {$this->clinicCode}",
                    "X-Clinic-Secret: {$this->clinicSecret}",
                    "Content-Type: application/json"
                ),
                'method' => $method,
                'content' => json_encode($data),
                'timeout' => 60
            )
        );
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            throw new Exception("Gagal mengirim data ke SATUSEHAT");
        }
        
        return json_decode($result, true);
    }
    
    public function kirimEncounter($data) {
        return $this->sendRequest('/api/encounter', $data);
    }
    
    public function kirimDiagnosis($data) {
        return $this->sendRequest('/api/diagnosis', $data);
    }
    
    public function kirimObservation($data) {
        return $this->sendRequest('/api/observation', $data);
    }
    
    public function kirimProcedure($data) {
        return $this->sendRequest('/api/procedure', $data);
    }
    
    public function getPatientId($nik) {
        return $this->sendRequest('/api/satusehat/get-patient-id', array('nik' => $nik));
    }
    
    public function getPractitionerId($nik, $sip = null, $str = null) {
        $data = array('nik' => $nik);
        if ($sip) $data['sip'] = $sip;
        if ($str) $data['str'] = $str;
        
        return $this->sendRequest('/api/satusehat/get-practitioner-id', $data);
    }
    
    public function getHealthcareServiceId($kodePoli) {
        return $this->sendRequest('/api/satusehat/get-healthcare-service-id', array('kode_poli' => $kodePoli));
    }
    
    public function registerPatient($data) {
        return $this->sendRequest('/api/satusehat/register-patient', $data);
    }
    
    public function registerPractitioner($data) {
        return $this->sendRequest('/api/satusehat/register-practitioner', $data);
    }
}
```

// protected/config/main.php
```php
<?php
return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => 'SIMKlinik',
    
    // Preloading system components
    'preload' => array(
        'log',
    ),
    
    // Autoloading model and component classes
    'import' => array(
        'application.models.*',
        'application.components.*',
    ),
    
    // Application components
    'components' => array(
        'satusehat' => array(
            'class' => 'application.components.SatusehatComponent',
            'baseUrl' => 'https://your-satusehat-service.com',
            'clinicCode' => 'KLINIK-001',
            'clinicSecret' => 'your_clinic_secret'
        ),
        
        'db' => array(
            'class' => 'CDbConnection',
            'connectionString' => 'mysql:host=localhost;dbname=your_database',
            'username' => 'your_username',
            'password' => 'your_password',
            'charset' => 'utf8',
        ),
        
        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning',
                ),
            ),
        ),
    ),
    
    // Application-level parameters that can be accessed
    // using Yii::app()->params['paramName']
    'params' => array(
        // this is used in contact page
        'adminEmail' => 'admin@example.com',
    ),
);
```

// protected/models/Pasien.php
```php
<?php
class Pasien extends CActiveRecord {
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    public function tableName() {
        return 'pasien';
    }
    
    public function rules() {
        return array(
            array('nik, nama, tanggal_lahir, jenis_kelamin', 'required'),
            array('nik', 'length', 'max' => 16, 'min' => 16),
            array('nik', 'unique'),
            array('nama', 'length', 'max' => 100),
            array('jenis_kelamin', 'in', 'range' => array('L', 'P')),
            array('satusehat_id, sync_status, last_sync', 'safe'),
        );
    }
    
    public function relations() {
        return array(
        );
    }
    
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'nik' => 'NIK',
            'nama' => 'Nama',
            'tanggal_lahir' => 'Tanggal Lahir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'satusehat_id' => 'ID SATUSEHAT',
            'sync_status' => 'Status Sinkronisasi',
            'last_sync' => 'Sinkron Terakhir',
        );
    }
    
    public function updateSatusehatId($nik, $satusehatId) {
        $model = self::model()->findByAttributes(array('nik' => $nik));
        if ($model !== null) {
            $model->satusehat_id = $satusehatId;
            $model->sync_status = 'synced';
            $model->last_sync = new CDbExpression('NOW()');
            return $model->save();
        }
        return false;
    }
}
```

// protected/models/Dokter.php
```php
<?php
class Dokter extends CActiveRecord {
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    public function tableName() {
        return 'dokter';
    }
    
    public function rules() {
        return array(
            array('nik, nama, sip, str', 'required'),
            array('nik', 'length', 'max' => 16, 'min' => 16),
            array('sip, str', 'length', 'max' => 50),
            array('nik', 'unique'),
            array('nama', 'length', 'max' => 100),
            array('satusehat_id, sync_status, last_sync', 'safe'),
        );
    }
    
    public function relations() {
        return array(
        );
    }
    
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'nik' => 'NIK',
            'nama' => 'Nama',
            'sip' => 'SIP',
            'str' => 'STR',
            'satusehat_id' => 'ID SATUSEHAT',
            'sync_status' => 'Status Sinkronisasi',
            'last_sync' => 'Sinkron Terakhir',
        );
    }
    
    public function updateSatusehatId($sip, $satusehatId) {
        $model = self::model()->findByAttributes(array('sip' => $sip));
        if ($model !== null) {
            $model->satusehat_id = $satusehatId;
            $model->sync_status = 'synced';
            $model->last_sync = new CDbExpression('NOW()');
            return $model->save();
        }
        return false;
    }
}
```

// protected/controllers/KunjunganController.php
```php
<?php
class KunjunganController extends Controller {
    
    public function actionKirimEncounter() {
        if (Yii::app()->request->isPostRequest) {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validasi data
            $required = array('nik_pasien', 'tanggal_kunjungan', 'jenis_layanan', 'jenis_kunjungan', 'poli', 'dokter');
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    echo CJSON::encode(array(
                        'status' => 'error',
                        'message' => "Field {$field} wajib diisi"
                    ));
                    return;
                }
            }
            
            // Siapkan data untuk dikirim ke SATUSEHAT
            $encounterData = array(
                'nik_pasien' => $input['nik_pasien'],
                'tanggal_kunjungan' => $input['tanggal_kunjungan'],
                'tanggal_selesai' => isset($input['tanggal_selesai']) ? $input['tanggal_selesai'] : date('c'),
                'jenis_layanan' => $input['jenis_layanan'],
                'jenis_kunjungan' => $input['jenis_kunjungan'],
                'poli' => $input['poli'], // ID SATUSEHAT
                'dokter' => $input['dokter'], // ID SATUSEHAT
                'penjamin' => isset($input['penjamin']) ? $input['penjamin'] : '1',
                'keluhan_utama' => $input['keluhan_utama'],
                'anamnesa' => isset($input['anamnesa']) ? $input['anamnesa'] : '',
                'pemeriksaan_fisik' => $input['pemeriksaan_fisik']
            );
            
            try {
                $result = Yii::app()->satusehat->kirimEncounter($encounterData);
                
                if (isset($result['status']) && ($result['status'] === 'success' || $result['status'] === 'queued')) {
                    // Update status di database lokal
                    $pasien = Pasien::model()->findByAttributes(array('nik' => $input['nik_pasien']));
                    if ($pasien !== null) {
                        $pasien->sync_status = 'sent';
                        $pasien->save();
                    }
                    
                    echo CJSON::encode(array(
                        'status' => 'success',
                        'data' => $result
                    ));
                } else {
                    Yii::log('Gagal mengirim encounter: ' . CJSON::encode($result), CLogger::LEVEL_ERROR, 'application.satusehat');
                    
                    echo CJSON::encode(array(
                        'status' => 'error',
                        'message' => isset($result['message']) ? $result['message'] : 'Unknown error occurred'
                    ));
                }
            } catch (Exception $e) {
                Yii::log('Exception saat mengirim encounter: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.satusehat');
                
                echo CJSON::encode(array(
                    'status' => 'error',
                    'message' => $e->getMessage()
                ));
            }
        } else {
            throw new CHttpException(400, 'Permintaan tidak valid');
        }
    }
    
    public function actionKirimDiagnosis() {
        if (Yii::app()->request->isPostRequest) {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $required = array('encounter_id', 'pasien_id', 'tanggal_diagnosis', 'kode_icd10', 'deskripsi_diagnosis');
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    echo CJSON::encode(array(
                        'status' => 'error',
                        'message' => "Field {$field} wajib diisi"
                    ));
                    return;
                }
            }
            
            $diagnosisData = array(
                'encounter_id' => $input['encounter_id'],
                'pasien_id' => $input['pasien_id'],
                'tanggal_diagnosis' => $input['tanggal_diagnosis'],
                'kode_icd10' => $input['kode_icd10'],
                'deskripsi_diagnosis' => $input['deskripsi_diagnosis'],
                'kategori' => array(
                    array(
                        'coding' => array(
                            array(
                                'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                                'code' => 'encounter-diagnosis',
                                'display' => 'Encounter Diagnosis'
                            )
                        )
                    )
                ),
                'klinis_status' => isset($input['klinis_status']) ? $input['klinis_status'] : 'active',
                'verifikasi_status' => isset($input['verifikasi_status']) ? $input['verifikasi_status'] : 'confirmed',
                'keterangan' => isset($input['keterangan']) ? $input['keterangan'] : ''
            );
            
            try {
                $result = Yii::app()->satusehat->kirimDiagnosis($diagnosisData);
                
                if (isset($result['status']) && $result['status'] === 'success') {
                    echo CJSON::encode(array(
                        'status' => 'success',
                        'data' => $result
                    ));
                } else {
                    echo CJSON::encode(array(
                        'status' => 'error',
                        'message' => isset($result['message']) ? $result['message'] : 'Unknown error occurred'
                    ));
                }
            } catch (Exception $e) {
                Yii::log('Exception saat mengirim diagnosis: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.satusehat');
                
                echo CJSON::encode(array(
                    'status' => 'error',
                    'message' => $e->getMessage()
                ));
            }
        } else {
            throw new CHttpException(400, 'Permintaan tidak valid');
        }
    }
    
    public function actionKirimObservation() {
        if (Yii::app()->request->isPostRequest) {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $required = array('encounter_id', 'pasien_id', 'tanggal_observasi', 'kategori', 'kode', 'nilai');
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    echo CJSON::encode(array(
                        'status' => 'error',
                        'message' => "Field {$field} wajib diisi"
                    ));
                    return;
                }
            }
            
            $observationData = array(
                'encounter_id' => $input['encounter_id'],
                'pasien_id' => $input['pasien_id'],
                'tanggal_observasi' => $input['tanggal_observasi'],
                'kategori' => $input['kategori'],
                'kode' => $input['kode'],
                'nilai' => $input['nilai'],
                'nilai_diatas' => isset($input['nilai_diatas']) ? $input['nilai_diatas'] : null,
                'interpretasi' => isset($input['interpretasi']) ? $input['interpretasi'] : null,
                'keterangan' => isset($input['keterangan']) ? $input['keterangan'] : ''
            );
            
            try {
                $result = Yii::app()->satusehat->kirimObservation($observationData);
                
                if (isset($result['status']) && $result['status'] === 'success') {
                    echo CJSON::encode(array(
                        'status' => 'success',
                        'data' => $result
                    ));
                } else {
                    echo CJSON::encode(array(
                        'status' => 'error',
                        'message' => isset($result['message']) ? $result['message'] : 'Unknown error occurred'
                    ));
                }
            } catch (Exception $e) {
                Yii::log('Exception saat mengirim observasi: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.satusehat');
                
                echo CJSON::encode(array(
                    'status' => 'error',
                    'message' => $e->getMessage()
                ));
            }
        } else {
            throw new CHttpException(400, 'Permintaan tidak valid');
        }
    }
    
    public function actionKirimProcedure() {
        if (Yii::app()->request->isPostRequest) {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $required = array('encounter_id', 'pasien_id', 'tanggal_prosedur', 'kode_prosedur', 'deskripsi_prosedur');
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    echo CJSON::encode(array(
                        'status' => 'error',
                        'message' => "Field {$field} wajib diisi"
                    ));
                    return;
                }
            }
            
            $procedureData = array(
                'encounter_id' => $input['encounter_id'],
                'pasien_id' => $input['pasien_id'],
                'tanggal_prosedur' => $input['tanggal_prosedur'],
                'kode_prosedur' => $input['kode_prosedur'],
                'deskripsi_prosedur' => $input['deskripsi_prosedur'],
                'kode_metode' => isset($input['kode_metode']) ? $input['kode_metode'] : null,
                'deskripsi_metode' => isset($input['deskripsi_metode']) ? $input['deskripsi_metode'] : null,
                'kode_alat' => isset($input['kode_alat']) ? $input['kode_alat'] : null,
                'deskripsi_alat' => isset($input['deskripsi_alat']) ? $input['deskripsi_alat'] : null,
                'kondisi_klinis' => isset($input['kondisi_klinis']) ? $input['kondisi_klinis'] : null,
                'komplikasi' => isset($input['komplikasi']) ? $input['komplikasi'] : null,
                'hasil' => isset($input['hasil']) ? $input['hasil'] : null
            );
            
            try {
                $result = Yii::app()->satusehat->kirimProcedure($procedureData);
                
                if (isset($result['status']) && $result['status'] === 'success') {
                    echo CJSON::encode(array(
                        'status' => 'success',
                        'data' => $result
                    ));
                } else {
                    echo CJSON::encode(array(
                        'status' => 'error',
                        'message' => isset($result['message']) ? $result['message'] : 'Unknown error occurred'
                    ));
                }
            } catch (Exception $e) {
                Yii::log('Exception saat mengirim procedure: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.satusehat');
                
                echo CJSON::encode(array(
                    'status' => 'error',
                    'message' => $e->getMessage()
                ));
            }
        } else {
            throw new CHttpException(400, 'Permintaan tidak valid');
        }
    }
    
    public function actionCekPasien() {
        if (Yii::app()->request->isPostRequest) {
            $nik = Yii::app()->request->getPost('nik');
            
            if (empty($nik) || strlen($nik) != 16) {
                echo CJSON::encode(array(
                    'status' => 'error',
                    'message' => 'NIK harus terdiri dari 16 digit'
                ));
                return;
            }
            
            try {
                $result = Yii::app()->satusehat->getPatientId($nik);
                
                if (isset($result['satusehat_id'])) {
                    echo CJSON::encode(array(
                        'status' => 'success',
                        'satusehat_id' => $result['satusehat_id']
                    ));
                } else {
                    echo CJSON::encode(array(
                        'status' => 'not_found',
                        'message' => isset($result['message']) ? $result['message'] : 'Pasien tidak ditemukan di SATUSEHAT'
                    ));
                }
            } catch (Exception $e) {
                Yii::log('Exception saat cek pasien: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.satusehat');
                
                echo CJSON::encode(array(
                    'status' => 'error',
                    'message' => $e->getMessage()
                ));
            }
        } else {
            throw new CHttpException(400, 'Permintaan tidak valid');
        }
    }
}
```

### 4. JavaScript (Fetch API)

```javascript
class SatusehatClient {
    constructor(baseUrl, clinicCode, clinicSecret) {
        this.baseUrl = baseUrl;
        this.clinicCode = clinicCode;
        this.clinicSecret = clinicSecret;
    }
    
    async sendRequest(endpoint, data, method = 'POST') {
        const url = `${this.baseUrl}${endpoint}`;
        
        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'X-Clinic-Code': this.clinicCode,
                    'X-Clinic-Secret': this.clinicSecret,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status} - ${await response.text()}`);
            }
            
            return await response.json();
        } catch (error) {
            throw new Error(`Request Error: ${error.message}`);
        }
    }
    
    async kirimEncounter(data) {
        return this.sendRequest('/api/encounter', data);
    }
    
    async kirimDiagnosis(data) {
        return this.sendRequest('/api/diagnosis', data);
    }
    
    async kirimObservation(data) {
        return this.sendRequest('/api/observation', data);
    }
    
    async kirimProcedure(data) {
        return this.sendRequest('/api/procedure', data);
    }
    
    async getPatientId(nik) {
        return this.sendRequest('/api/satusehat/get-patient-id', { nik });
    }
    
    async getPractitionerId(nik, sip = null, str = null) {
        const data = { nik };
        if (sip) data.sip = sip;
        if (str) data.str = str;
        
        return this.sendRequest('/api/satusehat/get-practitioner-id', data);
    }
}

// Contoh penggunaan
const client = new SatusehatClient(
    'https://your-satusehat-service.com',
    'KLINIK-001',
    'your_clinic_secret'
);

// Kirim data kunjungan
client.kirimEncounter({
    nik_pasien: '3201234567890123',
    tanggal_kunjungan: '2024-01-15T08:30:00Z',
    tanggal_selesai: '2024-01-15T09:15:00Z',
    jenis_layanan: '101',
    jenis_kunjungan: '1',
    poli: '100001',
    dokter: '10001234567',
    penjamin: '1',
    keluhan_utama: 'Sakit kepala berat',
    pemeriksaan_fisik: {
        tanda_vital: {
            tekanan_darah: '130/85',
            nadi: 82,
            suhu: 37.2,
            pernapasan: 18,
            tinggi: 165,
            berat: 65.5
        }
    }
})
.then(response => console.log('Encounter berhasil dikirim:', response))
.catch(error => console.error('Error:', error.message));

// Kirim data prosedur
client.kirimProcedure({
    encounter_id: 'encounter-12345',
    pasien_id: 'P00123456789',
    tanggal_prosedur: '2024-01-15T08:45:00Z',
    kode_prosedur: '17.1',
    deskripsi_prosedur: 'Insisi dan ekstraksi dari kista atau abses',
    kode_metode: '3951000132103',
    deskripsi_metode: 'Metode insesi',
    kode_alat: '27724004',
    deskripsi_alat: 'Scalpel',
    kondisi_klinis: 'Abses pada lengan kanan',
    komplikasi: 'Tidak ada',
    hasil: 'Prosedur berhasil, tidak ada komplikasi'
})
.then(response => console.log('Procedure berhasil dikirim:', response))
.catch(error => console.error('Error:', error.message));
```

## Proses Pengiriman Data

### 1. Alur Pengiriman Data Kunjungan
1. Pastikan dokter dan poli sudah terdaftar di SATUSEHAT (dapatkan ID SATUSEHAT)
2. Kirim data kunjungan dengan NIK pasien (bukan ID SATUSEHAT)
3. Service akan mencari atau mendaftarkan pasien secara otomatis
4. Service mengembalikan status pengiriman

### 2. Alur Pengiriman Data Observasi
1. Setelah kunjungan terdaftar, Anda bisa mengirimkan data observasi
2. Gunakan ID encounter dan pasien dari respons sebelumnya
3. Kirimkan data observasi seperti tanda vital, hasil lab, dll
4. Service akan menyimpan dan menghubungkan observasi ke kunjungan yang sesuai

### 3. Alur Pengiriman Data Prosedur
1. Prosedur biasanya dikirim setelah kunjungan terdaftar
2. Gunakan ID encounter dan pasien dari respons sebelumnya
3. Kirimkan data prosedur medis yang dilakukan
4. Service akan menyimpan dan menghubungkan prosedur ke kunjungan yang sesuai

### 4. Penanganan Observasi saat Kirim Data Kunjungan
Saat klinik mengirimkan data kunjungan, data observasi (seperti tanda vital) yang disertakan dalam data kunjungan akan diproses secara otomatis. Namun, untuk pengiriman observasi yang lebih lengkap atau terpisah (misalnya hasil laboratorium, pemeriksaan fisik tambahan), Anda perlu mengirimkannya secara terpisah menggunakan endpoint `/api/observation`.

Berikut adalah bagaimana prosesnya:

1. **Data observasi dalam kunjungan**: Data observasi yang disertakan dalam bagian `pemeriksaan_fisik` pada kunjungan akan diproses dan dikirimkan ke SATUSEHAT sebagai bagian dari kunjungan tersebut.

2. **Data observasi terpisah**: Untuk observasi yang lebih lengkap atau dikirim secara terpisah, Anda bisa menggunakan endpoint `/api/observation` untuk mengirimkan data observasi lengkap sesuai standar FHIR.

3. **Sinkronisasi observasi**: Anda bisa mengirimkan observasi segera setelah kunjungan terdaftar atau dalam batch sesuai kebutuhan sistem Anda.

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

## Best Practices

### 1. Pengiriman Data
- Gunakan NIK pasien untuk kemudahan integrasi
- Simpan ID SATUSEHAT dokter dan poli secara lokal setelah pertama kali diperoleh
- Gunakan batch processing untuk data dalam jumlah besar
- Implementasikan retry mechanism untuk permintaan yang gagal

### 2. Keamanan
- Jangan hardcode kredensial di kode sumber
- Gunakan environment variable atau file konfigurasi terenkripsi
- Pastikan koneksi ke SATUSEHAT Service menggunakan HTTPS
- Batasi akses ke endpoint hanya dari IP yang terpercaya

### 3. Performa
- Gunakan caching untuk ID SATUSEHAT yang sering diakses
- Implementasikan queue untuk pengiriman data
- Gunakan batch processing untuk data yang banyak
- Optimalkan query database untuk pencarian data

### 4. Pemantauan
- Implementasikan logging untuk semua permintaan
- Buat dashboard untuk melihat status pengiriman data
- Tampilkan jumlah data yang berhasil/gagal dikirim
- Tampilkan log error dan solusi umum