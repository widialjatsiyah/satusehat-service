# Panduan Lengkap Pengiriman Bundle SATUSEHAT

Dokumen ini menjelaskan secara lengkap cara mengirim data bundle SATUSEHAT (Encounter + Conditions) dari berbagai platform: CodeIgniter 3 dan Yii Framework 1.

## Apa itu Bundle?

Bundle adalah cara mengirim beberapa data terkait secara bersamaan ke SATUSEHAT. Dalam konteks ini, kita mengirim data [Encounter](file:///c:/laragon/www/service-satusehat/app/Models/Encounter.php#L5-L21) (kunjungan) dan satu atau lebih data [Condition](file:///c:/laragon/www/service-satusehat/app/Models/Condition.php#L5-L17) (diagnosis) dalam satu permintaan.

## API Endpoint

```
POST /api/registration/bundle
```

## Header yang Dibutuhkan

- `Content-Type: application/json`
- `X-Clinic-Code: [kode_klinik]`
- `X-Clinic-Secret: [secret_klinik]`

## Struktur Data yang Dikirim

```json
{
  "encounter": {
    "registration_number": "string",
    "arrived": "string",
    "in_progress_start": "string",
    "in_progress_end": "string",
    "finished": "string",
    "consultation_method": "string",
    "patient_id": "string",
    "patient_name": "string",
    "practitioner_id": "string",
    "practitioner_name": "string",
    "location_id": "string",
    "location_name": "string"
  },
  "conditions": [
    {
      "icd10_code": "string",
      "patient_id": "string",
      "patient_name": "string",
      "clinical_status": "string (optional, default: active)",
      "category": "string (optional, default: Diagnosis)",
      "onset_date_time": "string (optional)",
      "recorded_date": "string (optional)",
      "verification_status": "string (optional)"
    }
  ]
}
```

---

## 1. Panduan untuk CodeIgniter 3 (PHP)

### Library untuk Koneksi SATUSEHAT

```php
<?php
// application/libraries/Satusehat_lib.php
defined('BASEPATH') OR exit('No direct script access allowed');

class Satusehat_lib {
    private $base_url;
    private $clinic_code;
    private $clinic_secret;
    
    public function __construct($params = array()) {
        $this->base_url = $params['base_url'] ?? 'http://localhost:8000'; // Ganti dengan URL service SATUSEHAT Anda
        $this->clinic_code = $params['clinic_code'] ?? '';
        $this->clinic_secret = $params['clinic_secret'] ?? '';
    }
    
    private function _make_request($endpoint, $data, $method = 'POST') {
        $url = $this->base_url . $endpoint;
        
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60, // Tambah timeout karena bisa memakan waktu lama
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "X-Clinic-Code: " . $this->clinic_code,
                "X-Clinic-Secret: " . $this->clinic_secret
            ),
        );
        
        $curl = curl_init();
        curl_setopt_array($curl, $curl_options);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception('cURL Error: ' . $err);
        }
        
        if ($http_code >= 400) {
            throw new Exception('HTTP Error: ' . $http_code . ' - ' . $response);
        }
        
        $result = json_decode($response, true);
        
        return $result;
    }
    
    public function kirim_bundle($data) {
        return $this->_make_request('/api/registration/bundle', $data);
    }
}
```

### Controller untuk Pengiriman Bundle

```php
<?php
// application/controllers/Satusehat.php
defined('BASEPATH') OR exit('No direct script access allowed');

class Satusehat extends CI_Controller {

    private $satusehat_lib;
    
    public function __construct() {
        parent::__construct();
        
        // Load library SATUSEHAT
        $this->load->library('satusehat_lib', [
            'base_url' => 'http://localhost:8000', // Ganti dengan URL service SATUSEHAT Anda
            'clinic_code' => 'KODE_KLINIK_ANDA',
            'clinic_secret' => 'SECRET_KLINIK_ANDA'
        ]);
    }

    public function kirim_bundle() {
        // Data yang akan dikirim
        $data = [
            'encounter' => [
                'registration_number' => 'REG20231201001',
                'arrived' => '2023-12-01T08:00:00+07:00',
                'in_progress_start' => '2023-12-01T08:30:00+07:00',
                'in_progress_end' => '2023-12-01T09:00:00+07:00',
                'finished' => '2023-12-01T09:30:00+07:00',
                'consultation_method' => 'RAJAL',
                'patient_id' => 'satusehat_patient_id',
                'patient_name' => 'Nama Pasien',
                'practitioner_id' => 'satusehat_practitioner_id',
                'practitioner_name' => 'Nama Dokter',
                'location_id' => 'satusehat_location_id',
                'location_name' => 'Nama Poli'
            ],
            'conditions' => [
                [
                    'icd10_code' => 'A00.0',
                    'patient_id' => 'satusehat_patient_id',
                    'patient_name' => 'Nama Pasien',
                    'clinical_status' => 'active',
                    'category' => 'Diagnosis',
                    'onset_date_time' => '2023-12-01T08:00:00+07:00',
                    'recorded_date' => '2023-12-01T08:00:00+07:00'
                ],
                [
                    'icd10_code' => 'B01.1',
                    'patient_id' => 'satusehat_patient_id',
                    'patient_name' => 'Nama Pasien',
                    'clinical_status' => 'active',
                    'category' => 'Diagnosis'
                ]
            ]
        ];

        try {
            // Kirim ke service SATUSEHAT
            $response = $this->satusehat_lib->kirim_bundle($data);
            
            // Tampilkan hasil
            header('Content-Type: application/json');
            echo json_encode($response);
        } catch (Exception $e) {
            // Tangani error
            http_response_code(500);
            echo json_encode([
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

### Alternatif menggunakan cURL native

```php
private function kirim_ke_satusehat($data) {
    $url = 'http://localhost:8000/api/registration/bundle';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Clinic-Code: KODE_KLINIK_ANDA',
        'X-Clinic-Secret: SECRET_KLINIK_ANDA'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}
```

---

## 2. Panduan untuk Yii Framework 1 (PHP)

### Component untuk Koneksi SATUSEHAT

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
    
    public function kirimBundle($data) {
        return $this->sendRequest('/api/registration/bundle', $data);
    }
    
    public function kirimEncounter($data) {
        return $this->sendRequest('/api/encounter', $data);
    }
    
    public function kirimCondition($data) {
        return $this->sendRequest('/api/condition', $data);
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

### Konfigurasi Component

Tambahkan konfigurasi di file `protected/config/main.php`:

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
            'baseUrl' => 'http://localhost:8000', // Ganti dengan URL service SATUSEHAT Anda
            'clinicCode' => 'KODE_KLINIK_ANDA',
            'clinicSecret' => 'SECRET_KLINIK_ANDA'
        ),
        // ... komponen lainnya
    ),
    
    // ... konfigurasi lainnya
);
```

### Controller untuk Pengiriman Bundle

```php
<?php
// protected/controllers/SatusehatController.php
class SatusehatController extends Controller {
    
    public function actionKirimBundle() {
        // Data yang akan dikirim
        $data = array(
            'encounter' => array(
                'registration_number' => 'REG20231201001',
                'arrived' => '2023-12-01T08:00:00+07:00',
                'in_progress_start' => '2023-12-01T08:30:00+07:00',
                'in_progress_end' => '2023-12-01T09:00:00+07:00',
                'finished' => '2023-12-01T09:30:00+07:00',
                'consultation_method' => 'RAJAL',
                'patient_id' => 'satusehat_patient_id',
                'patient_name' => 'Nama Pasien',
                'practitioner_id' => 'satusehat_practitioner_id',
                'practitioner_name' => 'Nama Dokter',
                'location_id' => 'satusehat_location_id',
                'location_name' => 'Nama Poli'
            ),
            'conditions' => array(
                array(
                    'icd10_code' => 'A00.0',
                    'patient_id' => 'satusehat_patient_id',
                    'patient_name' => 'Nama Pasien',
                    'clinical_status' => 'active',
                    'category' => 'Diagnosis',
                    'onset_date_time' => '2023-12-01T08:00:00+07:00',
                    'recorded_date' => '2023-12-01T08:00:00+07:00'
                ),
                array(
                    'icd10_code' => 'B01.1',
                    'patient_id' => 'satusehat_patient_id',
                    'patient_name' => 'Nama Pasien',
                    'clinical_status' => 'active',
                    'category' => 'Diagnosis'
                )
            )
        );

        try {
            // Kirim ke service SATUSEHAT
            $result = Yii::app()->satusehat->kirimBundle($data);
            
            // Hasil sukses
            echo CJSON::encode($result);
        } catch (Exception $e) {
            // Tangani error
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, 'application.satusehat');
            
            header('Content-Type: application/json');
            header('HTTP/1.1 500 Internal Server Error');
            echo CJSON::encode(array(
                'error' => $e->getMessage()
            ));
        }
    }
}
```

---

## 3. Contoh Data Lengkap

Berikut adalah contoh lengkap data yang dikirim ke endpoint bundle:

```json
{
  "encounter": {
    "registration_number": "REG20231201001",
    "arrived": "2023-12-01T08:00:00+07:00",
    "in_progress_start": "2023-12-01T08:30:00+07:00",
    "in_progress_end": "2023-12-01T09:00:00+07:00",
    "finished": "2023-12-01T09:30:00+07:00",
    "consultation_method": "RAJAL",
    "patient_id": "satusehat_patient_id",
    "patient_name": "Nama Pasien",
    "practitioner_id": "satusehat_practitioner_id",
    "practitioner_name": "Nama Dokter",
    "location_id": "satusehat_location_id",
    "location_name": "Nama Poli"
  },
  "conditions": [
    {
      "icd10_code": "A00.0",
      "patient_id": "satusehat_patient_id",
      "patient_name": "Nama Pasien",
      "clinical_status": "active",
      "category": "Diagnosis",
      "onset_date_time": "2023-12-01T08:00:00+07:00",
      "recorded_date": "2023-12-01T08:00:00+07:00"
    },
    {
      "icd10_code": "B01.1",
      "patient_id": "satusehat_patient_id",
      "patient_name": "Nama Pasien",
      "clinical_status": "active",
      "category": "Diagnosis"
    }
  ]
}
```

---

## 4. Penanganan Error

### Response Sukses:
```json
{
  "meta": {
    "code": 200,
    "message": "Bundle sent successfully",
    "clinic_id": "KODE_KLINIK"
  },
  "data": {
    "id": "resource_id_dari_satusehat"
  }
}
```

### Response Error:
```json
{
  "meta": {
    "code": 500,
    "message": "Pesan error",
    "clinic_id": "KODE_KLINIK"
  },
  "data": {
    "error_detail": "Detail error"
  }
}
```

### Duplicate Entry:
```json
{
  "meta": {
    "code": 201,
    "message": "Duplicate in SATUSEHAT",
    "clinic_id": "KODE_KLINIK"
  },
  "data": null
}
```

---

## 5. Tips dan Trik

1. Pastikan semua ID SATUSEHAT yang digunakan (patient_id, practitioner_id, location_id) sudah terdaftar di SATUSEHAT
2. Gunakan format tanggal ISO 8601 yang benar
3. Periksa kembali kode ICD-10 pada kondisi/diagnosis
4. Simpan ID SATUSEHAT yang sudah didapat untuk digunakan kembali
5. Gunakan environment yang sesuai (STAGING/PRODUCTION)
6. Aktifkan logging untuk keperluan troubleshooting
7. Pastikan koneksi internet stabil karena proses bisa memakan waktu cukup lama
