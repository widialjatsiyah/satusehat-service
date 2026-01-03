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
            ])->get($satuSehatService->client->fhir_url . '/Patient', [
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