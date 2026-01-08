<?php

namespace App\Services;

use App\Models\Clinic;
use Exception;
use Illuminate\Support\Arr;
use Satusehat\Integration\FHIR\Encounter;
use Satusehat\Integration\OAuth2Client;
use Satusehat\Integration\Models\SatusehatLog;
use Illuminate\Support\Facades\Log;
use Satusehat\Integration\FHIR\Condition;
use Satusehat\Integration\FHIR\Bundle;
use Satusehat\Integration\FHIR\Procedure;
use Satusehat\Integration\FHIR\Location;

class SatuSehatService
{
    protected $client;
    protected $clinic;

    public function __construct(?Clinic $clinic = null)
    {
        try {
            if ($clinic) {
                $this->setClinic($clinic);
            }
        } catch (\Exception $e) {
            Log::error('Error in SatuSehatService constructor', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Set the clinic for the service to operate on
     */
    public function setClinic(Clinic $clinic): self
    {
        try {
            $this->clinic = $clinic;
            // dd($this->clinic);
            $this->initializeSatuSehatClient();
            return $this;
        } catch (\Exception $e) {
            Log::error('Error in setClinic', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Initialize the SATUSEHAT client with clinic-specific credentials
     */
    private function initializeSatuSehatClient(): void
    {
        try {

            // dd($this->clinic->satusehat_client_secret);
            // Check if clinic credentials are available
            if (empty($this->clinic->satusehat_client_id) || empty($this->clinic->satusehat_client_secret) || empty($this->clinic->organization_id)) {

                Log::error([
                    'message' => 'Missing required clinic credentials',
                    'clinic' => $this->clinic
                ]);
                throw new Exception('Missing required clinic credentials');
            }

            // Set environment variables secara runtime
            putenv('CLIENTID_' . $this->clinic->environment . '=' . $this->clinic->satusehat_client_id);
            putenv('CLIENTSECRET_' . $this->clinic->environment . '=' . $this->clinic->satusehat_client_secret);
            putenv('ORGID_' . $this->clinic->environment . '=' . $this->clinic->organization_id);
            putenv('SATUSEHAT_ENV=' . $this->clinic->environment);

            // Aktifkan override agar tidak membaca dari env file
            config(['satusehatintegration.ss_parameter_override' => false]);

            $this->client = new OAuth2Client();

            Log::info("SatuSehatService: Client initialization completed");
        } catch (\Exception $e) {
            Log::error('Failed to initialize SATUSEHAT client', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Get access token for the currently set clinic
     */
    public function getAccessToken(): string
    {
        try {
            Log::info("SatuSehatService: Getting access token", [
                'clinic_id' => $this->clinic ? $this->clinic->id : 'NO CLINIC SET'
            ]);

            if (!$this->clinic) {
                throw new Exception('Clinic not set. Please call setClinic() first.');
            }

            // Get token using the library's built-in function
            $token = $this->client->token();

            if (!$token || is_array($token)) {
                Log::error('Failed to obtain SATUSEHAT access token', [
                    'clinic_id' => $this->clinic->id,
                    'clinic_code' => $this->clinic->code,
                    'token' => $token
                ]);

                if (is_array($token) && count($token) >= 2) {
                    throw new Exception('Failed to obtain SATUSEHAT access token: ' . $token[1]);
                }

                throw new Exception('Failed to obtain SATUSEHAT access token');
            }

            Log::info("SatuSehatService: Successfully obtained token");

            return $token;
        } catch (\Exception $e) {
            Log::error('Error in getAccessToken', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get the FHIR base URL based on environment
     */
    public function getFhirUrl(): string
    {
        // Get the base URL based on the environment
        $environment = $this->clinic ? $this->clinic->environment : config('satusehat.env', 'DEV');

        switch (strtoupper($environment)) {
            case 'PROD':
                return config('satusehat.base_url_prod', 'https://api-satusehat.kemkes.go.id');
            case 'STG':
                return config('satusehat.base_url_stg', 'https://api-satusehat-stg.dto.kemkes.go.id');
            case 'DEV':
            default:
                return config('satusehat.base_url_dev', 'https://api-satusehat-dev.dto.kemkes.go.id');
        }
    }

    public function get_by_nik($resourceType, $nik)
    {
        if (!$this->clinic) {
            throw new Exception('Clinic not set. Please call setClinic() first.');
        }

        try {
            // Using the library's built-in function
            [$statusCode, $response] = $this->client->get_by_nik($resourceType, $nik);
            $res = ['status_code' => $statusCode, 'response' => $response];
            return $res;
        } catch (Exception $e) {
            Log::error('Failed to get ' . $resourceType . ' by NIK from SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'nik' => $nik
            ]);

            throw $e;
        }
    }


    public function get_by_id($resourceType, $id)
    {
        if (!$this->clinic) {
            throw new Exception('Clinic not set. Please call setClinic() first.');
        }

        try {
            // Using the library's built-in function
            [$statusCode, $response] = $this->client->get_by_id($resourceType, $id);
            $res = ['status_code' => $statusCode, 'response' => $response];
            return $res;
        } catch (Exception $e) {
            Log::error('Failed to get ' . $resourceType . ' by ID from SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            throw $e;
        }
    }

    /**
     * Register a Patient resource to SATUSEHAT
     */
    public function registerPatient($patientData): array
    {
        if (!$this->clinic) {
            throw new Exception('Clinic not set. Please call setClinic() first.');
        }

        try {
            // Using the library's built-in function
            [$statusCode, $response] = $this->client->ss_post('Patient', json_encode($patientData));

            if (is_array($response) && isset($response['issue'])) {
                Log::error('Failed to register patient to SATUSEHAT', [
                    'clinic_id' => $this->clinic->id,
                    'clinic_code' => $this->clinic->code,
                    'error' => $response
                ]);

                throw new Exception('Failed to register patient: ' . json_encode($response));
            }

            return $response;
        } catch (Exception $e) {
            Log::error('Failed to register patient to SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'patient_data' => $patientData
            ]);

            throw $e;
        }
    }

    /**
     * Register a Practitioner resource to SATUSEHAT
     */
    public function registerPractitioner($practitionerData): array
    {
        if (!$this->clinic) {
            throw new Exception('Clinic not set. Please call setClinic() first.');
        }

        try {
            // Using the library's built-in function
            [$statusCode, $response] = $this->client->ss_post('Practitioner', json_encode($practitionerData));

            if (is_array($response) && isset($response['issue'])) {
                Log::error('Failed to register practitioner to SATUSEHAT', [
                    'clinic_id' => $this->clinic->id,
                    'clinic_code' => $this->clinic->code,
                    'error' => $response
                ]);

                throw new Exception('Failed to register practitioner: ' . json_encode($response));
            }

            return $response;
        } catch (Exception $e) {
            Log::error('Failed to register practitioner to SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'practitioner_data' => $practitionerData
            ]);

            throw $e;
        }
    }

    /**
     * Send an Observation resource to SATUSEHAT
     */
    public function sendObservation($observationData): array
    {
        if (!$this->clinic) {
            throw new Exception('Clinic not set. Please call setClinic() first.');
        }

        try {
            // Using the library's built-in function
            [$statusCode, $response] = $this->client->ss_post('Observation', json_encode($observationData));

            if (is_array($response) && isset($response['issue'])) {
                Log::error('Failed to send observation to SATUSEHAT', [
                    'clinic_id' => $this->clinic->id,
                    'clinic_code' => $this->clinic->code,
                    'error' => $response
                ]);

                throw new Exception('Failed to send observation: ' . json_encode($response));
            }

            return $response;
        } catch (Exception $e) {
            Log::error('Failed to send observation to SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'observation_data' => $observationData
            ]);

            throw $e;
        }
    }

    /**
     * Send a Procedure resource to SATUSEHAT
     */
    public function sendProcedure($procedureData): array
    {
        if (!$this->clinic) {
            throw new Exception('Clinic not set. Please call setClinic() first.');
        }

        try {
            // Using the library's built-in function
            [$statusCode, $response] = $this->client->ss_post('Procedure', json_encode($procedureData));

            if (is_array($response) && isset($response['issue'])) {
                Log::error('Failed to send procedure to SATUSEHAT', [
                    'clinic_id' => $this->clinic->id,
                    'clinic_code' => $this->clinic->code,
                    'error' => $response
                ]);

                throw new Exception('Failed to send procedure: ' . json_encode($response));
            }

            return $response;
        } catch (Exception $e) {
            Log::error('Failed to send procedure to SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'procedure_data' => $procedureData
            ]);

            throw $e;
        }
    }

    /**
     * Send a Condition resource to SATUSEHAT
     */
    public function sendCondition($conditionData): array
    {
        if (!$this->clinic) {
            throw new Exception('Clinic not set. Please call setClinic() first.');
        }

        try {
            // Using the library's built-in function
            [$statusCode, $response] = $this->client->ss_post('Condition', json_encode($conditionData));

            if (is_array($response) && isset($response['issue'])) {
                Log::error('Failed to send condition to SATUSEHAT', [
                    'clinic_id' => $this->clinic->id,
                    'clinic_code' => $this->clinic->code,
                    'error' => $response
                ]);

                throw new Exception('Failed to send condition: ' . json_encode($response));
            }

            return $response;
        } catch (Exception $e) {
            Log::error('Failed to send condition to SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'condition_data' => $conditionData
            ]);

            throw $e;
        }
    }

    /**
     * Send an Encounter resource to SATUSEHAT
     */
    public function sendEncounter($encounterData): array
    {
        if (!$this->clinic) {
            throw new Exception('Clinic not set. Please call setClinic() first.');
        }

        try {
            $encounter = new Encounter;
            $encounter->addRegistrationId($encounterData['encounter']['registration_number']); // unique string free text (increments / UUID)

            $encounter->setArrived($encounterData['encounter']['arrived']);
            $encounter->setInProgress($encounterData['encounter']['in_progress_start'], $encounterData['encounter']['in_progress_end']);
            $encounter->setFinished($encounterData['encounter']['finished']);

            $encounter->setConsultationMethod($encounterData['encounter']['consultation_method']); // RAJAL, IGD, RANAP, HOMECARE, TELEKONSULTASI
            $encounter->setSubject($encounterData['encounter']['patient_id'], $encounterData['encounter']['patient_name']); // ID SATUSEHAT Pasien dan Nama SATUSEHAT
            $encounter->addParticipant($encounterData['encounter']['practitioner_id'], $encounterData['encounter']['practitioner_name']); // ID SATUSEHAT Dokter, Nama Dokter
            $encounter->addLocation($encounterData['encounter']['location_id'], $encounterData['encounter']['location_name']); // ID SATUSEHAT Location, Nama Poli
            // dd($encounterData);
            if (isset($encounterData['conditions'])) {
                $encounter->addDiagnosis($encounterData['conditions']['condition_id'], $encounterData['conditions']['icd10_code']); // ID SATUSEHAT Condition, Kode ICD10
            }
            $encounter->json();
            dd($encounter);
            // Using the library's built-in function
            [$statusCode, $response] = $encounter->post();

            if (is_array($response) && isset($response['issue'])) {
                Log::error('Failed to send encounter to SATUSEHAT', [
                    'clinic_id' => $this->clinic->id,
                    'clinic_code' => $this->clinic->code,
                    'error' => $response
                ]);

                throw new Exception('Failed to send encounter: ' . json_encode($response));
            }

            return $response;
        } catch (Exception $e) {
            Log::error('Failed to send encounter to SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'encounter_data' => $encounterData
            ]);

            throw $e;
        }
    }

    /**
     * Send a Patient resource to SATUSEHAT
     */
    public function sendPatient($patientData): array
    {
        if (!$this->clinic) {
            throw new Exception('Clinic not set. Please call setClinic() first.');
        }

        try {
            // Using the library's built-in function
            [$statusCode, $response] = $this->client->ss_post('Patient', json_encode($patientData));

            if (is_array($response) && isset($response['issue'])) {
                Log::error('Failed to send patient to SATUSEHAT', [
                    'clinic_id' => $this->clinic->id,
                    'clinic_code' => $this->clinic->code,
                    'error' => $response
                ]);

                throw new Exception('Failed to send patient: ' . json_encode($response));
            }

            return $response;
        } catch (Exception $e) {
            Log::error('Failed to send patient to SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'patient_data' => $patientData
            ]);

            throw $e;
        }
    }

    /**
     * Send a Practitioner resource to SATUSEHAT
     */
    public function sendPractitioner($practitionerData): array
    {
        if (!$this->clinic) {
            throw new Exception('Clinic not set. Please call setClinic() first.');
        }

        try {
            // Using the library's built-in function
            [$statusCode, $response] = $this->client->ss_post('Practitioner', json_encode($practitionerData));

            if (is_array($response) && isset($response['issue'])) {
                Log::error('Failed to send practitioner to SATUSEHAT', [
                    'clinic_id' => $this->clinic->id,
                    'clinic_code' => $this->clinic->code,
                    'error' => $response
                ]);

                throw new Exception('Failed to send practitioner: ' . json_encode($response));
            }

            return $response;
        } catch (Exception $e) {
            Log::error('Failed to send practitioner to SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'practitioner_data' => $practitionerData
            ]);

            throw $e;
        }
    }

    /**
     * Create and send a bundle containing an Encounter and one or more Conditions to SATUSEHAT
     */
    public function bundleSatusehat($encounterData, $conditionsData): array
    {
        if (!$this->clinic) {
            throw new Exception('Clinic not set. Please call setClinic() first.');
        }

        try {
            // dd($encounterData, $conditionsData);
            // Create Encounter
            $encounter = new Encounter();
            $encounter->addRegistrationId($encounterData['registration_number']);
            $encounter->setArrived($encounterData['arrived']);
            $encounter->setInProgress($encounterData['in_progress_start'], $encounterData['in_progress_end']);
            $encounter->setFinished($encounterData['finished']);
            $encounter->setConsultationMethod($encounterData['consultation_method']);
            $encounter->setSubject($encounterData['patient_id'], $encounterData['patient_name']);
            $encounter->addParticipant($encounterData['practitioner_id'], $encounterData['practitioner_name']);
            $encounter->addLocation($encounterData['location_id'], $encounterData['location_name']);

            // Create conditions array
            $conditions = [];
            foreach ($conditionsData as $conditionData) {
                $condition = new Condition();
                // Set clinical status if provided, default to 'active'
                if (isset($conditionData['clinical_status'])) {
                    $condition->addClinicalStatus($conditionData['clinical_status']);
                } else {
                    $condition->addClinicalStatus('active');
                }

                // Set category if provided, default to 'Diagnosis'
                if (isset($conditionData['category'])) {
                    $condition->addCategory($conditionData['category']);
                } else {
                    $condition->addCategory('Diagnosis');
                }

                $condition->addCode($conditionData['icd10_code']);
                $condition->setSubject($conditionData['patient_id'], $conditionData['patient_name']);

                // dd($conditionData['icd10_code']);
                // Set onset and recorded date if provided
                if (isset($conditionData['onset_date_time'])) {
                    $condition->setOnsetDateTime($conditionData['onset_date_time']);
                }

                if (isset($conditionData['recorded_date'])) {
                    $condition->setRecordedDate($conditionData['recorded_date']);
                }

                $conditions[] = $condition;
            }

            // Create bundle
            $bundle = new Bundle();
            $bundle->addEncounter($encounter);

            // Add all conditions to the bundle
            foreach ($conditions as $condition) {
                $bundle->addCondition($condition);
            }
            // Send the bundle to SATUSEHAT
            [$statusCode, $response] =  $bundle->post();
            // dd($response);
            if (is_array($response) && isset($response['issue'])) {
                Log::error('Failed to send bundle to SATUSEHAT', [
                    'clinic_id' => $this->clinic->id,
                    'clinic_code' => $this->clinic->code,
                    'error' => $response
                ]);

                throw new Exception('Failed to send bundle: ' . json_encode($response));
            }
            $res = ['status_code' => $statusCode, 'response' => $response];
            return $res;
            // return $response;
        } catch (Exception $e) {
            Log::error('Failed to send bundle to SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'encounter_data' => $encounterData,
                'conditions_data' => $conditionsData
            ]);

            throw $e;
        }
    }

    /**
     * Get the current clinic
     */
    public function getClinic(): ?Clinic
    {
        return $this->clinic;
    }

    /**
     * Get the OAuth2Client instance
     */
    public function getClient(): OAuth2Client
    {
        return $this->client;
    }

    /**
     * Send a Location resource to SATUSEHAT using the clinic's organization ID
     */
    public function sendLocation($locationData): array
    {
        // dd($locationData);
        // Location
        $location = new Location;
        $location->addIdentifier($locationData['kode_poli']); // unique string free text (increments / UUID / inisial)
        $location->setName($locationData['nama_poli']); // string free text
        $location->addPhysicalType($locationData['physical_type']); // ro = ruangan, bu = bangunan, wi = sayap gedung, ve = kendaraan, ho = rumah, ca = kabined, rd = jalan, area = area. Default bila tidak dideklarasikan = ruangan
        $location->setManagingOrganization($this->clinic->organization_id); // ID SATUSEHAT Sarana Kesehatan (Organization)
        $location->json();
        // dd($location);

        [$statusCode, $response] = $location->post();
        // dd($response);
        // return $response;
        $res = ['status_code' => $statusCode, 'response' => $response];
        return $res;
    }
    /**
     * Log error and throw exception with consistent format
     */
    private function logErrorAndThrow(string $message, $data, ?Exception $previous = null): void
    {
        $context = [
            'clinic_id' => $this->clinic->id,
            'clinic_code' => $this->clinic->code,
        ];

        if (is_array($data)) {
            $context['data'] = $data;
        } else {
            $context['error'] = $data;
        }

        if ($previous) {
            $context['exception_message'] = $previous->getMessage();
            $context['file'] = $previous->getFile();
            $context['line'] = $previous->getLine();
            $context['trace'] = $previous->getTraceAsString();
        }

        Log::error($message, $context);

        if ($previous) {
            throw new Exception($message, 0, $previous);
        } else {
            throw new Exception($message);
        }
    }
}
