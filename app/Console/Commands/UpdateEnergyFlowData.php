<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EnergyFlowLog;
use App\Services\SunSyncService;
use App\Services\MyEnergiApiService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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

    /**
     * Create a new command instance.
     */
    public function __construct(
        SunSyncService $sunSyncService,
        MyEnergiApiService $myEnergiApiService
    ) {
        parent::__construct();
        $this->sunSyncService = $sunSyncService;
        $this->myEnergiApiService = $myEnergiApiService;
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
                $this->storeEnergyFlowLog($sunSyncData, $zappiData);
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
    
    /**
     * Store energy flow data in the database
     */
    private function storeEnergyFlowLog($sunSyncData, $zappiData)
    {
        // Get SunSync timestamp from plantInfo
        $sunSyncTimestamp = null;
        if (isset($sunSyncData['updateAt'])) {
            $sunSyncTimestamp = Carbon::parse($sunSyncData['updateAt']);
        } elseif (isset($sunSyncData['plantInfo']['updateAt'])) {
            $sunSyncTimestamp = Carbon::parse($sunSyncData['plantInfo']['updateAt']);
        }

        // Convert Zappi timestamp to Carbon instance
        $zappiTimestamp = null;
        if (isset($zappiData['zappi'][0]['dat']) && isset($zappiData['zappi'][0]['tim'])) {
            $zappiTimestamp = Carbon::createFromFormat(
                'd-m-Y H:i:s',
                $zappiData['zappi'][0]['dat'] . ' ' . $zappiData['zappi'][0]['tim'],
                'UTC'
            )->setTimezone('Europe/London');
        }

        // Calculate the values
        $homeLoadPower = $sunSyncData['homeLoadPower'] ?? 0;
        $upsLoadPower = $sunSyncData['upsLoadPower'] ?? 0;
        $smartLoadPower = $sunSyncData['smartLoadPower'] ?? 0;
        $zappiDiv = $zappiData['zappi'][0]['div'] ?? 0;
        $zappiGrd = $zappiData['zappi'][0]['grd'] ?? 0;
        $zappiGen = $zappiData['zappi'][0]['gen'] ?? 0;

        $data = [
            'pv1_power' => $sunSyncData['pv'][0]['power'] ?? 0,
            'pv2_power' => $sunSyncData['pv'][1]['power'] ?? 0,
            'total_pv_power' => ($sunSyncData['pv'][0]['power'] ?? 0) + ($sunSyncData['pv'][1]['power'] ?? 0),
            'grid_power' => $zappiGrd ?? 0,
            'grid_power_sunsync' => ($sunSyncData['toGrid'] ?? true) ? -abs($sunSyncData['gridOrMeterPower'] ?? 0) : abs($sunSyncData['gridOrMeterPower'] ?? 0),
            'battery_power' => ($sunSyncData['toBat'] ?? true) ? -abs($sunSyncData['battPower'] ?? 0) : abs($sunSyncData['battPower'] ?? 0),
            'battery_soc' => $sunSyncData['soc'] ?? 0,
            'ups_load_power' => $upsLoadPower,
            'smart_load_power' => $smartLoadPower,
            'home_load_power' => $zappiGrd + $zappiGen - $zappiDiv,
            'home_load_sunsync' => $homeLoadPower - $zappiDiv,
            'combined_load_node_sunsync' => $upsLoadPower + $smartLoadPower + $homeLoadPower - $zappiDiv,
            'combined_load_node' => $upsLoadPower + $smartLoadPower + $zappiGrd + $zappiGen - $zappiDiv,
            'zappi_node' => $zappiDiv,
            'car_node_connection' => $zappiData['zappi'][0]['pst'] ?? null,
            'car_node_Mode' => $zappiData['zappi'][0]['zmo'] ?? null,
            'car_node_status' => $zappiData['zappi'][0]['sta'] ?? null,
            'last_consumption' => $zappiData['zappi'][0]['che'] ?? 0,
            'sunsync_updated_at' => $sunSyncTimestamp,
            'zappi_updated_at' => $zappiTimestamp
        ];

        $energyFlowLog = EnergyFlowLog::create($data);
        $this->line("Created record ID: {$energyFlowLog->id}");
    }
}
