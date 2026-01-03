<?php

namespace App\Services;

use App\Models\Clinic;
use Exception;
use GuzzleHttp\Client;
use Satusehat\Integration\OAuth2Client;
use Satusehat\Integration\Models\SatusehatLog;
use Illuminate\Support\Facades\Log;

class SatuSehatService
{
    protected $client;
    protected $clinic;

    public function __construct(?Clinic $clinic = null)
    {
        if ($clinic) {
            $this->setClinic($clinic);
        }
    }

    /**
     * Set the clinic for the service to operate on
     */
    public function setClinic(Clinic $clinic): self
    {
        $this->clinic = $clinic;
        $this->initializeSatuSehatClient();
        return $this;
    }

    /**
     * Initialize the SATUSEHAT client with clinic-specific credentials
     */
    private function initializeSatuSehatClient(): void
    {
        // Set environment variables for this specific clinic
        putenv('CLIENTID_' . strtoupper($this->clinic->code) . '=' . $this->clinic->satusehat_client_id);
        putenv('CLIENTSECRET_' . strtoupper($this->clinic->code) . '=' . $this->clinic->satusehat_client_secret);
        putenv('ORGID_' . strtoupper($this->clinic->code) . '=' . $this->clinic->organization_id); // assuming you'll add this field to Clinic

        // Store the original environment values to restore later
        $originalClientId = getenv('CLIENTID_STG');
        $originalClientSecret = getenv('CLIENTSECRET_STG');
        $originalOrgId = getenv('ORGID_STG');

        // Temporarily set environment for this clinic
        putenv('CLIENTID_STG=' . $this->clinic->satusehat_client_id);
        putenv('CLIENTSECRET_STG=' . $this->clinic->satusehat_client_secret);
        putenv('ORGID_STG=' . $this->clinic->organization_id);

        $this->client = new OAuth2Client();

        // Restore original environment values
        putenv('CLIENTID_STG=' . $originalClientId);
        putenv('CLIENTSECRET_STG=' . $originalClientSecret);
        putenv('ORGID_STG=' . $originalOrgId);
    }

    /**
     * Get access token for the currently set clinic
     */
    public function getAccessToken(): string
    {
        if (!$this->clinic) {
            throw new Exception('Clinic not set. Please call setClinic() first.');
        }

        // Check if token exists in clinic model and is still valid
        if ($this->clinic->satusehat_access_token && $this->clinic->satusehat_token_expires_at && $this->clinic->satusehat_token_expires_at->isFuture()) {
            return $this->clinic->satusehat_access_token;
        }

        // Get new token using the library
        $token = $this->client->getAccessToken();
        
        if (!$token) {
            Log::error('Failed to obtain SATUSEHAT access token', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code
            ]);
            throw new Exception('Failed to obtain SATUSEHAT access token');
        }

        // Update clinic with new token
        $this->clinic->update([
            'satusehat_access_token' => $token,
            'satusehat_token_expires_at' => now()->addSeconds(3600 - 60), // assuming 1 hour expiry, refresh 1 minute early
        ]);

        return $token;
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
            $accessToken = $this->getAccessToken();
            
            // Using the library's HTTP client
            $client = new Client([
                'base_uri' => $this->client->fhir_url,
                'timeout' => config('services.satusehat.timeout', 30),
            ]);

            $response = $client->post('/Encounter', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/fhir+json',
                    'Content-Type' => 'application/fhir+json',
                ],
                'json' => $encounterData,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            // Log the successful request
            SatusehatLog::log(
                $this->clinic->id,
                'Encounter',
                'POST',
                $response->getStatusCode(),
                $result,
                $encounterData
            );

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to send encounter to SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'encounter_data' => $encounterData
            ]);
            
            // Log the failed request
            SatusehatLog::log(
                $this->clinic->id,
                'Encounter',
                'POST',
                500,
                ['error' => $e->getMessage()],
                $encounterData
            );
            
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
            $accessToken = $this->getAccessToken();
            
            $client = new Client([
                'base_uri' => $this->client->fhir_url,
                'timeout' => config('services.satusehat.timeout', 30),
            ]);

            $response = $client->post('/Patient', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/fhir+json',
                    'Content-Type' => 'application/fhir+json',
                ],
                'json' => $patientData,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            // Log the successful request
            SatusehatLog::log(
                $this->clinic->id,
                'Patient',
                'POST',
                $response->getStatusCode(),
                $result,
                $patientData
            );

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to send patient to SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'patient_data' => $patientData
            ]);
            
            // Log the failed request
            SatusehatLog::log(
                $this->clinic->id,
                'Patient',
                'POST',
                500,
                ['error' => $e->getMessage()],
                $patientData
            );
            
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
            $accessToken = $this->getAccessToken();
            
            $client = new Client([
                'base_uri' => $this->client->fhir_url,
                'timeout' => config('services.satusehat.timeout', 30),
            ]);

            $response = $client->post('/Practitioner', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/fhir+json',
                    'Content-Type' => 'application/fhir+json',
                ],
                'json' => $practitionerData,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            // Log the successful request
            SatusehatLog::log(
                $this->clinic->id,
                'Practitioner',
                'POST',
                $response->getStatusCode(),
                $result,
                $practitionerData
            );

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to send practitioner to SATUSEHAT', [
                'clinic_id' => $this->clinic->id,
                'clinic_code' => $this->clinic->code,
                'error' => $e->getMessage(),
                'practitioner_data' => $practitionerData
            ]);
            
            // Log the failed request
            SatusehatLog::log(
                $this->clinic->id,
                'Practitioner',
                'POST',
                500,
                ['error' => $e->getMessage()],
                $practitionerData
            );
            
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
}