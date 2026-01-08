# Panduan Pengiriman Data Bundle SATUSEHAT

Dokumen ini menjelaskan cara mengirim data bundle SATUSEHAT (Encounter + Conditions) dari berbagai platform: CodeIgniter 3, Yii Framework 1, dan Postman.

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

## Contoh Data Lengkap

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

## 1. Panduan untuk CodeIgniter 3 (PHP)

### Controller

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SatusehatController extends CI_Controller {

    public function kirim_bundle()
    {
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
                ]
            ]
        ];

        // Kirim ke service SATUSEHAT
        $response = $this->kirim_ke_satusehat($data);
        
        // Tampilkan hasil
        echo json_encode($response);
    }

    private function kirim_ke_satusehat($data)
    {
        $url = 'http://localhost:8000/api/registration/bundle';
        
        $options = [
            'http' => [
                'header' => [
                    "Content-Type: application/json\r\n",
                    "X-Clinic-Code: KODE_KLINIK_ANDA\r\n",
                    "X-Clinic-Secret: SECRET_KLINIK_ANDA\r\n"
                ],
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        return json_decode($result, true);
    }
}
```

### Alternatif menggunakan cURL

```php
private function kirim_ke_satusehat_curl($data)
{
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

## 2. Panduan untuk Yii Framework 1 (PHP)

### Controller

```php
<?php
class SatusehatController extends Controller
{
    public function actionKirimBundle()
    {
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
                ]
            ]
        ];

        $response = $this->kirimKeSatusehat($data);
        
        echo json_encode($response);
    }

    private function kirimKeSatusehat($data)
    {
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
}
```

## 3. Panduan untuk Postman

### Langkah-langkah:

1. Buka Postman
2. Buat request baru dengan metode `POST`
3. Masukkan URL: `http://localhost:8000/api/registration/bundle`
4. Pada tab **Headers**, tambahkan:
   - Key: `Content-Type`, Value: `application/json`
   - Key: `X-Clinic-Code`, Value: `KODE_KLINIK_ANDA`
   - Key: `X-Clinic-Secret`, Value: `SECRET_KLINIK_ANDA`
5. Pada tab **Body**, pilih `raw` dan pilih format `JSON`, lalu masukkan data:

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

6. Klik tombol **Send**

## Penanganan Error

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

## Catatan Penting

- Pastikan klinik sudah terdaftar dan memiliki akses SATUSEHAT
- Semua field wajib harus diisi sesuai dengan format yang ditentukan
- Waktu harus dalam format ISO 8601
- ID patient, practitioner, dan location harus merupakan ID SATUSEHAT yang valid