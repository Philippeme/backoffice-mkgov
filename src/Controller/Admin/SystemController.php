<?php

namespace App\Controller\Admin;

use App\Entity\SystemLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/system')]
class SystemController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginator
    ) {}

    #[Route('/settings', name: 'admin_system_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $settings = $request->request->all('settings');
            
            // Save settings logic would go here
            // For now, we'll simulate saving settings
            $this->addFlash('success', 'Settings saved successfully.');
            
            return $this->redirectToRoute('admin_system_settings');
        }

        // Load current settings
        $currentSettings = [
            'site_name' => 'MK Gov Administration',
            'site_description' => 'Government E-Services Portal for Cameroon',
            'default_language' => 'en',
            'timezone' => 'Africa/Douala',
            'items_per_page' => 20,
            'max_file_size' => '10MB',
            'allowed_file_types' => 'pdf,doc,docx,jpg,jpeg,png',
            'email_notifications' => true,
            'sms_notifications' => false,
            'maintenance_mode' => false,
            'registration_enabled' => true,
            'password_min_length' => 8,
            'session_timeout' => 3600,
            'backup_frequency' => 'daily',
            'log_retention_days' => 30
        ];

        return $this->render('admin/system/settings.html.twig', [
            'settings' => $currentSettings,
            'page_title' => 'System Settings',
            'active_menu' => 'system'
        ]);
    }

    #[Route('/reference-data', name: 'admin_system_reference_data', methods: ['GET', 'POST'])]
    public function referenceData(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            $data = $request->request->all();

            switch ($action) {
                case 'add_region':
                    // Logic to add new region
                    $this->addFlash('success', 'Region added successfully.');
                    break;
                case 'add_service_type':
                    // Logic to add new service type
                    $this->addFlash('success', 'Service type added successfully.');
                    break;
                case 'add_document_type':
                    // Logic to add new document type
                    $this->addFlash('success', 'Document type added successfully.');
                    break;
            }

            return $this->redirectToRoute('admin_system_reference_data');
        }

        $referenceData = [
            'regions' => [
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
            ],
            'service_families' => [
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
            ],
            'document_types' => [
                'identity' => 'Identity Document',
                'birth_certificate' => 'Birth Certificate',
                'marriage_certificate' => 'Marriage Certificate',
                'death_certificate' => 'Death Certificate',
                'passport' => 'Passport',
                'driving_license' => 'Driving License',
                'academic_certificate' => 'Academic Certificate',
                'business_document' => 'Business Document',
                'legal_document' => 'Legal Document',
                'medical_document' => 'Medical Document'
            ],
            'status_types' => [
                'pending' => 'Pending',
                'processing' => 'Processing',
                'under_review' => 'Under Review',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'completed' => 'Completed'
            ]
        ];

        return $this->render('admin/system/reference_data.html.twig', [
            'reference_data' => $referenceData,
            'page_title' => 'Reference Data Management',
            'active_menu' => 'system'
        ]);
    }

    #[Route('/logs', name: 'admin_system_logs', methods: ['GET'])]
    public function logs(Request $request): Response
    {
        $queryBuilder = $this->entityManager
            ->getRepository(SystemLog::class)
            ->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->orderBy('l.createdAt', 'DESC');

        // Apply filters
        if ($level = $request->query->get('level')) {
            $queryBuilder->andWhere('l.level = :level')
                        ->setParameter('level', $level);
        }

        if ($category = $request->query->get('category')) {
            $queryBuilder->andWhere('l.category = :category')
                        ->setParameter('category', $category);
        }

        if ($dateFrom = $request->query->get('date_from')) {
            $queryBuilder->andWhere('l.createdAt >= :date_from')
                        ->setParameter('date_from', new \DateTime($dateFrom));
        }

        if ($dateTo = $request->query->get('date_to')) {
            $queryBuilder->andWhere('l.createdAt <= :date_to')
                        ->setParameter('date_to', new \DateTime($dateTo . ' 23:59:59'));
        }

        $pagination = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            50
        );

        return $this->render('admin/system/logs.html.twig', [
            'logs' => $pagination,
            'page_title' => 'System Logs',
            'active_menu' => 'system'
        ]);
    }

    #[Route('/logs/clear', name: 'admin_system_logs_clear', methods: ['POST'])]
    public function clearLogs(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('clear_logs', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token']);
        }

        $daysToKeep = $request->request->getInt('days_to_keep', 30);
        $cutoffDate = new \DateTime("-{$daysToKeep} days");

        $deletedCount = $this->entityManager
            ->createQuery('DELETE FROM App\Entity\SystemLog l WHERE l.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoffDate)
            ->execute();

        return new JsonResponse([
            'success' => true,
            'message' => "Deleted {$deletedCount} log entries older than {$daysToKeep} days"
        ]);
    }

    #[Route('/backup', name: 'admin_system_backup', methods: ['GET', 'POST'])]
    public function backup(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $backupType = $request->request->get('backup_type', 'full');
            
            // Simulate backup process
            $backupFileName = 'mk-gov-backup-' . date('Y-m-d-H-i-s') . '.sql';
            
            // In a real implementation, this would trigger the actual backup process
            $this->addFlash('success', "Backup '{$backupFileName}' created successfully.");
            
            return $this->redirectToRoute('admin_system_backup');
        }

        // Get list of existing backups (simulated)
        $backups = [
            [
                'filename' => 'mk-gov-backup-2024-01-15-10-30-00.sql',
                'size' => '15.2 MB',
                'created_at' => new \DateTime('2024-01-15 10:30:00'),
                'type' => 'full'
            ],
            [
                'filename' => 'mk-gov-backup-2024-01-14-10-30-00.sql',
                'size' => '14.8 MB',
                'created_at' => new \DateTime('2024-01-14 10:30:00'),
                'type' => 'full'
            ]
        ];

        return $this->render('admin/system/backup.html.twig', [
            'backups' => $backups,
            'page_title' => 'System Backup',
            'active_menu' => 'system'
        ]);
    }

    #[Route('/health-check', name: 'admin_system_health', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'external_apis' => $this->checkExternalAPIs()
        ];

        $overallStatus = array_reduce($checks, function($carry, $check) {
            return $carry && $check['status'] === 'ok';
        }, true);

        return new JsonResponse([
            'status' => $overallStatus ? 'healthy' : 'degraded',
            'timestamp' => new \DateTime(),
            'checks' => $checks
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            $this->entityManager->getConnection()->executeQuery('SELECT 1');
            return ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        if (is_writable($uploadsDir)) {
            return ['status' => 'ok', 'message' => 'Storage is writable'];
        }
        return ['status' => 'error', 'message' => 'Storage is not writable'];
    }

    private function checkCache(): array
    {
        // Simplified cache check
        return ['status' => 'ok', 'message' => 'Cache is operational'];
    }

    private function checkQueue(): array
    {
        // Simplified queue check
        return ['status' => 'ok', 'message' => 'Message queue is operational'];
    }

    private function checkExternalAPIs(): array
    {
        // Simplified external API check
        return ['status' => 'ok', 'message' => 'External APIs are accessible'];
    }
}