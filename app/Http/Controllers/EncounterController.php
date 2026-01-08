<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\SatusehatLog;
use App\Jobs\SendEncounterToSatusehat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SatusehatResourceController extends Controller
{
    /**
     * Queue patient data for SATUSEHAT registration
     */
    public function sendPatientAsync(Request $request)
    {
        $clinic = $request->attributes->get('clinic');

        $request->validate([
            'nik' => 'required|string|size:16',
            'name' => 'required|string',
            'birth_date' => 'required|date',
            'gender' => 'required|in:L,P',
            'address' => 'required|string',
            'province_code' => 'required|string',
            'city_code' => 'required|string',
            'district_code' => 'required|string',
            'village_code' => 'required|string',
            'phone' => 'required|string',
            'nationality' => 'required|string',
        ]);

        try {
            // Create a log entry to track this request
            $log = SatusehatLog::create([
                'clinic_id' => $clinic->id,
                'resource_type' => 'Patient',
                'request_payload' => json_encode([
                    'nik' => $request->nik,
                    'name' => $request->name,
                    'gender' => $request->gender,
                    'birth_date' => $request->birth_date,
                    'address' => $request->address,
                    'province_code' => $request->province_code,
                    'city_code' => $request->city_code,
                    'district_code' => $request->district_code,
                    'village_code' => $request->village_code,
                    'phone' => $request->phone,
                    'nationality' => $request->nationality,
                ]),
                'status' => 'PENDING',
                'retry_count' => 0
            ]);

            // Dispatch the job to send the patient data
            SendEncounterToSatusehat::dispatch($log->id);

            return response()->json([
                'status' => 'success',
                'log_id' => $log->id,
                'message' => 'Patient data queued for SATUSEHAT registration'
            ]);
        } catch (\Exception $e) {
            Log::error('Exception in sendPatientAsync', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Queue practitioner data for SATUSEHAT registration
     */
    public function sendPractitionerAsync(Request $request)
    {
        $clinic = $request->attributes->get('clinic');

        $request->validate([
            'nik' => 'required|string|size:16',
            'name' => 'required|string',
            'sip' => 'required|string',
            'str' => 'required|string',
            'birth_date' => 'required|date',
            'gender' => 'required|in:L,P',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
        ]);

        try {
            // Create a log entry to track this request
            $log = SatusehatLog::create([
                'clinic_id' => $clinic->id,
                'resource_type' => 'Practitioner',
                'request_payload' => json_encode([
                    'nik' => $request->nik,
                    'name' => $request->name,
                    'sip' => $request->sip,
                    'str' => $request->str,
                    'gender' => $request->gender,
                    'birth_date' => $request->birth_date,
                    'address' => $request->address,
                    'phone' => $request->phone,
                    'email' => $request->email,
                ]),
                'status' => 'PENDING',
                'retry_count' => 0
            ]);

            // Dispatch the job to send the practitioner data
            SendEncounterToSatusehat::dispatch($log->id);

            return response()->json([
                'status' => 'success',
                'log_id' => $log->id,
                'message' => 'Practitioner data queued for SATUSEHAT registration'
            ]);
        } catch (\Exception $e) {
            Log::error('Exception in sendPractitionerAsync', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Queue encounter data for SATUSEHAT submission
     */
    public function sendEncounterAsync(Request $request)
    {
        $clinic = $request->attributes->get('clinic');

        $request->validate([
            'patient_id' => 'required|string',
            'practitioner_id' => 'required|string',
            'status' => 'required|string',
            'class_code' => 'required|string',
            'class_display' => 'required|string',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
        ]);

        try {
            // Create a log entry to track this request
            $log = SatusehatLog::create([
                'clinic_id' => $clinic->id,
                'resource_type' => 'Encounter',
                'request_payload' => json_encode([
                    'patient_id' => $request->patient_id,
                    'practitioner_id' => $request->practitioner_id,
                    'status' => $request->status,
                    'class_code' => $request->class_code,
                    'class_display' => $request->class_display,
                    'period_start' => $request->period_start,
                    'period_end' => $request->period_end,
                    'type' => [
                        'code' => $request->type_code ?? 'RAN',
                        'display' => $request->type_display ?? 'Ranap',
                        'system' => $request->type_system ?? 'http://snomed.info/sct'
                    ],
                    'reason' => [
                        'code' => $request->reason_code ?? 'check-up',
                        'display' => $request->reason_display ?? 'Check-up',
                        'system' => $request->reason_system ?? 'http://snomed.info/sct'
                    ],
                ]),
                'status' => 'PENDING',
                'retry_count' => 0
            ]);

            // Dispatch the job to send the encounter data
            SendEncounterToSatusehat::dispatch($log->id);

            return response()->json([
                'status' => 'success',
                'log_id' => $log->id,
                'message' => 'Encounter data queued for SATUSEHAT submission'
            ]);
        } catch (\Exception $e) {
            Log::error('Exception in sendEncounterAsync', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Queue observation data for SATUSEHAT submission
     */
    public function sendObservationAsync(Request $request)
    {
        $clinic = $request->attributes->get('clinic');

        $request->validate([
            'patient_id' => 'required|string',
            'category_code' => 'required|string',
            'category_display' => 'required|string',
            'code' => 'required|array',
            'code.code' => 'required|string',
            'code.display' => 'required|string',
            'value' => 'required',
            'value_type' => 'required|in:string,quantity,boolean,integer,range,ratio,sampled-data,time,datetime,period'
        ]);

        try {
            // Create a log entry to track this request
            $log = SatusehatLog::create([
                'clinic_id' => $clinic->id,
                'resource_type' => 'Observation',
                'request_payload' => json_encode([
                    'patient_id' => $request->patient_id,
                    'category' => [
                        'code' => $request->category_code,
                        'display' => $request->category_display,
                        'system' => $request->category_system ?? 'http://terminology.hl7.org/CodeSystem/observation-category'
                    ],
                    'code' => [
                        'code' => $request->code['code'],
                        'display' => $request->code['display'],
                        'system' => $request->code['system'] ?? 'http://loinc.org'
                    ],
                    'value' => $request->value,
                    'value_type' => $request->value_type,
                    'effective_date' => $request->effective_date ?? now()->format('Y-m-d\TH:i:sP'),
                    'status' => $request->status ?? 'final',
                    'practitioner_id' => $request->practitioner_id ?? null,
                    'encounter_id' => $request->encounter_id ?? null,
                ]),
                'status' => 'PENDING',
                'retry_count' => 0
            ]);

            // Dispatch the job to send the observation data
            SendEncounterToSatusehat::dispatch($log->id);

            return response()->json([
                'status' => 'success',
                'log_id' => $log->id,
                'message' => 'Observation data queued for SATUSEHAT submission'
            ]);
        } catch (\Exception $e) {
            Log::error('Exception in sendObservationAsync', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Queue procedure data for SATUSEHAT submission
     */
    public function sendProcedureAsync(Request $request)
    {
        $clinic = $request->attributes->get('clinic');

        $request->validate([
            'patient_id' => 'required|string',
            'practitioner_id' => 'required|string',
            'code' => 'required|array',
            'code.code' => 'required|string',
            'code.display' => 'required|string',
        ]);

        try {
            // Create a log entry to track this request
            $log = SatusehatLog::create([
                'clinic_id' => $clinic->id,
                'resource_type' => 'Procedure',
                'request_payload' => json_encode([
                    'patient_id' => $request->patient_id,
                    'practitioner_id' => $request->practitioner_id,
                    'category' => [
                        'code' => $request->category_code ?? 'procedure',
                        'display' => $request->category_display ?? 'Procedure',
                        'system' => $request->category_system ?? 'http://snomed.info/sct'
                    ],
                    'code' => [
                        'code' => $request->code['code'],
                        'display' => $request->code['display'],
                        'system' => $request->code['system'] ?? 'http://snomed.info/sct'
                    ],
                    'performed_date' => $request->performed_date ?? now()->format('Y-m-d\TH:i:sP'),
                    'status' => $request->status ?? 'completed',
                    'encounter_id' => $request->encounter_id ?? null,
                ]),
                'status' => 'PENDING',
                'retry_count' => 0
            ]);

            // Dispatch the job to send the procedure data
            SendEncounterToSatusehat::dispatch($log->id);

            return response()->json([
                'status' => 'success',
                'log_id' => $log->id,
                'message' => 'Procedure data queued for SATUSEHAT submission'
            ]);
        } catch (\Exception $e) {
            Log::error('Exception in sendProcedureAsync', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Queue condition data for SATUSEHAT submission
     */
    public function sendConditionAsync(Request $request)
    {
        $clinic = $request->attributes->get('clinic');

        $request->validate([
            'patient_id' => 'required|string',
            'code' => 'required|array',
            'code.code' => 'required|string',
            'code.display' => 'required|string',
        ]);

        try {
            // Create a log entry to track this request
            $log = SatusehatLog::create([
                'clinic_id' => $clinic->id,
                'resource_type' => 'Condition',
                'request_payload' => json_encode([
                    'patient_id' => $request->patient_id,
                    'code' => [
                        'code' => $request->code['code'],
                        'display' => $request->code['display'],
                        'system' => $request->code['system'] ?? 'http://snomed.info/sct'
                    ],
                    'clinical_status' => $request->clinical_status ?? 'active',
                    'verification_status' => $request->verification_status ?? 'confirmed',
                    'onset_date' => $request->onset_date ?? now()->format('Y-m-d\TH:i:sP'),
                    'recorded_date' => $request->recorded_date ?? now()->format('Y-m-d\TH:i:sP'),
                    'practitioner_id' => $request->practitioner_id ?? null,
                    'encounter_id' => $request->encounter_id ?? null,
                ]),
                'status' => 'PENDING',
                'retry_count' => 0
            ]);

            // Dispatch the job to send the condition data
            SendEncounterToSatusehat::dispatch($log->id);

            return response()->json([
                'status' => 'success',
                'log_id' => $log->id,
                'message' => 'Condition (diagnosis) data queued for SATUSEHAT submission'
            ]);
        } catch (\Exception $e) {
            Log::error('Exception in sendConditionAsync', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Jobs\SendEncounterToSatusehat;
use App\Models\Clinic;
use App\Models\SatusehatLog;
use App\Services\SatuSehatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EncounterController extends Controller
{
    public function store(Request $request)
    {
        $clinic = $request->attributes->get('clinic');

        // Basic validation
        $data = $request->validate([
            'nik_pasien' => 'required|string|size:16',  // Changed from pasien_id to nik_pasien
            'tanggal_kunjungan' => 'required|date',
            'tanggal_selesai' => 'required|date',
            'jenis_layanan' => 'required|string',
            'jenis_kunjungan' => 'required|string',
            'poli' => 'required|string',
            'dokter' => 'required|string',
            'penjamin' => 'required|string',
            'keluhan_utama' => 'required|string',
            'anamnesa' => 'required|string',
            'pemeriksaan_fisik' => 'required|array',
        ]);

        // Get the SATUSEHAT patient ID from NIK
        $satuSehatService = new SatuSehatService($clinic);

        try {
            // Try to get patient ID from SATUSEHAT
            $accessToken = $satuSehatService->getAccessToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/fhir+json',
            ])->get($satuSehatService->getFhirUrl() . '/Patient', [
                'identifier' => 'http://sys-ids.kemkes.go.id/patient/' . $clinic->organization_id . '|' . $data['nik_pasien']
            ]);

            if ($response->successful()) {
                $fhirData = $response->json();

                if (isset($fhirData['entry']) && count($fhirData['entry']) > 0) {
                    $pasienId = $fhirData['entry'][0]['resource']['id'];
                } else {
                    // Patient not found, try to register the patient
                    Log::info("Patient with NIK {$data['nik_pasien']} not found in SATUSEHAT, attempting registration");

                    // Create patient resource
                    $patientResource = $this->createPatientResource($clinic, $data['nik_pasien']);
                    $patientResponse = $satuSehatService->sendPatient($patientResource);
                    $pasienId = $patientResponse['id'] ?? null;

                    if (!$pasienId) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Failed to register patient in SATUSEHAT'
                        ], 422);
                    }
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to query SATUSEHAT for patient'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error getting patient ID from SATUSEHAT', [
                'clinic_id' => $clinic->id,
                'nik' => $data['nik_pasien'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error processing patient data: ' . $e->getMessage()
            ], 500);
        }

        // Replace nik_pasien with the SATUSEHAT patient ID
        $data['pasien_id'] = $pasienId;
        unset($data['nik_pasien']); // Remove the NIK field as we now have the SATUSEHAT ID

        // Simpan ke log dan enqueue job
        $log = SatusehatLog::create([
            'clinic_id' => $clinic->id,
            'resource_type' => 'Encounter',
            'request_payload' => json_encode($data),
            'status' => 'PENDING',
        ]);

        SendEncounterToSatusehat::dispatch($log->id);

        return response()->json(['status' => 'queued', 'log_id' => $log->id], 202);
    }

    /**
     * Create a patient resource for SATUSEHAT
     */
    private function createPatientResource($clinic, $nik)
    {
        // In a real implementation, you would fetch the complete patient data from your local database
        // For now, we'll create a basic resource with just the NIK
        return [
            'resourceType' => 'Patient',
            'id' => 'id-' . uniqid(),
            'identifier' => [
                [
                    'system' => 'http://sys-ids.kemkes.go.id/patient/' . $clinic->organization_id,
                    'value' => $nik,
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
                    'text' => 'Pasien Baru - ' . $nik  // In real implementation, use actual name from your DB
                ]
            ],
            'gender' => 'unknown',  // In real implementation, use actual gender from your DB
            // Add other fields as needed from your local patient data
        ];
    }

    /**
     * Endpoint to test sending patient data to SATUSEHAT for a specific clinic
     */
    public function testPatient(Request $request, $clinicCode)
    {
        $clinic = Clinic::where('code', $clinicCode)->firstOrFail();

        // Validasi input
        $data = $request->validate([
            'name' => 'required|array',
            'name.0.text' => 'required',
            'telecom' => 'sometimes|array',
            'gender' => 'required|in:male,female,other',
            'birthDate' => 'required|date',
        ]);

        $satuSehatService = new SatuSehatService();
        $satuSehatService->setClinic($clinic);

        try {
            $response = $satuSehatService->sendPatient($data);
            return response()->json(['status' => 'success', 'data' => $response]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint to get access token for a specific clinic
     */
    public function getToken(Request $request, $clinicCode)
    {
        $clinic = Clinic::where('code', $clinicCode)->firstOrFail();

        $satuSehatService = new SatuSehatService();
        $satuSehatService->setClinic($clinic);

        try {
            $token = $satuSehatService->getAccessToken();
            return response()->json(['access_token' => $token]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
