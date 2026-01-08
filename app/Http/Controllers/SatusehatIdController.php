<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Services\SatuSehatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Carbon\Carbon;

class SatusehatIdController extends Controller
{
    public function getPatientByNIK(Request $request)
    {
        $clinic = $request->attributes->get('clinic');
        $nik = $request->nik;

        try {
            $satuSehatService = new SatuSehatService($clinic);

            // Using the library's built-in method to search for patient
            $response = $satuSehatService->get_by_nik('Patient', $nik);

            // dd($response);
            // Check if response is an array with two elements (header, body) or just the body
            if (is_array($response) && isset($response['response'])) {
                $data = $response['response'];
            } else {
                $data = $response;
            }
            // dd($data->entry[0]->resource->id);
            if (isset($data->entry[0]) && count($data->entry) > 0) {
                $patientId = $data->entry[0]->resource->id;
                return response()->json([
                    'meta' => [
                        'code' => 200,
                        'message' => 'success',
                        'clinic_id' => $clinic->code
                    ],
                    'data' => [
                        'satusehat_id' => $patientId
                    ]
                ], 200);
            } else {
                return response()->json([
                    'meta' => [
                        'code' => 404,
                        'message' => 'Patient not found in SATUSEHAT',
                        'clinic_id' => $clinic->code
                    ],
                    'data' => null
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Exception in getPatientByNIK', [
                'clinic_id' => $clinic->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'meta' => [
                    'code' => 500,
                    'message' => 'An error occurred while processing your request',
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'error_detail' => $e->getMessage()
                ]
            ], 500);
        }

        return response()->json([
            'meta' => [
                'code' => 500,
                'message' => 'An error occurred while processing your request',
                'clinic_id' => $clinic->code
            ],
            'data' => [
                'error_detail' => $e->getMessage()
            ]
        ], 500);
    }

    public function getPatientByID(Request $request)
    {
        $clinic = $request->attributes->get('clinic');
        $id = $request->id;
        // dd($id);
        try {
            $satuSehatService = new SatuSehatService($clinic);
            // dd($satuSehatService);
            // Using the library's built-in method to search for patient
            $response = $satuSehatService->get_by_id('Patient', $id);

            // dd($response);
            // Check if response is an array with two elements (header, body) or just the body
            if (is_array($response) && isset($response['response'])) {
                $data = $response['response'];
            } else {
                $data = $response;
            }
            // dd($data->id);
            // dd($data->entry[0]->resource->id);
            if ($data->id) {
                $patientId = $data->id;
                return response()->json([
                    'meta' => [
                        'code' => 200,
                        'message' => 'success',
                        'clinic_id' => $clinic->code
                    ],
                    'data' => [
                        'satusehat_id' => $patientId
                    ]
                ], 200);
            } else {
                return response()->json([
                    'meta' => [
                        'code' => 404,
                        'message' => 'Patient not found in SATUSEHAT',
                        'clinic_id' => $clinic->code
                    ],
                    'data' => null
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Exception in getPatientByID', [
                'clinic_id' => $clinic->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing your request',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getPractitionerId(Request $request)
    {
        $clinic = $request->attributes->get('clinic');

        $request->validate([
            'nik' => 'nullable|string|size:16',
            'id'  => 'nullable|string',
        ]);

        try {
            $satuSehatService = new SatuSehatService($clinic);

            // === Ambil response dari SATUSEHAT ===
            if ($request->filled('nik')) {
                $response = $satuSehatService->get_by_nik('Practitioner', $request->nik);
            } elseif ($request->filled('id')) {
                $response = $satuSehatService->get_by_id('Practitioner', $request->id);
            } else {
                return response()->json([
                    'meta' => [
                        'code' => 422,
                        'message' => 'nik atau id wajib diisi',
                        'clinic_id' => $clinic->code
                    ],
                    'data' => null
                ], 422);
            }

            // === Normalisasi response ===
            $data = $this->normalizeSatuSehatResponse($response);

            // === Ambil Practitioner ID ===
            $practitionerId = null;

            // Case: Bundle search
            if (isset($data->entry) && is_array($data->entry) && count($data->entry) > 0) {
                $practitionerId = $data->entry[0]->resource->id ?? null;
            }
            // Case: direct resource
            elseif (isset($data->id)) {
                $practitionerId = $data->id;
            }

            if (!$practitionerId) {
                return response()->json([
                    'meta' => [
                        'code' => 404,
                        'message' => 'Practitioner tidak ditemukan di SATUSEHAT',
                        'clinic_id' => $clinic->code
                    ],
                    'data' => null
                ], 404);
            }

            return response()->json([
                'meta' => [
                    'code' => 200,
                    'message' => 'success',
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'satusehat_id' => $practitionerId
                ]
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Exception in getPractitionerId', [
                'clinic_id' => $clinic->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'meta' => [
                    'code' => 500,
                    'message' => 'Terjadi kesalahan saat memproses permintaan',
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'error_detail' => $e->getMessage()
                ]
            ], 500);
        }
    }


    public function getHealthcareServiceId(Request $request)
    {
        $clinic = $request->attributes->get('clinic');

        $request->validate([
            'kode_poli' => 'required|string'
        ]);

        try {
            $satuSehatService = new SatuSehatService($clinic);

            $response = $satuSehatService->getClient()->get_by_id(
                'HealthcareService',
                'identifier',
                'http://sys-ids.kemkes.go.id/healthcare-service/' . $clinic->organization_id . '|' . $request->kode_poli
            );

            // Check if response is an array with two elements (header, body) or just the body
            if (is_array($response) && isset($response[1])) {
                $data = $response[1];
            } else {
                $data = $response;
            }

            if (isset($data['entry']) && count($data['entry']) > 0) {
                $serviceId = $data['entry'][0]['resource']['id'];
                return response()->json([
                    'meta' => [
                        'code' => 200,
                        'message' => 'success',
                        'clinic_id' => $clinic->code
                    ],
                    'data' => [
                        'satusehat_id' => $serviceId
                    ]
                ], 200);
            } else {
                return response()->json([
                    'meta' => [
                        'code' => 404,
                        'message' => 'Healthcare service not found in SATUSEHAT',
                        'clinic_id' => $clinic->code
                    ],
                    'data' => null
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Exception in getHealthcareServiceId', [
                'clinic_id' => $clinic->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'meta' => [
                    'code' => 500,
                    'message' => 'An error occurred while processing your request',
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'error_detail' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function registerPatient(Request $request)
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
            $satuSehatService = new SatuSehatService($clinic);

            // Prepare patient data using the package's PatientBuilder
            $patientBuilder = new PatientBuilder();
            $patientBuilder->setNIK($request->nik)
                ->setName($request->name)
                ->setGender($request->gender === 'L' ? 'male' : 'female')
                ->setBirthDate($request->birth_date)
                ->setTelecom('HP', $request->phone)
                ->setAddress($request->address, $request->village_code, $request->district_code, $request->city_code, $request->province_code);

            // Register patient to SATUSEHAT using the service
            $response = $satuSehatService->registerPatient($patientBuilder->build());

            return response()->json([
                'meta' => [
                    'code' => 200,
                    'message' => 'Patient registered successfully',
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'id' => $response['id'] ?? null
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Exception in registerPatient', [
                'clinic_id' => $clinic->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'meta' => [
                    'code' => 500,
                    'message' => $e->getMessage(),
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'error_detail' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function registerPractitioner(Request $request)
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
            $satuSehatService = new SatuSehatService($clinic);

            // Prepare practitioner data using the package's PractitionerBuilder
            $practitionerBuilder = new PractitionerBuilder();
            $practitionerBuilder->setNIK($request->nik)
                ->setName($request->name)
                ->setGender($request->gender === 'L' ? 'male' : 'female')
                ->setBirthDate($request->birth_date)
                ->setSIP($request->sip)
                ->setSTR($request->str)
                ->setAddress($request->address)
                ->setTelecom('HP', $request->phone)
                ->setTelecom('Email', $request->email);

            // Register practitioner to SATUSEHAT using the service
            $response = $satuSehatService->registerPractitioner($practitionerBuilder->build());

            return response()->json([
                'meta' => [
                    'code' => 200,
                    'message' => 'Practitioner registered successfully',
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'id' => $response['id'] ?? null
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Exception in registerPractitioner', [
                'clinic_id' => $clinic->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'meta' => [
                    'code' => 500,
                    'message' => $e->getMessage(),
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'error_detail' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function sendEncounter(Request $request)
    {
        $clinic = $request->attributes->get('clinic');

        // Check if the data is in the nested format (encounter object inside the request)
        $encounterData = $request->has('encounter') ? $request->encounter : $request->all();

        // $request->validate([
        //     'encounter.registration_number' => 'required_without:registration_number|string',
        //     'encounter.arrived' => 'required_without:arrived|string',
        //     'encounter.in_progress_start' => 'required_without:in_progress_start|string',
        //     'encounter.in_progress_end' => 'required_without:in_progress_end|string',
        //     'encounter.finished' => 'required_without:finished|string',
        //     'encounter.consultation_method' => 'required_without:consultation_method|string|in:RAJAL,IGD,RANAP,HOMECARE,TELEKONSULTASI',
        //     'encounter.patient_id' => 'required_without:patient_id|string',
        //     'encounter.patient_name' => 'required_without:patient_name|string',
        //     'encounter.practitioner_id' => 'required_without:practitioner_id|string',
        //     'encounter.practitioner_name' => 'required_without:practitioner_name|string',
        //     'encounter.location_id' => 'required_without:location_id|string',
        //     'encounter.location_name' => 'required_without:location_name|string',
        //     // For flat format (backward compatibility)
        //     'registration_number' => 'required_without:encounter|string',
        //     'arrived' => 'required_without:encounter|string',
        //     'in_progress_start' => 'required_without:encounter|string',
        //     'in_progress_end' => 'required_without:encounter|string',
        //     'finished' => 'required_without:encounter|string',
        //     'consultation_method' => 'required_without:encounter|string|in:RAJAL,IGD,RANAP,HOMECARE,TELEKONSULTASI',
        //     'patient_id' => 'required_without:encounter|string',
        //     'patient_name' => 'required_without:encounter|string',
        //     'practitioner_id' => 'required_without:encounter|string',
        //     'practitioner_name' => 'required_without:encounter|string',
        //     'location_id' => 'required_without:encounter|string',
        //     'location_name' => 'required_without:encounter|string',
        // ]);

        try {
            $satuSehatService = new SatuSehatService($clinic);

            // If the request contains nested encounter data, extract it
            // if ($request->has('encounter')) {
            //     $encounterData = $request->encounter;
            // } else {
            $encounterData = $request->all();
            // }
            // dd($encounterData);
            // Send encounter to SATUSEHAT using the service
            $response = $satuSehatService->sendEncounter($encounterData);
            dd($response);
            return response()->json([
                'meta' => [
                    'code' => 200,
                    'message' => 'Encounter sent successfully',
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'id' => $response['id'] ?? null
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Exception in sendEncounter', [
                'clinic_id' => $clinic->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'meta' => [
                    'code' => 500,
                    'message' => $e->getMessage(),
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'error_detail' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function sendObservation(Request $request)
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
            $satuSehatService = new SatuSehatService($clinic);

            // Prepare observation data using the package's ObservationBuilder
            $observationBuilder = new ObservationBuilder();
            $observationBuilder->setPatient($request->patient_id)
                ->setCategory($request->category_code, $request->category_display, $request->category_system)
                ->setCode($request->code['code'], $request->code['display'], $request->code['system'])
                ->setEffectiveDateTime($request->effective_date ?? now()->format('Y-m-d\TH:i:sP'))
                ->setStatus($request->status ?? 'final');

            if ($request->has('practitioner_id')) {
                $observationBuilder->setPerformer($request->practitioner_id);
            }

            if ($request->has('encounter_id')) {
                $observationBuilder->setEncounter($request->encounter_id);
            }

            // Set value based on value_type
            switch ($request->value_type) {
                case 'quantity':
                    $observationBuilder->setValueQuantity($request->value, $request->unit ?? null, $request->system ?? null, $request->code_value ?? null);
                    break;
                case 'string':
                    $observationBuilder->setValueString($request->value);
                    break;
                case 'boolean':
                    $observationBuilder->setValueBoolean($request->value);
                    break;
                case 'integer':
                    $observationBuilder->setValueInteger($request->value);
                    break;
                default:
                    $observationBuilder->setValueString($request->value);
                    break;
            }

            // Send observation to SATUSEHAT using the service
            $response = $satuSehatService->sendObservation($observationBuilder->build());

            return response()->json([
                'meta' => [
                    'code' => 200,
                    'message' => 'Observation sent successfully',
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'id' => $response['id'] ?? null
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Exception in sendObservation', [
                'clinic_id' => $clinic->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'meta' => [
                    'code' => 500,
                    'message' => $e->getMessage(),
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'error_detail' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function sendProcedure(Request $request)
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
            $satuSehatService = new SatuSehatService($clinic);

            // Prepare procedure data using the package's ProcedureBuilder
            $procedureBuilder = new ProcedureBuilder();
            $procedureBuilder->setPatient($request->patient_id)
                ->setPractitioner($request->practitioner_id)
                ->setCategory($request->category_code ?? 'procedure', $request->category_display ?? 'Procedure', $request->category_system ?? 'http://snomed.info/sct')
                ->setCode($request->code['code'], $request->code['display'], $request->code['system'])
                ->setPerformedDateTime($request->performed_date ?? now()->format('Y-m-d\TH:i:sP'))
                ->setStatus($request->status ?? 'completed');

            if ($request->has('encounter_id')) {
                $procedureBuilder->setEncounter($request->encounter_id);
            }

            // Send procedure to SATUSEHAT using the service
            $response = $satuSehatService->sendProcedure($procedureBuilder->build());

            return response()->json([
                'meta' => [
                    'code' => 200,
                    'message' => 'Procedure sent successfully',
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'id' => $response['id'] ?? null
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Exception in sendProcedure', [
                'clinic_id' => $clinic->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'meta' => [
                    'code' => 500,
                    'message' => $e->getMessage(),
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'error_detail' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function sendCondition(Request $request)
    {
        $clinic = $request->attributes->get('clinic');

        $request->validate([
            'patient_id' => 'required|string',
            'code' => 'required|array',
            'code.code' => 'required|string',
            'code.display' => 'required|string',
        ]);

        try {
            $satuSehatService = new SatuSehatService($clinic);

            // Prepare condition data using the package's ConditionBuilder
            $conditionBuilder = new ConditionBuilder();
            $conditionBuilder->setPatient($request->patient_id)
                ->setCode($request->code['code'], $request->code['display'], $request->code['system'])
                ->setClinicalStatus($request->clinical_status ?? 'active')
                ->setVerificationStatus($request->verification_status ?? 'confirmed')
                ->setOnsetDateTime($request->onset_date ?? now()->format('Y-m-d\TH:i:sP'))
                ->setRecordedDate($request->recorded_date ?? now()->format('c'));

            if ($request->has('practitioner_id')) {
                $conditionBuilder->setAsserter($request->practitioner_id);
            }

            if ($request->has('encounter_id')) {
                $conditionBuilder->setEncounter($request->encounter_id);
            }

            // Send condition (diagnosis) to SATUSEHAT using the service
            $response = $satuSehatService->sendCondition($conditionBuilder->build());

            return response()->json([
                'meta' => [
                    'code' => 200,
                    'message' => 'Condition (diagnosis) sent successfully',
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'id' => $response['id'] ?? null
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Exception in sendCondition', [
                'clinic_id' => $clinic->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'meta' => [
                    'code' => 500,
                    'message' => $e->getMessage(),
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'error_detail' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function sendBundleSatusehat(Request $request)
    {
        $clinic = $request->attributes->get('clinic');

        $request->validate([
            'encounter' => 'required|array',
            'encounter.registration_number' => 'required|string',
            'encounter.consultation_method' => 'required|string',
            'encounter.patient_id' => 'required|string',
            'encounter.patient_name' => 'required|string',
            'encounter.practitioner_id' => 'required|string',
            'encounter.practitioner_name' => 'required|string',
            'encounter.location_id' => 'required|string',
            'encounter.location_name' => 'required|string',
            'conditions' => 'required|array',
            'conditions.*.icd10_code' => 'required|string',
            'conditions.*.patient_id' => 'required|string',
            'conditions.*.patient_name' => 'required|string',
        ]);

        try {
            $satuSehatService = new SatuSehatService($clinic);
            $arrived = Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $inprogress_start = Carbon::now('Asia/Jakarta')
                ->addMinutes(10)
                ->format('Y-m-d\TH:i:sP');
            $inprogress_end = Carbon::now('Asia/Jakarta')
                ->addMinutes(20)
                ->format('Y-m-d\TH:i:sP');
            $timeonset = Carbon::now('Asia/Jakarta')
                ->addMinute(25)
                ->format('Y-m-d\TH:i:sP');
            $finished = Carbon::now('Asia/Jakarta')
                ->addMinutes(62)
                ->format('Y-m-d\TH:i:sP');

            // Prepare encounter data
            $encounterData = [
                'registration_number' => $request->encounter['registration_number'],
                'arrived' => $request->encounter['arrived'] ?? $arrived,
                'in_progress_start' => $request->encounter['in_progress_start'] ?? $inprogress_start,
                'in_progress_end' => $request->encounter['in_progress_end'] ?? $inprogress_end,
                'finished' => $request->encounter['finished'] ?? $finished,
                'consultation_method' => $request->encounter['consultation_method'],
                'patient_id' => $request->encounter['patient_id'],
                'patient_name' => $request->encounter['patient_name'],
                'practitioner_id' => $request->encounter['practitioner_id'],
                'practitioner_name' => $request->encounter['practitioner_name'],
                'location_id' => $request->encounter['location_id'],
                'location_name' => $request->encounter['location_name'],
            ];

            // Prepare conditions data
            $conditionsData = [];
            foreach ($request->conditions as $condition) {
                $conditionData = [
                    'clinical_status' => $condition['clinical_status'] ?? 'active',
                    'category' => $condition['category'] ?? 'Diagnosis',
                    'icd10_code' => $condition['icd10_code'],
                    'patient_id' => $condition['patient_id'],
                    'patient_name' => $condition['patient_name'],
                    'onset_date_time' => $condition['onset_date_time'] ?? $timeonset,
                    'recorded_date' => $condition['recorded_date'] ?? now()->format('c'),
                ];

                if (isset($condition['verification_status'])) {
                    $conditionData['verification_status'] = $condition['verification_status'];
                }

                $conditionsData[] = $conditionData;
            }

            // Send bundle to SATUSEHAT using the service
            $response = $satuSehatService->bundleSatusehat($encounterData, $conditionsData);
            // dd($response);
            if (is_array($response) && isset($response['response'])) {
                $data = $response['response'];
            } else {
                $data = $response;
            }
            // dd($data->entry[0]->resource->id);
            if (isset($data->entry[0]) && count($data->entry) > 0) {
                $encounterData = $data->entry[0]->response->resourceID;
                $conditionData = $data->entry[1]->response->resourceID;
                return response()->json([
                    'meta' => [
                        'code' => 200,
                        'message' => 'success',
                        'clinic_id' => $clinic->code
                    ],
                    'data' => [
                        'encounter' => $encounterData,
                        'conditions' => $conditionData,
                    ]
                ], 200);
            } else {
                return response()->json([
                    'meta' => [
                        'code' => 201,
                        'message' => 'Duplicate in SATUSEHAT',
                        'clinic_id' => $clinic->code
                    ],
                    'data' => null
                ], 201);
            }
        } catch (\Exception $e) {
            Log::error('Exception in sendBundleSatusehat', [
                'clinic_id' => $clinic->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'meta' => [
                    'code' => 500,
                    'message' => $e->getMessage(),
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'error_detail' => $e->getMessage()
                ]
            ], 500);
        }
    }
    function getResourceIdFromBundle($bundle, $type)
    {
        // dd($bundle['response']['entry'][0]);
        // $bundle = $bundle['response']->entry[0];
        // dd($bundle);
        foreach ($bundle as $entry) {
            if ($entry->response->resourceType === $type) {
                return $entry->response->resourceID;
            }
        }
        return null;
    }

    public function sendLocation(Request $request)
    {
        $clinic = $request->attributes->get('clinic');

        try {
            // dd($request->all());
            $satuSehatService = new SatuSehatService($clinic);


            // Send location to SATUSEHAT using the service
            $response = $satuSehatService->sendLocation($request->all());

            return response()->json([
                'meta' => [
                    'code' => 200,
                    'message' => 'Location sent successfully',
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'id' => $response['id'] ?? null
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Exception in sendLocation', [
                'clinic_id' => $clinic->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'meta' => [
                    'code' => 500,
                    'message' => $e->getMessage(),
                    'clinic_id' => $clinic->code
                ],
                'data' => [
                    'error_detail' => $e->getMessage()
                ]
            ], 500);
        }
    }

    private function normalizeSatuSehatResponse($response)
    {
        // Case: [header, body]
        if (is_array($response) && isset($response[1])) {
            return $response[1];
        }

        // Case: ['response' => body]
        if (is_array($response) && isset($response['response'])) {
            return $response['response'];
        }

        return $response;
    }
}
