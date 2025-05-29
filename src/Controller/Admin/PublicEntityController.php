<?php

namespace App\Controller\Admin;

use App\Entity\PublicEntity;
use App\Form\PublicEntityType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/entities')]
class PublicEntityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginator
    ) {}

    #[Route('/', name: 'admin_entities_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $queryBuilder = $this->entityManager
            ->getRepository(PublicEntity::class)
            ->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC');

        // Apply filters
        if ($search = $request->query->get('search')) {
            $queryBuilder->andWhere('e.name LIKE :search OR e.description LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        if ($type = $request->query->get('type')) {
            $queryBuilder->andWhere('e.type = :type')
                        ->setParameter('type', $type);
        }

        if ($region = $request->query->get('region')) {
            $queryBuilder->andWhere('e.region = :region')
                        ->setParameter('region', $region);
        }

        if ($status = $request->query->get('status')) {
            $isActive = $status === 'active';
            $queryBuilder->andWhere('e.isActive = :status')
                        ->setParameter('status', $isActive);
        }

        $pagination = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/public_entities/index.html.twig', [
            'entities' => $pagination,
            'page_title' => 'Public Entities Management',
            'active_menu' => 'entities'
        ]);
    }

    #[Route('/new', name: 'admin_entities_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $entity = new PublicEntity();
        $form = $this->createForm(PublicEntityType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity->setCreatedAt(new \DateTimeImmutable());
            $entity->setUpdatedAt(new \DateTimeImmutable());
            
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->addFlash('success', 'Public entity created successfully.');
            return $this->redirectToRoute('admin_entities_index');
        }

        return $this->render('admin/public_entities/new.html.twig', [
            'entity' => $entity,
            'form' => $form,
            'page_title' => 'New Public Entity',
            'active_menu' => 'entities'
        ]);
    }

    #[Route('/{id}', name: 'admin_entities_show', methods: ['GET'])]
    public function show(PublicEntity $entity): Response
    {
        return $this->render('admin/public_entities/show.html.twig', [
            'entity' => $entity,
            'page_title' => 'Entity Details',
            'active_menu' => 'entities'
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_entities_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PublicEntity $entity): Response
    {
        $form = $this->createForm(PublicEntityType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->addFlash('success', 'Public entity updated successfully.');
            return $this->redirectToRoute('admin_entities_index');
        }

        return $this->render('admin/public_entities/edit.html.twig', [
            'entity' => $entity,
            'form' => $form,
            'page_title' => 'Edit Public Entity',
            'active_menu' => 'entities'
        ]);
    }

    #[Route('/{id}', name: 'admin_entities_delete', methods: ['POST'])]
    public function delete(Request $request, PublicEntity $entity): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
            $this->addFlash('success', 'Public entity deleted successfully.');
        }

        return $this->redirectToRoute('admin_entities_index');
    }

    #[Route('/{id}/toggle-status', name: 'admin_entities_toggle_status', methods: ['POST'])]
    public function toggleStatus(PublicEntity $entity): JsonResponse
    {
        $entity->setIsActive(!$entity->getIsActive());
        $entity->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'status' => $entity->getIsActive(),
            'message' => $entity->getIsActive() ? 'Entity activated' : 'Entity deactivated'
        ]);
    }

    #[Route('/api/bulk-action', name: 'admin_entities_bulk_action', methods: ['POST'])]
    public function bulkAction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? '';
        $ids = $data['ids'] ?? [];

        if (empty($ids) || empty($action)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid parameters']);
        }

        $entities = $this->entityManager
            ->getRepository(PublicEntity::class)
            ->findBy(['id' => $ids]);

        switch ($action) {
            case 'activate':
                foreach ($entities as $entity) {
                    $entity->setIsActive(true);
                    $entity->setUpdatedAt(new \DateTimeImmutable());
                }
                break;
            case 'deactivate':
                foreach ($entities as $entity) {
                    $entity->setIsActive(false);
                    $entity->setUpdatedAt(new \DateTimeImmutable());
                }
                break;
            case 'delete':
                foreach ($entities as $entity) {
                    $this->entityManager->remove($entity);
                }
                break;
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('%d entities processed successfully', count($entities))
        ]);
    }
}