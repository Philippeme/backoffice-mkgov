<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Request;
use App\Entity\Document;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
        private string $senderEmail = 'noreply@mkgov.cm',
        private string $senderName = 'MK Gov Administration'
    ) {}

    /**
     * Send notification when a new request is created
     */
    public function notifyRequestCreated(Request $request): void
    {
        try {
            // Notify user about request creation
            $this->sendRequestCreatedEmail($request);
            
            // Notify administrators about new request
            $this->notifyAdministratorsNewRequest($request);
            
            $this->logger->info('Request creation notifications sent', [
                'request_id' => $request->getId(),
                'user_id' => $request->getUser()->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send request creation notifications', [
                'request_id' => $request->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification when request status changes
     */
    public function notifyRequestStatusChanged(Request $request, string $previousStatus): void
    {
        try {
            $this->sendRequestStatusEmail($request, $previousStatus);
            
            $this->logger->info('Request status change notification sent', [
                'request_id' => $request->getId(),
                'previous_status' => $previousStatus,
                'new_status' => $request->getStatus()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send request status notification', [
                'request_id' => $request->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification when a document is verified or rejected
     */
    public function notifyDocumentStatusChanged(Document $document): void
    {
        try {
            $this->sendDocumentStatusEmail($document);
            
            $this->logger->info('Document status change notification sent', [
                'document_id' => $document->getId(),
                'status' => $document->getStatus()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send document status notification', [
                'document_id' => $document->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail(User $user, string $temporaryPassword = null): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from($this->senderEmail)
                ->to($user->getEmail())
                ->subject($this->translator->trans('email.welcome.subject'))
                ->htmlTemplate('emails/welcome.html.twig')
                ->context([
                    'user' => $user,
                    'temporary_password' => $temporaryPassword,
                    'login_url' => $this->generateLoginUrl()
                ]);

            $this->mailer->send($email);
            
            $this->logger->info('Welcome email sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send welcome email', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(User $user, string $temporaryPassword): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from($this->senderEmail)
                ->to($user->getEmail())
                ->subject($this->translator->trans('email.password_reset.subject'))
                ->htmlTemplate('emails/password_reset.html.twig')
                ->context([
                    'user' => $user,
                    'temporary_password' => $temporaryPassword,
                    'login_url' => $this->generateLoginUrl()
                ]);

            $this->mailer->send($email);
            
            $this->logger->info('Password reset email sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send password reset email', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send bulk notifications to multiple users
     */
    public function sendBulkNotification(array $users, string $subject, string $message, array $context = []): int
    {
        $successCount = 0;
        
        foreach ($users as $user) {
            try {
                $email = (new TemplatedEmail())
                    ->from($this->senderEmail)
                    ->to($user->getEmail())
                    ->subject($subject)
                    ->htmlTemplate('emails/bulk_notification.html.twig')
                    ->context(array_merge([
                        'user' => $user,
                        'message' => $message
                    ], $context));

                $this->mailer->send($email);
                $successCount++;
            } catch (\Exception $e) {
                $this->logger->error('Failed to send bulk notification', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->logger->info('Bulk notification sent', [
            'total_users' => count($users),
            'successful_sends' => $successCount,
            'subject' => $subject
        ]);
        
        return $successCount;
    }

    /**
     * Send system maintenance notification
     */
    public function sendMaintenanceNotification(\DateTimeInterface $startTime, \DateTimeInterface $endTime, string $reason = null): void
    {
        try {
            // Get all active users (implement user repository method)
            // For now, we'll assume this method exists
            $users = []; // This would be populated from user repository
            
            $subject = $this->translator->trans('email.maintenance.subject');
            
            foreach ($users as $user) {
                $email = (new TemplatedEmail())
                    ->from($this->senderEmail)
                    ->to($user->getEmail())
                    ->subject($subject)
                    ->htmlTemplate('emails/maintenance.html.twig')
                    ->context([
                        'user' => $user,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'reason' => $reason
                    ]);

                $this->mailer->send($email);
            }
            
            $this->logger->info('Maintenance notification sent', [
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send maintenance notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send SMS notification (placeholder for SMS service integration)
     */
    public function sendSmsNotification(string $phoneNumber, string $message): bool
    {
        try {
            // Integrate with SMS service provider (e.g., Twilio, Nexmo, etc.)
            // For now, this is a placeholder
            
            $this->logger->info('SMS notification sent', [
                'phone_number' => $phoneNumber,
                'message_length' => strlen($message)
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send SMS notification', [
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    private function sendRequestCreatedEmail(Request $request): void
    {
        $email = (new TemplatedEmail())
            ->from($this->senderEmail)
            ->to($request->getUser()->getEmail())
            ->subject($this->translator->trans('email.request_created.subject', [
                'tracking_number' => $request->getTrackingNumber()
            ]))
            ->htmlTemplate('emails/request_created.html.twig')
            ->context([
                'request' => $request,
                'user' => $request->getUser(),
                'tracking_url' => $this->generateTrackingUrl($request->getTrackingNumber())
            ]);

        $this->mailer->send($email);
    }

    private function sendRequestStatusEmail(Request $request, string $previousStatus): void
    {
        $email = (new TemplatedEmail())
            ->from($this->senderEmail)
            ->to($request->getUser()->getEmail())
            ->subject($this->translator->trans('email.request_status.subject', [
                'tracking_number' => $request->getTrackingNumber(),
                'status' => $this->translator->trans('status.' . $request->getStatus())
            ]))
            ->htmlTemplate('emails/request_status_changed.html.twig')
            ->context([
                'request' => $request,
                'user' => $request->getUser(),
                'previous_status' => $previousStatus,
                'new_status' => $request->getStatus(),
                'tracking_url' => $this->generateTrackingUrl($request->getTrackingNumber())
            ]);

        $this->mailer->send($email);
    }

    private function sendDocumentStatusEmail(Document $document): void
    {
        $email = (new TemplatedEmail())
            ->from($this->senderEmail)
            ->to($document->getUploadedBy()->getEmail())
            ->subject($this->translator->trans('email.document_status.subject', [
                'document_name' => $document->getName(),
                'status' => $this->translator->trans('status.' . $document->getStatus())
            ]))
            ->htmlTemplate('emails/document_status_changed.html.twig')
            ->context([
                'document' => $document,
                'user' => $document->getUploadedBy(),
                'status' => $document->getStatus(),
                'notes' => $document->getNotes()
            ]);

        $this->mailer->send($email);
    }

    private function notifyAdministratorsNewRequest(Request $request): void
    {
        // Get all admin users (implement user repository method)
        // For now, we'll assume this method exists
        $adminUsers = []; // This would be populated from user repository
        
        foreach ($adminUsers as $admin) {
            $email = (new TemplatedEmail())
                ->from($this->senderEmail)
                ->to($admin->getEmail())
                ->subject($this->translator->trans('email.admin_new_request.subject'))
                ->htmlTemplate('emails/admin_new_request.html.twig')
                ->context([
                    'request' => $request,
                    'admin' => $admin,
                    'admin_url' => $this->generateAdminUrl($request->getId())
                ]);

            $this->mailer->send($email);
        }
    }

    private function generateLoginUrl(): string
    {
        // Generate login URL - this would use Symfony's URL generator
        return '/login';
    }

    private function generateTrackingUrl(string $trackingNumber): string
    {
        // Generate tracking URL - this would use Symfony's URL generator
        return '/track/' . $trackingNumber;
    }

    private function generateAdminUrl(int $requestId): string
    {
        // Generate admin URL - this would use Symfony's URL generator
        return '/admin/requests/' . $requestId;
    }

    /**
     * Get notification preferences for a user
     */
    public function getNotificationPreferences(User $user): array
    {
        // This would typically be stored in user preferences or a separate table
        return [
            'email_notifications' => true,
            'sms_notifications' => false,
            'request_updates' => true,
            'document_updates' => true,
            'system_updates' => false,
            'marketing' => false
        ];
    }

    /**
     * Update notification preferences for a user
     */
    public function updateNotificationPreferences(User $user, array $preferences): void
    {
        // This would typically update user preferences in the database
        $this->logger->info('Notification preferences updated', [
            'user_id' => $user->getId(),
            'preferences' => $preferences
        ]);
    }

    /**
     * Check if user should receive notification based on preferences
     */
    public function shouldSendNotification(User $user, string $notificationType): bool
    {
        $preferences = $this->getNotificationPreferences($user);
        
        return $preferences[$notificationType] ?? false;
    }
}