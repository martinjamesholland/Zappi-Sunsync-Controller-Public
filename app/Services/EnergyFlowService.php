<?php

namespace App\Services;

use App\Models\EnergyFlowLog;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EnergyFlowService
{
    /**
     * Log the current energy flow data
     *
     * @param array $sunSyncData
     * @param array $zappiData
     * @return EnergyFlowLog
     */
    public function logEnergyFlow(array $sunSyncData, array $zappiData): EnergyFlowLog
    {
        try {
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

            return EnergyFlowLog::create($data);
        } catch (\Exception $e) {
            Log::error('Failed to log energy flow data: ' . $e->getMessage());
            throw $e;
        }
    }
}
