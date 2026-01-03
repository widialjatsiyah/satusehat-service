<?php
namespace App\Services;

use App\Models\Clinic;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated This service is deprecated. Use App\Services\SatuSehatService instead.
 * This service was the original implementation but has been replaced with 
 * a more robust implementation that integrates with the ivanwilliammd/satusehat-integration library.
 */
class SatusehatClient
{
    protected Clinic $clinic;
    protected Client $http;

    public function __construct(Clinic $clinic)
    {
        $this->clinic = $clinic;
        $this->http = new Client([
            'base_uri' => config('services.satusehat.base_uri'),
            'timeout' => config('services.satusehat.timeout', 30),
        ]);
    }

    public function getAccessToken(): string
    {
        // cek cache
        if ($this->clinic->satusehat_access_token && $this->clinic->satusehat_token_expires_at && $this->clinic->satusehat_token_expires_at->isFuture()) {
            return $this->clinic->satusehat_access_token;
        }

        // request token
        $resp = $this->http->post('/oauth/token', [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clinic->satusehat_client_id,
                'client_secret' => $this->clinic->satusehat_client_secret,
            ],
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 15,
        ]);

        $data = json_decode($resp->getBody()->getContents(), true);
        $token = $data['access_token'] ?? null;
        $expiresIn = $data['expires_in'] ?? 3600;

        if (!$token) {
            Log::error('Failed obtain token', ['clinic' => $this->clinic->id, 'resp' => $data]);
            throw new \Exception('Failed to obtain access token');
        }

        $this->clinic->update([
            'satusehat_access_token' => $token,
            'satusehat_token_expires_at' => now()->addSeconds($expiresIn - 60),
        ]);

        return $token;
    }

    public function postResource(string $path, array $payload)
    {
        $token = $this->getAccessToken();
        $resp = $this->http->post($path, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/fhir+json',
                'Content-Type' => 'application/fhir+json',
            ],
            'json' => $payload,
        ]);

        return json_decode((string)$resp->getBody(), true);
    }
}