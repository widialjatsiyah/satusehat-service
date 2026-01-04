# Dokumentasi Pengujian SATUSEHAT Service dengan Postman

## Daftar Isi
1. [Persiapan Awal](#persiapan-awal)
2. [Konfigurasi Postman](#konfigurasi-postman)
3. [Pengujian Endpoint](#pengujian-endpoint)
4. [Pengujian Lengkap](#pengujian-lengkap)
5. [Troubleshooting](#troubleshooting)

## Persiapan Awal

### 1. Instalasi Postman
Pastikan Anda telah menginstal aplikasi Postman terbaru dari situs resmi: https://www.postman.com/downloads/

### 2. Kredensial Klinik
Sebelum melakukan pengujian, pastikan Anda memiliki:
- `CLINIC_CODE`: Kode unik klinik
- `CLINIC_SECRET`: Secret untuk otentikasi
- `BASE_URL`: URL layanan SATUSEHAT Service

## Konfigurasi Postman

### 1. Membuat Environment Baru
1. Klik ikon gear di pojok kanan atas
2. Pilih "Manage Environments"
3. Klik "Add" untuk membuat environment baru
4. Beri nama environment, misalnya: `satusehat-dev`
5. Tambahkan variabel berikut:
   - `base_url`: URL dasar service SATUSEHAT (misalnya: `https://your-satusehat-service.com`)
   - `clinic_code`: Kode klinik Anda
   - `clinic_secret`: Secret klinik Anda

### 2. Membuat Collection Baru
1. Klik tombol "New" di kiri atas
2. Pilih "Collection"
3. Beri nama collection, misalnya: `SATUSEHAT Service Tests`
4. Klik "Create"

## Pengujian Endpoint

### 1. Pengujian Otentikasi

#### Endpoint: `/api/encounter` (tanpa header otentikasi)
1. Klik "Add Request" di dalam collection Anda
2. Beri nama: `Test Auth - Missing Headers`
3. Pilih method: `POST`
4. Masukkan URL: `{{base_url}}/api/encounter`
5. Pada tab "Headers", tambahkan:
   - Key: `X-Clinic-Code`, Value: `{{clinic_code}}`
   - Key: `X-Clinic-Secret`, Value: `{{clinic_secret}}`
6. Pada tab "Body", pilih "raw" dan JSON, lalu masukkan:
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

### 2. Pengujian Pencarian ID SATUSEHAT

#### Endpoint: `/api/satusehat/get-patient-id`
1. Tambahkan request baru ke collection: `Get Patient ID`
2. Method: `POST`
3. URL: `{{base_url}}/api/satusehat/get-patient-id`
4. Headers:
   - `X-Clinic-Code`: `{{clinic_code}}`
   - `X-Clinic-Secret`: `{{clinic_secret}}`
   - `Content-Type`: `application/json`
5. Body:
```json
{
  "nik": "3201234567890123"
}
```

#### Endpoint: `/api/satusehat/get-practitioner-id`
1. Tambahkan request baru: `Get Practitioner ID`
2. Method: `POST`
3. URL: `{{base_url}}/api/satusehat/get-practitioner-id`
4. Headers sama seperti di atas
5. Body:
```json
{
  "nik": "3201234567890123",
  "sip": "1234567890",
  "str": "0987654321"
}
```

#### Endpoint: `/api/satusehat/get-healthcare-service-id`
1. Tambahkan request baru: `Get Healthcare Service ID`
2. Method: `POST`
3. URL: `{{base_url}}/api/satusehat/get-healthcare-service-id`
4. Headers sama seperti di atas
5. Body:
```json
{
  "kode_poli": "100001"
}
```

### 3. Pengujian Registrasi Data

#### Endpoint: `/api/satusehat/register-patient`
1. Tambahkan request baru: `Register Patient`
2. Method: `POST`
3. URL: `{{base_url}}/api/satusehat/register-patient`
4. Headers:
   - `X-Clinic-Code`: `{{clinic_code}}`
   - `X-Clinic-Secret`: `{{clinic_secret}}`
   - `Content-Type`: `application/json`
5. Body:
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

#### Endpoint: `/api/satusehat/register-practitioner`
1. Tambahkan request baru: `Register Practitioner`
2. Method: `POST`
3. URL: `{{base_url}}/api/satusehat/register-practitioner`
4. Headers sama seperti di atas
5. Body:
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

### 4. Pengujian Pengiriman Data Klinis

#### Endpoint: `/api/encounter`
1. Tambahkan request baru: `Send Encounter`
2. Method: `POST`
3. URL: `{{base_url}}/api/encounter`
4. Headers:
   - `X-Clinic-Code`: `{{clinic_code}}`
   - `X-Clinic-Secret`: `{{clinic_secret}}`
   - `Content-Type`: `application/json`
5. Body:
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

#### Endpoint: `/api/diagnosis`
1. Tambahkan request baru: `Send Diagnosis`
2. Method: `POST`
3. URL: `{{base_url}}/api/diagnosis`
4. Headers sama seperti di atas
5. Body:
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

#### Endpoint: `/api/observation`
1. Tambahkan request baru: `Send Observation`
2. Method: `POST`
3. URL: `{{base_url}}/api/observation`
4. Headers sama seperti di atas
5. Body:
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

#### Endpoint: `/api/procedure`
1. Tambahkan request baru: `Send Procedure`
2. Method: `POST`
3. URL: `{{base_url}}/api/procedure`
4. Headers sama seperti di atas
5. Body:
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

## Pengujian Lengkap

### 1. Alur Pengujian Terpadu
Untuk pengujian lengkap, ikuti urutan berikut:

1. **Cek apakah pasien sudah terdaftar** - Gunakan `/api/satusehat/get-patient-id`
2. **Jika belum terdaftar, registrasi pasien** - Gunakan `/api/satusehat/register-patient`
3. **Cek apakah dokter sudah terdaftar** - Gunakan `/api/satusehat/get-practitioner-id`
4. **Jika belum terdaftar, registrasi dokter** - Gunakan `/api/satusehat/register-practitioner`
5. **Kirim data kunjungan** - Gunakan `/api/encounter`
6. **Kirim data diagnosis** - Gunakan `/api/diagnosis`
7. **Kirim data observasi** - Gunakan `/api/observation`
8. **Kirim data prosedur** - Gunakan `/api/procedure`

### 2. Menggunakan Pre-request Script dan Tests
Untuk otomasi, Anda bisa menambahkan script di Postman:

#### Di tab "Pre-request Script" untuk request yang bergantung pada response sebelumnya:
```javascript
// Ambil ID dari variabel global
pm.globals.set("encounter_id", "encounter-12345");
pm.globals.set("pasien_id", "P00123456789");
```

#### Di tab "Tests" untuk menyimpan ID dari response:
```javascript
// Simpan ID dari response ke variabel global untuk digunakan di request berikutnya
const responseJson = pm.response.json();
if(responseJson.satusehat_id) {
    pm.globals.set("satusehat_id", responseJson.satusehat_id);
}
if(responseJson.id) {
    pm.globals.set("resource_id", responseJson.id);
}
```

## Troubleshooting

### 1. Error Umum dan Solusi

#### Error 401 - Unauthorized
- **Penyebab**: Kredensial klinik salah atau tidak dikirim
- **Solusi**: Pastikan `X-Clinic-Code` dan `X-Clinic-Secret` benar dan terkirim di header

#### Error 422 - Unprocessable Entity
- **Penyebab**: Data yang dikirim tidak valid atau tidak lengkap
- **Solusi**: Cek format data sesuai dokumentasi dan pastikan semua field wajib terisi

#### Error 500 - Internal Server Error
- **Penyebab**: Kesalahan di sisi server
- **Solusi**: Cek log server dan pastikan koneksi ke SATUSEHAT berjalan dengan baik

### 2. Tips Pengujian
- Gunakan environment yang berbeda untuk development, staging, dan production
- Gunakan variabel global untuk menyimpan ID yang didapat dari response
- Gunakan Collection Runner untuk menjalankan pengujian otomatis
- Gunakan Newman untuk menjalankan collection dari command line

### 3. Monitoring dan Logging
- Gunakan tab "Console" di Postman (View > Show Postman Console) untuk melihat log permintaan
- Cek response body dan status code untuk memastikan permintaan berhasil
- Gunakan "Test Results" untuk melihat hasil dari script test