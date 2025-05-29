<?php

namespace App\Service;

use App\Entity\SystemLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;

class LoggingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private LoggerInterface $logger
    ) {}

    /**
     * Log authentication events
     */
    public function logAuthentication(string $action, User $user = null, array $context = []): void
    {
        $this->log('info', 'authentication', $action, array_merge($context, [
            'user_id' => $user?->getId(),
            'email' => $user?->getEmail()
        ]), $user);
    }

    /**
     * Log successful login
     */
    public function logSuccessfulLogin(User $user): void
    {
        $this->logAuthentication('User logged in successfully', $user, [
            'login_method' => 'form'
        ]);
    }

    /**
     * Log failed login attempt
     */
    public function logFailedLogin(string $email, string $reason = 'Invalid credentials'): void
    {
        $this->log('warning', 'authentication', 'Failed login attempt', [
            'email' => $email,
            'reason' => $reason,
            'ip_address' => $this->getClientIp()
        ]);
    }

    /**
     * Log logout
     */
    public function logLogout(User $user): void
    {
        $this->logAuthentication('User logged out', $user);
    }

    /**
     * Log request creation
     */
    public function logRequestCreated(\App\Entity\Request $request): void
    {
        $this->log('info', 'request', 'New service request created', [
            'request_id' => $request->getId(),
            'tracking_number' => $request->getTrackingNumber(),
            'service_family' => $request->getServiceFamily(),
            'title' => $request->getTitle()
        ], $request->getUser());
    }

    /**
     * Log request status change
     */
    public function logRequestStatusChanged(\App\Entity\Request $request, string $previousStatus, User $changedBy = null): void
    {
        $this->log('info', 'request', 'Request status changed', [
            'request_id' => $request->getId(),
            'tracking_number' => $request->getTrackingNumber(),
            'previous_status' => $previousStatus,
            'new_status' => $request->getStatus(),
            'changed_by' => $changedBy?->getFullName()
        ], $changedBy);
    }

    /**
     * Log document upload
     */
    public function logDocumentUploaded(\App\Entity\Document $document): void
    {
        $this->log('info', 'document', 'Document uploaded', [
            'document_id' => $document->getId(),
            'document_name' => $document->getName(),
            'document_type' => $document->getDocumentType(),
            'file_size' => $document->getFileSize(),
            'mime_type' => $document->getMimeType()
        ], $document->getUploadedBy());
    }

    /**
     * Log document verification
     */
    public function logDocumentVerified(\App\Entity\Document $document, User $verifiedBy): void
    {
        $this->log('info', 'document', 'Document verified', [
            'document_id' => $document->getId(),
            'document_name' => $document->getName(),
            'verified_by' => $verifiedBy->getFullName()
        ], $verifiedBy);
    }

    /**
     * Log document rejection
     */
    public function logDocumentRejected(\App\Entity\Document $document, User $rejectedBy, string $reason): void
    {
        $this->log('warning', 'document', 'Document rejected', [
            'document_id' => $document->getId(),
            'document_name' => $document->getName(),
            'rejected_by' => $rejectedBy->getFullName(),
            'reason' => $reason
        ], $rejectedBy);
    }

    /**
     * Log user account creation
     */
    public function logUserCreated(User $user, User $createdBy = null): void
    {
        $this->log('info', 'user', 'User account created', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'full_name' => $user->getFullName(),
            'roles' => $user->getRoles(),
            'created_by' => $createdBy?->getFullName()
        ], $createdBy);
    }

    /**
     * Log user account modification
     */
    public function logUserModified(User $user, array $changes, User $modifiedBy = null): void
    {
        $this->log('info', 'user', 'User account modified', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'changes' => $changes,
            'modified_by' => $modifiedBy?->getFullName()
        ], $modifiedBy);
    }

    /**
     * Log user account deletion
     */
    public function logUserDeleted(User $user, User $deletedBy = null): void
    {
        $this->log('warning', 'user', 'User account deleted', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'full_name' => $user->getFullName(),
            'deleted_by' => $deletedBy?->getFullName()
        ], $deletedBy);
    }

    /**
     * Log security events
     */
    public function logSecurityEvent(string $event, string $level = 'warning', array $context = []): void
    {
        $this->log($level, 'security', $event, $context);
    }

    /**
     * Log password reset
     */
    public function logPasswordReset(User $user, User $resetBy = null): void
    {
        $this->log('warning', 'security', 'Password reset performed', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'reset_by' => $resetBy?->getFullName()
        ], $resetBy);
    }

    /**
     * Log failed file upload
     */
    public function logFailedFileUpload(string $fileName, string $error, User $user = null): void
    {
        $this->log('error', 'system', 'File upload failed', [
            'file_name' => $fileName,
            'error' => $error,
            'max_file_size' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size')
        ], $user);
    }

    /**
     * Log system errors
     */
    public function logSystemError(string $message, array $context = [], \Throwable $exception = null): void
    {
        $errorContext = $context;
        
        if ($exception) {
            $errorContext['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }

        $this->log('error', 'system', $message, $errorContext);
    }

    /**
     * Log database operations
     */
    public function logDatabaseOperation(string $operation, string $table, array $data = [], User $user = null): void
    {
        $this->log('info', 'database', "Database {$operation} on {$table}", [
            'operation' => $operation,
            'table' => $table,
            'data_hash' => md5(json_encode($data)), // Don't log sensitive data directly
            'record_count' => is_array($data) ? count($data) : 1
        ], $user);
    }

    /**
     * Log API access
     */
    public function logApiAccess(string $endpoint, string $method, int $statusCode, User $user = null): void
    {
        $level = $statusCode >= 400 ? 'warning' : 'info';
        
        $this->log($level, 'api', "API {$method} {$endpoint}", [
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'user_agent' => $this->getUserAgent()
        ], $user);
    }

    /**
     * Log configuration changes
     */
    public function logConfigurationChange(string $setting, $oldValue, $newValue, User $changedBy = null): void
    {
        $this->log('warning', 'system', 'Configuration setting changed', [
            'setting' => $setting,
            'old_value' => $this->sanitizeValue($oldValue),
            'new_value' => $this->sanitizeValue($newValue),
            'changed_by' => $changedBy?->getFullName()
        ], $changedBy);
    }

    /**
     * Log bulk operations
     */
    public function logBulkOperation(string $operation, string $entityType, array $ids, User $performedBy = null): void
    {
        $this->log('info', 'system', "Bulk {$operation} performed", [
            'operation' => $operation,
            'entity_type' => $entityType,
            'entity_count' => count($ids),
            'entity_ids' => $ids,
            'performed_by' => $performedBy?->getFullName()
        ], $performedBy);
    }

    /**
     * Log data export operations
     */
    public function logDataExport(string $type, string $format, int $recordCount, User $exportedBy = null): void
    {
        $this->log('info', 'system', 'Data export performed', [
            'export_type' => $type,
            'format' => $format,
            'record_count' => $recordCount,
            'exported_by' => $exportedBy?->getFullName()
        ], $exportedBy);
    }

    /**
     * Log system maintenance events
     */
    public function logMaintenanceEvent(string $event, array $context = []): void
    {
        $this->log('info', 'system', $event, array_merge($context, [
            'maintenance_time' => new \DateTimeImmutable()
        ]));
    }

    /**
     * Get activity logs for a specific user
     */
    public function getUserActivityLogs(User $user, int $limit = 50): array
    {
        return $this->entityManager
            ->getRepository(SystemLog::class)
            ->createQueryBuilder('sl')
            ->where('sl.user = :user')
            ->setParameter('user', $user)
            ->orderBy('sl.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get system logs by category and level
     */
    public function getLogsByCategory(string $category, string $level = null, int $limit = 100): array
    {
        $qb = $this->entityManager
            ->getRepository(SystemLog::class)
            ->createQueryBuilder('sl')
            ->where('sl.category = :category')
            ->setParameter('category', $category)
            ->orderBy('sl.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($level) {
            $qb->andWhere('sl.level = :level')
               ->setParameter('level', $level);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Clean old logs
     */
    public function cleanOldLogs(int $daysToKeep = 30, string $level = null): int
    {
        $cutoffDate = new \DateTimeImmutable("-{$daysToKeep} days");
        
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->delete(SystemLog::class, 'sl')
            ->where('sl.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoffDate);

        if ($level) {
            $qb->andWhere('sl.level = :level')
               ->setParameter('level', $level);
        }

        $deletedCount = $qb->getQuery()->execute();
        
        $this->log('info', 'system', 'Old logs cleaned', [
            'days_kept' => $daysToKeep,
            'level_filter' => $level,
            'deleted_count' => $deletedCount
        ]);

        return $deletedCount;
    }

    /**
     * Core logging method
     */
    private function log(string $level, string $category, string $message, array $context = [], User $user = null): void
    {
        try {
            $systemLog = new SystemLog();
            $systemLog->setLevel($level);
            $systemLog->setCategory($category);
            $systemLog->setMessage($message);
            $systemLog->setContext($context);
            $systemLog->setIpAddress($this->getClientIp());
            $systemLog->setUserAgent($this->getUserAgent());
            $systemLog->setUser($user ?: $this->getCurrentUser());

            $this->entityManager->persist($systemLog);
            $this->entityManager->flush();

            // Also log to Symfony's logger for external log aggregation
            $this->logger->log($level, $message, array_merge($context, [
                'category' => $category,
                'user_id' => $systemLog->getUser()?->getId(),
                'ip_address' => $systemLog->getIpAddress()
            ]));

        } catch (\Exception $e) {
            // If logging fails, at least log to Symfony's default logger
            $this->logger->error('Failed to create system log entry', [
                'original_message' => $message,
                'original_level' => $level,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    private function getClientIp(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->getClientIp();
    }

    private function getUserAgent(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->headers->get('User-Agent');
    }

    private function sanitizeValue($value): string
    {
        if (is_string($value) && (str_contains(strtolower($value), 'password') || str_contains(strtolower($value), 'token'))) {
            return '[REDACTED]';
        }
        
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        
        return (string) $value;
    }
}