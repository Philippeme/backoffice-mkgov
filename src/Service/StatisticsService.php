<?php

namespace App\Service;

use App\Entity\Request;
use App\Entity\User;
use App\Entity\Procedure;
use App\Entity\Document;
use App\Entity\PublicEntity;
use Doctrine\ORM\EntityManagerInterface;

class StatisticsService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function getDashboardStats(): array
    {
        $requestRepo = $this->entityManager->getRepository(Request::class);
        $userRepo = $this->entityManager->getRepository(User::class);
        $procedureRepo = $this->entityManager->getRepository(Procedure::class);
        $documentRepo = $this->entityManager->getRepository(Document::class);

        return [
            'total_requests' => $requestRepo->count([]),
            'completed_requests' => $requestRepo->count(['status' => 'completed']),
            'pending_requests' => $requestRepo->count(['status' => 'pending']),
            'processing_requests' => $requestRepo->count(['status' => 'processing']),
            'active_users' => $userRepo->count(['isActive' => true]),
            'total_procedures' => $procedureRepo->count([]),
            'total_documents' => $documentRepo->count([]),
            'pending_approvals' => $requestRepo->count(['status' => 'under_review']),
            'pending_documents' => $documentRepo->count(['status' => 'pending']),
            'system_alerts' => 3, // This would come from a real monitoring system
            'requests_change' => $this->calculatePercentageChange('requests'),
            'completed_change' => $this->calculatePercentageChange('completed'),
            'pending_change' => $this->calculatePercentageChange('pending'),
            'users_change' => $this->calculatePercentageChange('users')
        ];
    }

    public function getFilteredStats(array $filters): array
    {
        $qb = $this->entityManager->getRepository(Request::class)->createQueryBuilder('r');
        
        $this->applyFilters($qb, $filters);
        
        $requests = $qb->getQuery()->getResult();
        
        $stats = [
            'total_requests' => count($requests),
            'completed_requests' => count(array_filter($requests, fn($r) => $r->getStatus() === 'completed')),
            'pending_requests' => count(array_filter($requests, fn($r) => $r->getStatus() === 'pending')),
            'processing_requests' => count(array_filter($requests, fn($r) => $r->getStatus() === 'processing')),
        ];
        
        return $stats;
    }

    public function getRecentRequests(int $limit = 10): array
    {
        return $this->entityManager->getRepository(Request::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->addSelect('u')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getRequestsChartData(array $filters): array
    {
        $qb = $this->entityManager->getRepository(Request::class)->createQueryBuilder('r');
        $this->applyFilters($qb, $filters);
        
        $qb->select('DATE(r.createdAt) as date, COUNT(r.id) as count')
           ->groupBy('DATE(r.createdAt)')
           ->orderBy('date', 'ASC');
        
        $results = $qb->getQuery()->getArrayResult();
        
        return [
            'labels' => array_column($results, 'date'),
            'values' => array_column($results, 'count')
        ];
    }

    public function getProceduresChartData(array $filters): array
    {
        $qb = $this->entityManager->getRepository(Procedure::class)->createQueryBuilder('p');
        
        if (!empty($filters['category'])) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $filters['category']);
        }
        
        $qb->select('p.category, COUNT(p.id) as count')
           ->groupBy('p.category')
           ->orderBy('count', 'DESC');
        
        $results = $qb->getQuery()->getArrayResult();
        
        return [
            'labels' => array_column($results, 'category'),
            'values' => array_column($results, 'count')
        ];
    }

    public function getUsersChartData(array $filters): array
    {
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u');
        
        $qb->select('DATE(u.createdAt) as date, COUNT(u.id) as count')
           ->where('u.createdAt >= :start_date')
           ->setParameter('start_date', new \DateTime('-30 days'))
           ->groupBy('DATE(u.createdAt)')
           ->orderBy('date', 'ASC');
        
        $results = $qb->getQuery()->getArrayResult();
        
        return [
            'labels' => array_column($results, 'date'),
            'values' => array_column($results, 'count')
        ];
    }

    public function getEntitiesChartData(array $filters): array
    {
        $qb = $this->entityManager->getRepository(PublicEntity::class)->createQueryBuilder('e');
        
        if (!empty($filters['region'])) {
            $qb->andWhere('e.region = :region')
               ->setParameter('region', $filters['region']);
        }
        
        $qb->select('e.type, COUNT(e.id) as count')
           ->groupBy('e.type')
           ->orderBy('count', 'DESC');
        
        $results = $qb->getQuery()->getArrayResult();
        
        return [
            'labels' => array_column($results, 'type'),
            'values' => array_column($results, 'count')
        ];
    }

    public function getRegions(): array
    {
        return [
            'centre' => 'Centre',
            'littoral' => 'Littoral',
            'ouest' => 'Ouest',
            'nord' => 'Nord',
            'adamaoua' => 'Adamaoua',
            'est' => 'Est',
            'nord-ouest' => 'Nord-Ouest',
            'sud-ouest' => 'Sud-Ouest',
            'sud' => 'Sud',
            'extreme-nord' => 'Extrême-Nord'
        ];
    }

    public function getServiceFamilies(): array
    {
        return [
            'police_justice' => 'Police & Justice',
            'family' => 'Family',
            'transport' => 'Transport',
            'education' => 'Education',
            'business' => 'Business',
            'public_service' => 'Public Service',
            'land_construction' => 'Land & Construction',
            'consular' => 'Consular Services',
            'health' => 'Health',
            'civic_life' => 'Civic Life'
        ];
    }

    public function getStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'under_review' => 'Under Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'completed' => 'Completed'
        ];
    }

    public function getAvailableYears(): array
    {
        $currentYear = (int) date('Y');
        $years = [];
        
        for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
            $years[$i] = (string) $i;
        }
        
        return $years;
    }

    private function applyFilters($queryBuilder, array $filters): void
    {
        if (!empty($filters['region'])) {
            // Assuming we have a region field in request or user entity
            $queryBuilder->leftJoin('r.user', 'u')
                        ->andWhere('u.region = :region')
                        ->setParameter('region', $filters['region']);
        }

        if (!empty($filters['service_family'])) {
            $queryBuilder->andWhere('r.serviceFamily = :service_family')
                        ->setParameter('service_family', $filters['service_family']);
        }

        if (!empty($filters['status'])) {
            $queryBuilder->andWhere('r.status = :status')
                        ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['year'])) {
            $queryBuilder->andWhere('YEAR(r.createdAt) = :year')
                        ->setParameter('year', $filters['year']);
        }
    }

    private function calculatePercentageChange(string $type): float
    {
        // This would calculate the actual percentage change based on historical data
        // For demo purposes, returning random values
        return round((rand(-20, 30) / 10), 1);
    }
}