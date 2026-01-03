<?php

namespace App\Jobs;

use App\Models\SatusehatLog;
use App\Services\SatuSehatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log as Logger;

class SendEncounterToSatusehat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $logId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($logId)
    {
        $this->logId = $logId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $log = SatusehatLog::find($this->logId);
        if (!$log) {
            Logger::error('Log not found for SendEncounterToSatusehat job', ['log_id' => $this->logId]);
            return;
        }
        
        $clinic = $log->clinic;
        $satuSehatService = new SatuSehatService($clinic);

        // mapping: dari request_payload -> FHIR Encounter
        $payload = $this->mapToFHIR(json_decode($log->request_payload, true));

        try {
            $response = $satuSehatService->sendEncounter($payload);
            $log->update([
                'response_payload' => json_encode($response),
                'status' => 'SUCCESS'
            ]);
        } catch (\Exception $e) {
            $log->increment('retry_count');
            $log->update(['status' => 'FAILED', 'response_payload' => $e->getMessage()]);
            
            Logger::error('Failed to send encounter to SATUSEHAT', [
                'clinic_id' => $clinic->id,
                'clinic_code' => $clinic->code,
                'log_id' => $this->logId,
                'error' => $e->getMessage()
            ]);
            
            // Retrying strategy
            if ($log->retry_count < 5) {
                // re-dispatch with delay (exponential backoff)
                self::dispatch($this->logId)->delay(now()->addSeconds(30 * $log->retry_count));
            }
        }
    }

    /**
     * Map the input data to FHIR Encounter resource
     */
    private function mapToFHIR($data)
    {
        // This is a simplified mapping - you would need to implement according to FHIR specification
        return [
            'resourceType' => 'Encounter',
            'status' => 'finished',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
                'display' => 'ambulatory'
            ],
            'type' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://snomed.info/sct',
                            'code' => $data['jenis_layanan'],
                            'display' => 'Encounter for "check-up"'
                        ]
                    ]
                ]
            ],
            'subject' => [
                'reference' => 'Patient/' . $data['pasien_id']  // Using the SATUSEHAT ID that was resolved from NIK
            ],
            'participant' => [
                [
                    'individual' => [
                        'reference' => 'Practitioner/' . $data['dokter']
                    ]
                ]
            ],
            'period' => [
                'start' => $data['tanggal_kunjungan'],
                'end' => $data['tanggal_selesai']
            ],
            'reasonCode' => [
                [
                    'text' => $data['keluhan_utama']
                ]
            ],
            'serviceProvider' => [
                'reference' => 'Organization/' . $data['poli']
            ]
        ];
    }
}