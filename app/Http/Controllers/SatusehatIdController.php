<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Services\SatuSehatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SatusehatIdController extends Controller
{
    public function getPatientId(Request $request)
    {
        $clinic = $request->attributes->get('clinic');
        $satuSehatService = new SatuSehatService($clinic);

        $request->validate([
            'nik' => 'required|string|size:16'
        ]);

        try {
            // Coba cari pasien di SATUSEHAT berdasarkan NIK
            $accessToken = $satuSehatService->getAccessToken();
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/fhir+json',
            ])->get($satuSehatService->client->fhir_url . '/Patient', [
                'identifier' => 'http://sys-ids.kemkes.go.id/patient/' . $clinic->organization_id . '|' . $request->nik
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['entry']) && count($data['entry']) > 0) {
                    $patientId = $data['entry'][0]['resource']['id'];
                    return response()->json([
                        'status' => 'success',
                        'satusehat_id' => $patientId,
                        'message' => 'Patient ID found'
                    ]);
                } else {
                    return response()->json([
                        'status' => 'not_found',
                        'message' => 'Patient not found in SATUSEHAT'
                    ], 404);
                }
            } else {
                Log::error('Failed to get patient from SATUSEHAT', [
                    'clinic_id' => $clinic->id,
                    'response' => $response->body()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to query SATUSEHAT',
                    'details' => $response->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Exception in getPatientId', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getPractitionerId(Request $request)
    {
        $clinic = $request->attributes->get('clinic');
        $satuSehatService = new SatuSehatService($clinic);

        $request->validate([
            'nik' => 'nullable|string|size:16',
            'sip' => 'nullable|string',
            'str' => 'nullable|string'
        ]);

        try {
            $accessToken = $satuSehatService->getAccessToken();
            
            // Cek apakah pencarian menggunakan NIK
            if ($request->nik) {
                // Cari berdasarkan NIK
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/fhir+json',
                ])->get($satuSehatService->client->fhir_url . '/Practitioner', [
                    'identifier' => 'http://sys-ids.kemkes.go.id/practitioner/' . $clinic->organization_id . '|' . $request->nik
                ]);
            } else {
                // Gunakan SIP atau STR untuk pencarian
                $identifier = $request->sip ?: $request->str;
                $system = $request->sip ? 'http://sys-ids.kemkes.go.id/practitioner-sip/' . $clinic->organization_id : 'http://sys-ids.kemkes.go.id/practitioner-str/' . $clinic->organization_id;

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/fhir+json',
                ])->get($satuSehatService->client->fhir_url . '/Practitioner', [
                    'identifier' => $system . '|' . $identifier
                ]);
            }

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['entry']) && count($data['entry']) > 0) {
                    $practitionerId = $data['entry'][0]['resource']['id'];
                    return response()->json([
                        'status' => 'success',
                        'satusehat_id' => $practitionerId,
                        'message' => 'Practitioner ID found'
                    ]);
                } else {
                    return response()->json([
                        'status' => 'not_found',
                        'message' => 'Practitioner not found in SATUSEHAT'
                    ], 404);
                }
            } else {
                Log::error('Failed to get practitioner from SATUSEHAT', [
                    'clinic_id' => $clinic->id,
                    'response' => $response->body()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to query SATUSEHAT',
                    'details' => $response->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Exception in getPractitionerId', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getHealthcareServiceId(Request $request)
    {
        $clinic = $request->attributes->get('clinic');
        $satuSehatService = new SatuSehatService($clinic);

        $request->validate([
            'kode_poli' => 'required|string'
        ]);

        try {
            $accessToken = $satuSehatService->getAccessToken();
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/fhir+json',
            ])->get($satuSehatService->client->fhir_url . '/HealthcareService', [
                'identifier' => 'http://sys-ids.kemkes.go.id/healthcare-service/' . $clinic->organization_id . '|' . $request->kode_poli
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['entry']) && count($data['entry']) > 0) {
                    $serviceId = $data['entry'][0]['resource']['id'];
                    return response()->json([
                        'status' => 'success',
                        'satusehat_id' => $serviceId,
                        'message' => 'Healthcare service ID found'
                    ]);
                } else {
                    return response()->json([
                        'status' => 'not_found',
                        'message' => 'Healthcare service not found in SATUSEHAT'
                    ], 404);
                }
            } else {
                Log::error('Failed to get healthcare service from SATUSEHAT', [
                    'clinic_id' => $clinic->id,
                    'response' => $response->body()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to query SATUSEHAT',
                    'details' => $response->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Exception in getHealthcareServiceId', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function registerPatient(Request $request)
    {
        $clinic = $request->attributes->get('clinic');
        $satuSehatService = new SatuSehatService($clinic);

        $request->validate([
            'nik' => 'required|string|size:16',
            'nama' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'alamat' => 'required|string',
            'kode_provinsi' => 'required|string',
            'kode_kabupaten' => 'required|string',
            'kode_kecamatan' => 'required|string',
            'kode_kelurahan' => 'required|string',
            'no_hp' => 'required|string',
            'nama_ibu' => 'required|string',
            'gol_darah' => 'nullable|in:A,B,AB,O',
            'status_nikah' => 'nullable|string',
            'pekerjaan' => 'nullable|string'
        ]);

        try {
            // Format data sesuai standar FHIR untuk SATUSEHAT
            $fhirPatient = [
                'resourceType' => 'Patient',
                'id' => 'id-' . uniqid(),
                'identifier' => [
                    [
                        'system' => 'http://sys-ids.kemkes.go.id/patient/' . $clinic->organization_id,
                        'value' => $request->nik,
                        'type' => [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                    'code' => 'MR',
                                    'display' => 'Medical Record Number'
                                ]
                            ]
                        ]
                    ]
                ],
                'active' => true,
                'name' => [
                    [
                        'use' => 'official',
                        'text' => $request->nama
                    ]
                ],
                'gender' => strtolower($request->jenis_kelamin),
                'birthDate' => $request->tanggal_lahir,
                'address' => [
                    [
                        'type' => 'physical',
                        'line' => [$request->alamat],
                        'extension' => [
                            [
                                'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeGender',
                                'extension' => [
                                    [
                                        'url' => 'province',
                                        'valueCodeableConcept' => [
                                            'coding' => [
                                                [
                                                    'system' => 'http://sys-ids.kemkes.go.id/wilayah',
                                                    'code' => $request->kode_provinsi
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'url' => 'city',
                                        'valueCodeableConcept' => [
                                            'coding' => [
                                                [
                                                    'system' => 'http://sys-ids.kemkes.go.id/wilayah',
                                                    'code' => $request->kode_kabupaten
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'url' => 'district',
                                        'valueCodeableConcept' => [
                                            'coding' => [
                                                [
                                                    'system' => 'http://sys-ids.kemkes.go.id/wilayah',
                                                    'code' => $request->kode_kecamatan
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'url' => 'village',
                                        'valueCodeableConcept' => [
                                            'coding' => [
                                                [
                                                    'system' => 'http://sys-ids.kemkes.go.id/wilayah',
                                                    'code' => $request->kode_kelurahan
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'telecom' => [
                    [
                        'system' => 'phone',
                        'value' => $request->no_hp,
                        'use' => 'mobile'
                    ]
                ],
                'contact' => [
                    [
                        'relationship' => [
                            [
                                'coding' => [
                                    [
                                        'system' => 'http://terminology.hl7.org/CodeSystem/v2-0131',
                                        'code' => 'C',
                                        'display' => 'Emergency Contact'
                                    ]
                                ]
                            ]
                        ],
                        'name' => [
                            'text' => $request->nama_ibu
                        ],
                        'telecom' => [
                            [
                                'system' => 'phone',
                                'value' => $request->no_hp
                            ]
                        ]
                    ]
                ]
            ];

            // Kirim data ke SATUSEHAT
            $response = $satuSehatService->sendPatient($fhirPatient);
            
            return response()->json([
                'status' => 'success',
                'id' => $response['id'] ?? null,
                'message' => 'Patient registered successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Exception in registerPatient', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function registerPractitioner(Request $request)
    {
        $clinic = $request->attributes->get('clinic');
        $satuSehatService = new SatuSehatService($clinic);

        $request->validate([
            'nik' => 'required|string|size:16',
            'nama' => 'required|string',
            'sip' => 'required|string',
            'str' => 'required|string',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'alamat' => 'required|string',
            'kode_provinsi' => 'required|string',
            'kode_kabupaten' => 'required|string',
            'kode_kecamatan' => 'required|string',
            'kode_kelurahan' => 'required|string',
            'no_hp' => 'required|string',
            'email' => 'required|email',
            'spesialis' => 'required|string'
        ]);

        try {
            // Format data sesuai standar FHIR untuk SATUSEHAT
            $fhirPractitioner = [
                'resourceType' => 'Practitioner',
                'id' => 'id-' . uniqid(),
                'identifier' => [
                    [
                        'system' => 'http://sys-ids.kemkes.go.id/practitioner/' . $clinic->organization_id,
                        'value' => $request->nik,
                        'type' => [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                    'code' => 'NIK',
                                    'display' => 'NIK'
                                ]
                            ]
                        ]
                    ],
                    [
                        'system' => 'http://sys-ids.kemkes.go.id/practitioner-sip/' . $clinic->organization_id,
                        'value' => $request->sip,
                        'type' => [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                    'code' => 'SIP',
                                    'display' => 'Surat Izin Praktik'
                                ]
                            ]
                        ]
                    ],
                    [
                        'system' => 'http://sys-ids.kemkes.go.id/practitioner-str/' . $clinic->organization_id,
                        'value' => $request->str,
                        'type' => [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                    'code' => 'STR',
                                    'display' => 'Surat Tanda Registrasi'
                                ]
                            ]
                        ]
                    ]
                ],
                'active' => true,
                'name' => [
                    [
                        'use' => 'official',
                        'text' => $request->nama
                    ]
                ],
                'telecom' => [
                    [
                        'system' => 'phone',
                        'value' => $request->no_hp,
                        'use' => 'mobile'
                    ],
                    [
                        'system' => 'email',
                        'value' => $request->email
                    ]
                ],
                'address' => [
                    [
                        'type' => 'physical',
                        'line' => [$request->alamat],
                        'extension' => [
                            [
                                'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeGender',
                                'extension' => [
                                    [
                                        'url' => 'province',
                                        'valueCodeableConcept' => [
                                            'coding' => [
                                                [
                                                    'system' => 'http://sys-ids.kemkes.go.id/wilayah',
                                                    'code' => $request->kode_provinsi
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'url' => 'city',
                                        'valueCodeableConcept' => [
                                            'coding' => [
                                                [
                                                    'system' => 'http://sys-ids.kemkes.go.id/wilayah',
                                                    'code' => $request->kode_kabupaten
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'url' => 'district',
                                        'valueCodeableConcept' => [
                                            'coding' => [
                                                [
                                                    'system' => 'http://sys-ids.kemkes.go.id/wilayah',
                                                    'code' => $request->kode_kecamatan
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'url' => 'village',
                                        'valueCodeableConcept' => [
                                            'coding' => [
                                                [
                                                    'system' => 'http://sys-ids.kemkes.go.id/wilayah',
                                                    'code' => $request->kode_kelurahan
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'gender' => strtolower($request->jenis_kelamin),
                'birthDate' => $request->tanggal_lahir
            ];

            // Kirim data ke SATUSEHAT
            $response = $satuSehatService->sendPractitioner($fhirPractitioner);
            
            return response()->json([
                'status' => 'success',
                'id' => $response['id'] ?? null,
                'message' => 'Practitioner registered successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Exception in registerPractitioner', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}