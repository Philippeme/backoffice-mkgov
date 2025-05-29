<?php

namespace App\Service;

use App\Service\StatisticsService;

class MapService
{
    public function __construct(
        private StatisticsService $statisticsService
    ) {}

    public function getRegionalData(array $filters): array
    {
        $regions = $this->statisticsService->getRegions();
        $regionalData = [];

        foreach ($regions as $regionCode => $regionName) {
            $regionFilters = array_merge($filters, ['region' => $regionCode]);
            $stats = $this->statisticsService->getFilteredStats($regionFilters);
            
            $regionalData[] = [
                'region' => $regionCode,
                'name' => $regionName,
                'coordinates' => $this->getRegionCoordinates($regionCode),
                'total_requests' => $stats['total_requests'],
                'completed_requests' => $stats['completed_requests'],
                'pending_requests' => $stats['pending_requests'],
                'activity_level' => $this->getActivityLevel($stats['total_requests'])
            ];
        }

        return $regionalData;
    }

    private function getRegionCoordinates(string $regionCode): array
    {
        $coordinates = [
            'centre' => [3.8667, 11.5167],
            'littoral' => [4.0511, 9.7679],
            'ouest' => [5.4667, 10.5000],
            'nord' => [9.3265, 13.3833],
            'adamaoua' => [6.5000, 12.5000],
            'est' => [4.0000, 14.0000],
            'nord-ouest' => [6.2000, 10.2500],
            'sud-ouest' => [4.6167, 9.2667],
            'sud' => [2.7833, 11.5000],
            'extreme-nord' => [10.5833, 14.2167]
        ];

        return $coordinates[$regionCode] ?? [0, 0];
    }

    private function getActivityLevel(int $totalRequests): string
    {
        if ($totalRequests > 300) {
            return 'high';
        } elseif ($totalRequests > 150) {
            return 'medium';
        }
        return 'low';
    }
}