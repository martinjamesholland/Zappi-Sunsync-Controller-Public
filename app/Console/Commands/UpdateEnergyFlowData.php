<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SunSyncService;
use App\Services\MyEnergiApiService;
use App\Services\EnergyFlowService;
use Illuminate\Support\Facades\Log;

class UpdateEnergyFlowData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-energy-flow-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store energy flow data from SunSync and Zappi';

    private SunSyncService $sunSyncService;
    private MyEnergiApiService $myEnergiApiService;
    private EnergyFlowService $energyFlowService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        SunSyncService $sunSyncService,
        MyEnergiApiService $myEnergiApiService,
        EnergyFlowService $energyFlowService
    ) {
        parent::__construct();
        $this->sunSyncService = $sunSyncService;
        $this->myEnergiApiService = $myEnergiApiService;
        $this->energyFlowService = $energyFlowService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting energy flow data update...');
        
        // Get SunSync data
        $username = config('services.sunsync.username');
        $password = config('services.sunsync.password');
        $sunSyncData = [];
        $plantInfo = null;

        if (!empty($username) && !empty($password)) {
            $this->info('Authenticating with SunSync...');
            $authResponse = $this->sunSyncService->authenticate($username, $password);
            
            if ($authResponse && !isset($authResponse['error'])) {
                $this->info('Getting plant info...');
                $plantInfo = $this->sunSyncService->getPlantInfo();
                
                if ($plantInfo && isset($plantInfo['id'])) {
                    $this->info('Getting inverter info...');
                    $inverterInfo = $this->sunSyncService->getInverterInfo($plantInfo['id']);
                    
                    if ($inverterInfo && isset($inverterInfo['sn'])) {
                        $this->info('Getting inverter flow info...');
                        $sunSyncData = $this->sunSyncService->getInverterFlowInfo($inverterInfo['sn']);
                        // Add plantInfo to sunSyncData
                        $sunSyncData['plantInfo'] = $plantInfo;
                        
                        $this->info('Successfully retrieved SunSync data.');
                    } else {
                        $this->error('Inverter info missing or invalid');
                    }
                } else {
                    $this->error('Plant info missing or invalid');
                }
            } else {
                $this->error('SunSync authentication failed');
            }
        } else {
            $this->error('SunSync credentials not configured');
        }

        // Get Zappi data
        $this->info('Getting Zappi data...');
        $zappiData = $this->myEnergiApiService->getStatus();
        
        if (!empty($zappiData)) {
            $this->info('Successfully retrieved Zappi data.');
        } else {
            $this->error('Failed to retrieve Zappi data.');
        }

        // Store the latest status in the database if we have both data sets
        if (!empty($sunSyncData) && !empty($zappiData)) {
            try {
                $this->info('Storing energy flow log...');
                $energyFlowLog = $this->energyFlowService->logEnergyFlow($sunSyncData, $zappiData);
                $this->line("Created record ID: {$energyFlowLog->id}");
                $this->info('Successfully stored energy flow log.');
            } catch (\Exception $e) {
                $this->error('Error storing energy flow log: ' . $e->getMessage());
                Log::error('Error storing energy flow log: ' . $e->getMessage());
                Log::error('Stack trace: ' . $e->getTraceAsString());
                return 1;
            }
        } else {
            $this->error('Cannot store energy flow log: Missing data');
            Log::warning('Cannot store energy flow log: Missing data', [
                'has_sunsync_data' => !empty($sunSyncData),
                'has_zappi_data' => !empty($zappiData)
            ]);
            return 1;
        }
        
        $this->info('Energy flow data update completed successfully.');
        return 0;
    }
}
