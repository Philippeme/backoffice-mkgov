<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\StatisticsService;
use App\Service\MapService;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    public function __construct(
        private StatisticsService $statisticsService,
        private MapService $mapService
    ) {}

    #[Route('/', name: 'admin_dashboard')]
    public function index(): Response
    {
        // Get basic dashboard data
        $stats = $this->statisticsService->getDashboardStats();
        $recentRequests = $this->statisticsService->getRecentRequests(10);
        
        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'recent_requests' => $recentRequests,
            'page_title' => 'Dashboard',
            'active_menu' => 'dashboard'
        ]);
    }

    #[Route('/api/dashboard/stats', name: 'admin_dashboard_stats')]
    public function getStats(Request $request): JsonResponse
    {
        $filters = [
            'region' => $request->query->get('region'),
            'service_family' => $request->query->get('service_family'),
            'status' => $request->query->get('status'),
            'year' => $request->query->get('year', date('Y'))
        ];

        $stats = $this->statisticsService->getFilteredStats($filters);
        
        return new JsonResponse([
            'success' => true,
            'data' => $stats
        ]);
    }

    #[Route('/api/dashboard/map', name: 'admin_dashboard_map_data')]
    public function getMapData(Request $request): JsonResponse
    {
        $filters = [
            'region' => $request->query->get('region'),
            'service_family' => $request->query->get('service_family'),
            'status' => $request->query->get('status'),
            'year' => $request->query->get('year', date('Y'))
        ];

        $mapData = $this->mapService->getRegionalData($filters);
        
        return new JsonResponse([
            'success' => true,
            'data' => $mapData
        ]);
    }

    #[Route('/api/dashboard/charts/{type}', name: 'admin_dashboard_charts')]
    public function getChartData(string $type, Request $request): JsonResponse
    {
        $filters = [
            'region' => $request->query->get('region'),
            'service_family' => $request->query->get('service_family'),
            'status' => $request->query->get('status'),
            'year' => $request->query->get('year', date('Y'))
        ];

        $chartData = match($type) {
            'requests' => $this->statisticsService->getRequestsChartData($filters),
            'procedures' => $this->statisticsService->getProceduresChartData($filters),
            'users' => $this->statisticsService->getUsersChartData($filters),
            'entities' => $this->statisticsService->getEntitiesChartData($filters),
            default => []
        };
        
        return new JsonResponse([
            'success' => true,
            'data' => $chartData
        ]);
    }

    #[Route('/api/dashboard/filters', name: 'admin_dashboard_filters')]
    public function getFilters(): JsonResponse
    {
        $filters = [
            'regions' => $this->statisticsService->getRegions(),
            'service_families' => $this->statisticsService->getServiceFamilies(),
            'statuses' => $this->statisticsService->getStatuses(),
            'years' => $this->statisticsService->getAvailableYears()
        ];
        
        return new JsonResponse([
            'success' => true,
            'data' => $filters
        ]);
    }
}