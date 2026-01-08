# Dokumentasi Pengiriman Data ke SATUSEHAT

## Panduan Penggunaan Layanan untuk Klinik

Layanan ini dirancang agar mudah digunakan oleh klinik untuk mengintegrasikan data mereka ke SATUSEHAT. Berikut adalah panduan untuk klinik dalam menggunakan layanan ini:

### 1. Endpoint API

Semua endpoint memerlukan header otentikasi klinik:

```
X-Clinic-Code: [kode klinik]
X-Clinic-Secret: [secret klinik]
```

### 2. Fungsi Utama

#### A. Mengambil ID SATUSEHAT

**Mengambil ID Pasien berdasarkan NIK:**
```
GET /api/satusehat/get-patient-nik
Parameter: nik (string, 16 digit)
```

**Mengambil ID Dokter berdasarkan NIK/SIP/STR:**
```
GET /api/satusehat/get-practitioner-id
Parameter: nik (opsional), sip (opsional), str (opsional)
```

**Mengambil ID Poli berdasarkan kode:**
```
GET /api/satusehat/get-healthcare-service-id
Parameter: kode_poli (string)
```

#### B. Registrasi Data Dasar

**Mendaftarkan Pasien Baru:**
```
POST /api/satusehat/register-patient
Body:
{
  "nik": "string, 16 digit",
  "name": "string",
  "birth_date": "date",
  "gender": "L/P",
  "address": "string",
  "province_code": "string",
  "city_code": "string",
  "district_code": "string",
  "village_code": "string",
  "phone": "string",
  "nationality": "string"
}
```

**Mendaftarkan Dokter Baru:**
```
POST /api/satusehat/register-practitioner
Body:
{
  "nik": "string, 16 digit",
  "name": "string",
  "sip": "string",
  "str": "string",
  "birth_date": "date",
  "gender": "L/P",
  "address": "string",
  "phone": "string",
  "email": "email"
}
```

#### C. Pengiriman Data Klinis

**Mengirim Data Kunjungan:**
```
POST /api/satusehat/send-encounter
Body:
{
  "patient_id": "string",
  "practitioner_id": "string",
  "status": "string",
  "class_code": "string",
  "class_display": "string",
  "period_start": "datetime",
  "period_end": "datetime",
  "type_code": "string (opsional)",
  "type_display": "string (opsional)",
  "reason_code": "string (opsional)",
  "reason_display": "string (opsional)"
}
```

**Mengirim Data Observasi:**
```
POST /api/satusehat/send-observation
Body:
{
  "patient_id": "string",
  "category_code": "string",
  "category_display": "string",
  "code": {
    "code": "string",
    "display": "string",
    "system": "string (opsional)"
  },
  "value": "mixed",
  "value_type": "string (quantity, string, boolean, integer, range, ratio, sampled-data, time, datetime, period)",
  "effective_date": "datetime (opsional)",
  "status": "string (opsional)",
  "practitioner_id": "string (opsional)",
  "encounter_id": "string (opsional)"
}
```

**Mengirim Data Prosedur:**
```
POST /api/satusehat/send-procedure
Body:
{
  "patient_id": "string",
  "practitioner_id": "string",
  "code": {
    "code": "string",
    "display": "string",
    "system": "string (opsional)"
  },
  "category_code": "string (opsional)",
  "category_display": "string (opsional)",
  "performed_date": "datetime (opsional)",
  "status": "string (opsional)",
  "encounter_id": "string (opsional)"
}
```

**Mengirim Data Kondisi (Diagnosis):**
```
POST /api/satusehat/send-condition
Body:
{
  "patient_id": "string",
  "code": {
    "code": "string",
    "display": "string",
    "system": "string (opsional)"
  },
  "clinical_status": "string (opsional)",
  "verification_status": "string (opsional)",
  "onset_date": "datetime (opsional)",
  "recorded_date": "datetime (opsional)",
  "practitioner_id": "string (opsional)",
  "encounter_id": "string (opsional)"
}
```

### 3. Contoh Penggunaan

Berikut adalah contoh cara menggunakan layanan ini dari sisi klinik:

```php
<?php
// Contoh koneksi ke layanan SATUSEHAT
class SatuSehatClient {
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
        return $this->sendRequest('/api/satusehat/get-patient-nik', ['nik' => $nik], 'GET');
    }
    
    // Dapatkan ID SATUSEHAT dokter
    public function getPractitionerId($nik, $sip = null, $str = null) {
        $data = ['nik' => $nik];
        if ($sip) $data['sip'] = $sip;
        if ($str) $data['str'] = $str;
        
        return $this->sendRequest('/api/satusehat/get-practitioner-id', $data, 'GET');
    }
    
    // Dapatkan ID SATUSEHAT poli
    public function getHealthcareServiceId($kodePoli) {
        return $this->sendRequest('/api/satusehat/get-healthcare-service-id', ['kode_poli' => $kodePoli], 'GET');
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
```

### 4. Alur Pengiriman Data

1. **Persiapkan Data Dasar**:
   - Dapatkan ID SATUSEHAT untuk pasien, dokter, dan poli
   - Jika belum ada, daftarkan data dasar terlebih dahulu

2. **Kirim Data Klinis**:
   - Kirim data kunjungan (Encounter)
   - Kirim data observasi (Observation)
   - Kirim data prosedur (Procedure)
   - Kirim data diagnosis (Condition)

### 5. Penanganan Kesalahan

- Pastikan semua data yang dikirim telah sesuai dengan standar SATUSEHAT
- Gunakan format tanggal dan waktu yang benar (ISO 8601)
- Pastikan NIK dan kode unik lainnya valid
- Periksa kembali header otentikasi klinik

### 6. Error Codes

- `PASIENT_NOT_FOUND`: Pasien tidak ditemukan di SATUSEHAT
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