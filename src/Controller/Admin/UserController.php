<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/users')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginator,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/', name: 'admin_users_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $queryBuilder = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        // Apply filters
        if ($search = $request->query->get('search')) {
            $queryBuilder->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        if ($role = $request->query->get('role')) {
            $queryBuilder->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%' . $role . '%');
        }

        if ($status = $request->query->get('status')) {
            $queryBuilder->andWhere('u.isActive = :status')
                        ->setParameter('status', $status === 'active');
        }

        $pagination = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/users/index.html.twig', [
            'users' => $pagination,
            'page_title' => 'Users Management',
            'active_menu' => 'users'
        ]);
    }

    #[Route('/new', name: 'admin_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'User created successfully.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/new.html.twig', [
            'user' => $user,
            'form' => $form,
            'page_title' => 'New User',
            'active_menu' => 'users'
        ]);
    }

    #[Route('/{id}', name: 'admin_users_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'page_title' => 'User Details',
            'active_menu' => 'users'
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_users_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password if provided
            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $user->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->addFlash('success', 'User updated successfully.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'form' => $form,
            'page_title' => 'Edit User',
            'active_menu' => 'users'
        ]);
    }

    #[Route('/{id}', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/toggle-status', name: 'admin_users_toggle_status', methods: ['POST'])]
    public function toggleStatus(User $user): JsonResponse
    {
        $user->setIsActive(!$user->getIsActive());
        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'status' => $user->getIsActive(),
            'message' => $user->getIsActive() ? 'User activated' : 'User deactivated'
        ]);
    }

    #[Route('/{id}/permissions', name: 'admin_users_permissions', methods: ['GET', 'POST'])]
    public function managePermissions(Request $request, User $user): Response
    {
        if ($request->isMethod('POST')) {
            $permissions = $request->request->all('permissions');
            $user->setPermissions($permissions);
            $user->setUpdatedAt(new \DateTimeImmutable());
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Permissions updated successfully.');
            return $this->redirectToRoute('admin_users_index');
        }

        $availablePermissions = [
            'requests' => [
                'view' => 'View Requests',
                'create' => 'Create Requests',
                'edit' => 'Edit Requests',
                'delete' => 'Delete Requests',
                'approve' => 'Approve Requests'
            ],
            'procedures' => [
                'view' => 'View Procedures',
                'create' => 'Create Procedures',
                'edit' => 'Edit Procedures',
                'delete' => 'Delete Procedures'
            ],
            'documents' => [
                'view' => 'View Documents',
                'upload' => 'Upload Documents',
                'edit' => 'Edit Documents',
                'delete' => 'Delete Documents'
            ],
            'users' => [
                'view' => 'View Users',
                'create' => 'Create Users',
                'edit' => 'Edit Users',
                'delete' => 'Delete Users',
                'permissions' => 'Manage Permissions'
            ],
            'system' => [
                'settings' => 'System Settings',
                'logs' => 'View Logs',
                'backup' => 'System Backup'
            ]
        ];

        return $this->render('admin/users/permissions.html.twig', [
            'user' => $user,
            'available_permissions' => $availablePermissions,
            'page_title' => 'Manage Permissions - ' . $user->getFullName(),
            'active_menu' => 'users'
        ]);
    }

    #[Route('/api/bulk-action', name: 'admin_users_bulk_action', methods: ['POST'])]
    public function bulkAction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? '';
        $ids = $data['ids'] ?? [];

        if (empty($ids) || empty($action)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid parameters']);
        }

        $users = $this->entityManager
            ->getRepository(User::class)
            ->findBy(['id' => $ids]);

        switch ($action) {
            case 'activate':
                foreach ($users as $user) {
                    $user->setIsActive(true);
                    $user->setUpdatedAt(new \DateTimeImmutable());
                }
                break;
            case 'deactivate':
                foreach ($users as $user) {
                    $user->setIsActive(false);
                    $user->setUpdatedAt(new \DateTimeImmutable());
                }
                break;
            case 'delete':
                foreach ($users as $user) {
                    $this->entityManager->remove($user);
                }
                break;
            case 'reset_password':
                foreach ($users as $user) {
                    // Generate temporary password
                    $tempPassword = $this->generateTemporaryPassword();
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $tempPassword);
                    $user->setPassword($hashedPassword);
                    $user->setMustChangePassword(true);
                    $user->setUpdatedAt(new \DateTimeImmutable());
                    
                    // Send email with temporary password (implement email service)
                    $this->emailService->sendPasswordReset($user, $tempPassword);
                }
                break;
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('%d users processed successfully', count($users))
        ]);
    }

    #[Route('/export/{format}', name: 'admin_users_export', methods: ['GET'])]
    public function export(string $format): Response
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($users);
            case 'excel':
                return $this->exportToExcel($users);
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    private function exportToCsv(array $users): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="users.csv"');

        $csv = "ID,First Name,Last Name,Email,Roles,Status,Created At\n";
        
        foreach ($users as $user) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s\n",
                $user->getId(),
                $user->getFirstName(),
                $user->getLastName(),
                $user->getEmail(),
                implode(';', $user->getRoles()),
                $user->getIsActive() ? 'Active' : 'Inactive',
                $user->getCreatedAt()->format('Y-m-d H:i:s')
            );
        }

        $response->setContent($csv);
        return $response;
    }

    private function exportToExcel(array $users): Response
    {
        // Excel export implementation would go here
        // For now, return CSV format
        return $this->exportToCsv($users);
    }

    private function generateTemporaryPassword(): string
    {
        return bin2hex(random_bytes(8));
    }
}