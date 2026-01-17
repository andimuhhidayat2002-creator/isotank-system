<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterIsotank;
use Illuminate\Http\JsonResponse;

class FillingStatusController extends Controller
{
    /**
     * Get all available filling statuses
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $statuses = [];
        
        foreach (MasterIsotank::getValidFillingStatuses() as $code => $description) {
            $statuses[] = [
                'code' => $code,
                'description' => $description,
                'color' => $this->getStatusColor($code),
                'icon' => $this->getStatusIcon($code),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $statuses,
        ]);
    }

    /**
     * Get statistics for each filling status
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $stats = [];
        
        foreach (MasterIsotank::getValidFillingStatuses() as $code => $description) {
            $count = MasterIsotank::active()
                ->where('filling_status_code', $code)
                ->count();
                
            $stats[] = [
                'code' => $code,
                'description' => $description,
                'count' => $count,
                'color' => $this->getStatusColor($code),
            ];
        }

        // Add count for isotanks without status
        $noStatusCount = MasterIsotank::active()
            ->whereNull('filling_status_code')
            ->count();
            
        $stats[] = [
            'code' => null,
            'description' => 'No Status',
            'count' => $noStatusCount,
            'color' => '#9E9E9E',
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get color for status
     */
    private function getStatusColor(string $code): string
    {
        return match($code) {
            MasterIsotank::FILLING_STATUS_READY_TO_FILL => '#4CAF50', // Green
            MasterIsotank::FILLING_STATUS_FILLED => '#2196F3', // Blue
            MasterIsotank::FILLING_STATUS_UNDER_MAINTENANCE => '#FF9800', // Orange
            MasterIsotank::FILLING_STATUS_WAITING_CALIBRATION => '#FFC107', // Amber
            MasterIsotank::FILLING_STATUS_CLASS_SURVEY => '#9C27B0', // Purple
            default => '#9E9E9E', // Grey
        };
    }

    /**
     * Get icon for status
     */
    private function getStatusIcon(string $code): string
    {
        return match($code) {
            MasterIsotank::FILLING_STATUS_READY_TO_FILL => 'check_circle_outline',
            MasterIsotank::FILLING_STATUS_FILLED => 'check_circle',
            MasterIsotank::FILLING_STATUS_UNDER_MAINTENANCE => 'build_circle',
            MasterIsotank::FILLING_STATUS_WAITING_CALIBRATION => 'schedule',
            MasterIsotank::FILLING_STATUS_CLASS_SURVEY => 'assignment',
            default => 'help_outline',
        };
    }
}
