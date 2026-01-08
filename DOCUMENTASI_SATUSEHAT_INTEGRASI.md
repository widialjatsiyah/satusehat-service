# Dokumentasi Integrasi SATUSEHAT

## Daftar Isi
1. [Deskripsi Umum](#deskripsi-umum)
2. [Arsitektur Integrasi](#arsitektur-integrasi)
3. [Endpoint API](#endpoint-api)
4. [Contoh Penggunaan](#contoh-penggunaan)
5. [Pengelolaan Antrian](#pengelolaan-antrian)
6. [Logging](#logging)
7. [Integrasi dengan Sistem Eksternal](#integrasi-dengan-sistem-eksternal)
8. [Contoh Koding untuk Sistem Eksternal](#contoh-koding-untuk-sistem-eksternal)

## Deskripsi Umum

Proyek ini mengimplementasikan integrasi dengan platform SATUSEHAT menggunakan package `ivanwilliammd/satusehat-integration`. Integrasi mencakup pengiriman data pasien, dokter, encounter, observation, procedure, dan diagnosis (condition).

## Arsitektur Integrasi

Arsitektur integrasi terdiri dari beberapa komponen utama:

- `SatuSehatService`: Service utama yang menangani semua interaksi dengan SATUSEHAT
- `SatusehatIdController`: Controller untuk registrasi dan pencarian ID SATUSEHAT
- `EncounterController`: Controller untuk pengiriman data secara async
- `SendEncounterToSatusehat`: Job untuk mengirim data ke SATUSEHAT secara asynchronous
- `SatusehatLog`: Model untuk logging semua interaksi dengan SATUSEHAT

## Endpoint API

### Endpoint Sinkron (Langsung)

Endpoint ini akan langsung mengirim data ke SATUSEHAT dan menunggu respon:

```
POST /satusehat/get-patient-id
POST /satusehat/get-practitioner-id
POST /satusehat/get-healthcare-service-id
POST /satusehat/register-patient
POST /satusehat/register-practitioner
POST /satusehat/send-encounter
POST /satusehat/send-observation
POST /satusehat/send-procedure
POST /satusehat/send-condition
```

### Endpoint Asinkron (Antrian)

Endpoint ini akan menambahkan permintaan ke antrian dan diproses secara asynchronous:

```
POST /send-patient-async
POST /send-practitioner-async
POST /send-encounter-async
POST /send-observation-async
POST /send-procedure-async
POST /send-condition-async
```

## Contoh Penggunaan

### Registrasi Pasien

**Endpoint**: `POST /satusehat/register-patient`

**Payload**:
```json
{
    "nik": "1234567890123456",
    "name": "John Doe",
    "birth_date": "1990-01-01",
    "gender": "L",
    "address": "Jl. Contoh No. 123",
    "province_code": "31",
    "city_code": "3171",
    "district_code": "317101",
    "village_code": "3171011001",
    "phone": "+6281234567890",
    "nationality": "ID"
}
```

### Registrasi Dokter

**Endpoint**: `POST /satusehat/register-practitioner`

**Payload**:
```json
{
    "nik": "1234567890123456",
    "name": "Dr. Jane Doe",
    "sip": "1234567890",
    "str": "0987654321",
    "birth_date": "1985-05-15",
    "gender": "P",
    "address": "Jl. Contoh No. 456",
    "phone": "+6281234567890",
    "email": "jane.doe@example.com"
}
```

### Pengiriman Encounter

**Endpoint**: `POST /satusehat/send-encounter`

**Payload**:
```json
{
    "patient_id": "satusehat_patient_id",
    "practitioner_id": "satusehat_practitioner_id",
    "status": "finished",
    "class_code": "AMB",
    "class_display": "ambulatory",
    "period_start": "2023-01-01T08:00:00Z",
    "period_end": "2023-01-01T09:00:00Z",
    "type_code": "RAN",
    "type_display": "Ranap"
}
```

### Pengiriman Observation

**Endpoint**: `POST /satusehat/send-observation`

**Payload**:
```json
{
    "patient_id": "satusehat_patient_id",
    "category_code": "vital-signs",
    "category_display": "Vital Signs",
    "code": {
        "code": "8480-6",
        "display": "Systolic Blood Pressure",
        "system": "http://loinc.org"
    },
    "value": 120,
    "value_type": "quantity"
}
```

### Pengiriman Procedure

**Endpoint**: `POST /satusehat/send-procedure`

**Payload**:
```json
{
    "patient_id": "satusehat_patient_id",
    "practitioner_id": "satusehat_practitioner_id",
    "code": {
        "code": "123456",
        "display": "Sample Procedure",
        "system": "http://snomed.info/sct"
    }
}
```

### Pengiriman Condition (Diagnosis)

**Endpoint**: `POST /satusehat/send-condition`

**Payload**:
```json
{
    "patient_id": "satusehat_patient_id",
    "code": {
        "code": "T81.0",
        "display": "Postprocedural shock",
        "system": "http://snomed.info/sct"
    }
}
```

## Pengelolaan Antrian

Untuk menjalankan antrian pengiriman data ke SATUSEHAT, gunakan perintah:

```bash
php artisan queue:work
```

Atau untuk penggunaan produksi, gunakan Supervisor:

```bash
sudo supervisorctl start all
```

## Logging

Semua interaksi dengan SATUSEHAT akan dicatat di tabel `satusehat_logs` dengan informasi:

- ID Klinik
- Jenis resource (Patient, Practitioner, Encounter, dll)
- Payload permintaan
- Payload respon
- Status (SUCCESS/FAILED/PENDING)
- Jumlah percobaan ulang

## Konfigurasi Lingkungan

Pastikan untuk mengatur variabel lingkungan yang diperlukan untuk koneksi ke SATUSEHAT:

```
SATUSEHAT_ENV=STG
SATUSEHAT_TIMEOUT=60
```

## Penanganan Kesalahan

Setiap permintaan ke SATUSEHAT memiliki mekanisme retry otomatis hingga 5 kali sebelum gagal permanen. Waktu tunggu antar percobaan akan meningkat secara eksponensial.

## Integrasi dengan Sistem Eksternal

Sistem eksternal (seperti SimKlinik yang mungkin dibangun dengan Yii1 atau CI3) dapat berinteraksi dengan layanan SATUSEHAT ini menggunakan API HTTP sederhana. Berikut adalah pendekatan umum:

1. Sistem eksternal mengirimkan data ke layanan ini melalui API
2. Layanan ini menangani autentikasi SATUSEHAT, pembuatan FHIR resource, dan pengiriman data
3. Respon dikembalikan ke sistem eksternal

## Contoh Koding untuk Sistem Eksternal

### Contoh Implementasi di Yii1 Framework

Contoh untuk mengirim data pasien dari aplikasi Yii1 ke layanan SATUSEHAT:

```php
<?php
// File: protected/components/SatusehatIntegration.php

class SatusehatIntegration
{
    private $baseUrl;
    private $clinicKey;
    
    public function __construct($clinicKey)
    {
        $this->baseUrl = 'http://your-satusehat-service.com/api';
        $this->clinicKey = $clinicKey;
    }
    
    public function sendPatient($patientData)
    {
        $url = $this->baseUrl . '/satusehat/register-patient';
        
        $postData = [
            'nik' => $patientData['nik'],
            'name' => $patientData['name'],
            'birth_date' => $patientData['birth_date'],
            'gender' => $patientData['gender'],
            'address' => $patientData['address'],
            'province_code' => $patientData['province_code'],
            'city_code' => $patientData['city_code'],
            'district_code' => $patientData['district_code'],
            'village_code' => $patientData['village_code'],
            'phone' => $patientData['phone'],
            'nationality' => $patientData['nationality']
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n" .
                           "X-Clinic-Key: {$this->clinicKey}\r\n",
                'method' => 'POST',
                'content' => json_encode($postData)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            throw new Exception('Gagal mengirim data pasien ke SATUSEHAT');
        }
        
        return json_decode($result, true);
    }
    
    public function sendEncounter($encounterData)
    {
        $url = $this->baseUrl . '/satusehat/send-encounter';
        
        $postData = [
            'patient_id' => $encounterData['patient_id'],
            'practitioner_id' => $encounterData['practitioner_id'],
            'status' => $encounterData['status'],
            'class_code' => $encounterData['class_code'],
            'class_display' => $encounterData['class_display'],
            'period_start' => $encounterData['period_start'],
            'period_end' => $encounterData['period_end']
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n" .
                           "X-Clinic-Key: {$this->clinicKey}\r\n",
                'method' => 'POST',
                'content' => json_encode($postData)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            throw new Exception('Gagal mengirim data encounter ke SATUSEHAT');
        }
        
        return json_decode($result, true);
    }
    
    // Fungsi serupa untuk mengirim data lainnya (practitioner, observation, procedure, condition)
}
```

Contoh penggunaan di controller Yii1:

```php
<?php
// File: protected/controllers/PatientController.php

class PatientController extends Controller
{
    public function actionRegisterToSatusehat($id)
    {
        $patient = Patient::model()->findByPk($id);
        
        if (!$patient) {
            throw new CHttpException(404, 'Pasien tidak ditemukan');
        }
        
        $satusehat = new SatusehatIntegration(Yii::app()->params['clinicKey']);
        
        try {
            $patientData = [
                'nik' => $patient->nik,
                'name' => $patient->name,
                'birth_date' => $patient->birth_date,
                'gender' => $patient->gender,
                'address' => $patient->address,
                'province_code' => $patient->province_code,
                'city_code' => $patient->city_code,
                'district_code' => $patient->district_code,
                'village_code' => $patient->village_code,
                'phone' => $patient->phone,
                'nationality' => $patient->nationality
            ];
            
            $result = $satusehat->sendPatient($patientData);
            
            if ($result['status'] === 'success') {
                Yii::app()->user->setFlash('success', 'Pasien berhasil didaftarkan ke SATUSEHAT');
            } else {
                Yii::app()->user->setFlash('error', 'Gagal mendaftarkan pasien ke SATUSEHAT: ' . $result['message']);
            }
        } catch (Exception $e) {
            Yii::app()->user->setFlash('error', 'Error: ' . $e->getMessage());
        }
        
        $this->redirect(['view', 'id' => $id]);
    }
}
```

### Contoh Implementasi di CodeIgniter 3

Contoh untuk mengirim data dari aplikasi CI3 ke layanan SATUSEHAT:

```php
<?php
// File: application/libraries/Satusehat_api.php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Satusehat_api {
    
    private $ci;
    private $base_url;
    private $clinic_key;
    
    public function __construct($params = array())
    {
        $this->ci =& get_instance();
        $this->ci->load->config('satusehat');
        
        $this->base_url = $this->ci->config->item('satusehat_base_url');
        $this->clinic_key = $this->ci->config->item('clinic_key');
    }
    
    private function _make_request($endpoint, $data, $method = 'POST')
    {
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->base_url . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "X-Clinic-Key: " . $this->clinic_key
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception('cURL Error: ' . $err);
        }
        
        $result = json_decode($response, true);
        
        if ($http_code >= 400) {
            throw new Exception('HTTP Error: ' . $http_code . ' - ' . json_encode($result));
        }
        
        return $result;
    }
    
    public function register_patient($patient_data)
    {
        return $this->_make_request('/satusehat/register-patient', $patient_data);
    }
    
    public function register_practitioner($practitioner_data)
    {
        return $this->_make_request('/satusehat/register-practitioner', $practitioner_data);
    }
    
    public function send_encounter($encounter_data)
    {
        return $this->_make_request('/satusehat/send-encounter', $encounter_data);
    }
    
    public function send_observation($observation_data)
    {
        return $this->_make_request('/satusehat/send-observation', $observation_data);
    }
    
    public function send_procedure($procedure_data)
    {
        return $this->_make_request('/satusehat/send-procedure', $procedure_data);
    }
    
    public function send_condition($condition_data)
    {
        return $this->_make_request('/satusehat/send-condition', $condition_data);
    }
    
    // Method untuk mencari ID SATUSEHAT
    public function get_patient_id($nik)
    {
        $data = array('nik' => $nik);
        return $this->_make_request('/satusehat/get-patient-id', $data);
    }
}
```

Contoh konfigurasi CI3 (`application/config/satusehat.php`):

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['satusehat_base_url'] = 'http://your-satusehat-service.com/api';
$config['clinic_key'] = 'your_clinic_unique_key';
```

Contoh penggunaan di controller CI3:

```php
<?php
// File: application/controllers/Satusehat.php

defined('BASEPATH') OR exit('No direct script access allowed');

class Satusehat extends CI_Controller {
    
    public function __construct()
    {
        parent::__construct();
        $this->load->library('satusehat_api');
        $this->load->model('patient_model');
        $this->load->model('encounter_model');
    }
    
    public function register_patient($patient_id)
    {
        $patient = $this->patient_model->get_by_id($patient_id);
        
        if (!$patient) {
            show_error('Pasien tidak ditemukan', 404);
            return;
        }
        
        $patient_data = array(
            'nik' => $patient['nik'],
            'name' => $patient['name'],
            'birth_date' => $patient['birth_date'],
            'gender' => $patient['gender'],
            'address' => $patient['address'],
            'province_code' => $patient['province_code'],
            'city_code' => $patient['city_code'],
            'district_code' => $patient['district_code'],
            'village_code' => $patient['village_code'],
            'phone' => $patient['phone'],
            'nationality' => $patient['nationality']
        );
        
        try {
            $result = $this->satusehat_api->register_patient($patient_data);
            
            if ($result['status'] === 'success') {
                $this->session->set_flashdata('success', 'Pasien berhasil didaftarkan ke SATUSEHAT');
            } else {
                $this->session->set_flashdata('error', 'Gagal mendaftarkan pasien: ' . $result['message']);
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Error: ' . $e->getMessage());
        }
        
        redirect('patients/view/' . $patient_id);
    }
    
    public function send_encounter($encounter_id)
    {
        $encounter = $this->encounter_model->get_by_id($encounter_id);
        $patient = $this->patient_model->get_by_id($encounter['patient_id']);
        
        // Dapatkan ID SATUSEHAT untuk pasien dan dokter
        $patient_satusehat_id = $this->get_or_register_patient($patient['id']);
        $practitioner_satusehat_id = $this->get_or_register_practitioner($encounter['doctor_id']);
        
        $encounter_data = array(
            'patient_id' => $patient_satusehat_id,
            'practitioner_id' => $practitioner_satusehat_id,
            'status' => 'finished',
            'class_code' => 'AMB',
            'class_display' => 'ambulatory',
            'period_start' => $encounter['start_time'],
            'period_end' => $encounter['end_time']
        );
        
        try {
            $result = $this->satusehat_api->send_encounter($encounter_data);
            
            if ($result['status'] === 'success') {
                $this->session->set_flashdata('success', 'Encounter berhasil dikirim ke SATUSEHAT');
            } else {
                $this->session->set_flashdata('error', 'Gagal mengirim encounter: ' . $result['message']);
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Error: ' . $e->getMessage());
        }
        
        redirect('encounters/view/' . $encounter_id);
    }
    
    private function get_or_register_patient($patient_id)
    {
        $patient = $this->patient_model->get_by_id($patient_id);
        
        // Coba dapatkan ID SATUSEHAT terlebih dahulu
        try {
            $result = $this->satusehat_api->get_patient_id($patient['nik']);
            if ($result['status'] === 'success') {
                return $result['satusehat_id'];
            }
        } catch (Exception $e) {
            log_message('debug', 'Pasien belum terdaftar di SATUSEHAT: ' . $e->getMessage());
        }
        
        // Jika tidak ditemukan, daftarkan pasien baru
        $patient_data = array(
            'nik' => $patient['nik'],
            'name' => $patient['name'],
            'birth_date' => $patient['birth_date'],
            'gender' => $patient['gender'],
            'address' => $patient['address'],
            'province_code' => $patient['province_code'],
            'city_code' => $patient['city_code'],
            'district_code' => $patient['district_code'],
            'village_code' => $patient['village_code'],
            'phone' => $patient['phone'],
            'nationality' => $patient['nationality']
        );
        
        $result = $this->satusehat_api->register_patient($patient_data);
        return $result['id'];
    }
    
    // Method serupa untuk dokter
    private function get_or_register_practitioner($doctor_id)
    {
        // Implementasi untuk mendapatkan atau mendaftarkan dokter
        // ...
    }
}
```

### Contoh untuk Mengirim Observation (Yii1)

```php
<?php
// File: protected/components/SatusehatIntegration.php (tambahkan method ini)

public function sendObservation($observationData)
{
    $url = $this->baseUrl . '/satusehat/send-observation';
    
    $postData = [
        'patient_id' => $observationData['patient_id'],
        'category_code' => $observationData['category_code'],
        'category_display' => $observationData['category_display'],
        'code' => $observationData['code'],
        'value' => $observationData['value'],
        'value_type' => $observationData['value_type']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n" .
                       "X-Clinic-Key: {$this->clinicKey}\r\n",
            'method' => 'POST',
            'content' => json_encode($postData)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        throw new Exception('Gagal mengirim data observation ke SATUSEHAT');
    }
    
    return json_decode($result, true);
}
```

### Contoh untuk Mengirim Observation (CI3)

```php
<?php
// File: application/libraries/Satusehat_api.php (tambahkan method ini)

public function send_observation($observation_data)
{
    return $this->_make_request('/satusehat/send-observation', $observation_data);
}
```

Dengan pendekatan ini, sistem eksternal hanya perlu mengirimkan data ke layanan SATUSEHAT kita, dan layanan ini yang akan menangani semua kompleksitas integrasi dengan SATUSEHAT sesuai dengan spesifikasi FHIR. Ini memungkinkan sistem yang berbeda (Yii1, CI3, dll.) untuk dengan mudah terintegrasi dengan SATUSEHAT tanpa perlu memahami kompleksitas protokol FHIR dan autentikasi SATUSEHAT.