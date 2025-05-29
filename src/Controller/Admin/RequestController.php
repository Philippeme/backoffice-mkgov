<?php

namespace App\Controller\Admin;

use App\Entity\Request;
use App\Form\RequestType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/requests')]
class RequestController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginator
    ) {}

    #[Route('/', name: 'admin_requests_index', methods: ['GET'])]
    public function index(HttpRequest $request): Response
    {
        $queryBuilder = $this->entityManager
            ->getRepository(Request::class)
            ->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC');

        // Apply filters
        if ($search = $request->query->get('search')) {
            $queryBuilder->andWhere('r.title LIKE :search OR r.description LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        if ($status = $request->query->get('status')) {
            $queryBuilder->andWhere('r.status = :status')
                        ->setParameter('status', $status);
        }

        if ($serviceFamily = $request->query->get('service_family')) {
            $queryBuilder->andWhere('r.serviceFamily = :service_family')
                        ->setParameter('service_family', $serviceFamily);
        }

        $pagination = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/requests/index.html.twig', [
            'requests' => $pagination,
            'page_title' => 'Requests Management',
            'active_menu' => 'requests'
        ]);
    }

    #[Route('/new', name: 'admin_requests_new', methods: ['GET', 'POST'])]
    public function new(HttpRequest $request): Response
    {
        $requestEntity = new Request();
        $form = $this->createForm(RequestType::class, $requestEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requestEntity->setCreatedAt(new \DateTimeImmutable());
            $requestEntity->setUpdatedAt(new \DateTimeImmutable());
            
            $this->entityManager->persist($requestEntity);
            $this->entityManager->flush();

            $this->addFlash('success', 'Request created successfully.');
            return $this->redirectToRoute('admin_requests_index');
        }

        return $this->render('admin/requests/new.html.twig', [
            'request_entity' => $requestEntity,
            'form' => $form,
            'page_title' => 'New Request',
            'active_menu' => 'requests'
        ]);
    }

    #[Route('/{id}', name: 'admin_requests_show', methods: ['GET'])]
    public function show(Request $requestEntity): Response
    {
        return $this->render('admin/requests/show.html.twig', [
            'request' => $requestEntity,
            'page_title' => 'Request Details',
            'active_menu' => 'requests'
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_requests_edit', methods: ['GET', 'POST'])]
    public function edit(HttpRequest $request, Request $requestEntity): Response
    {
        $form = $this->createForm(RequestType::class, $requestEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requestEntity->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->addFlash('success', 'Request updated successfully.');
            return $this->redirectToRoute('admin_requests_index');
        }

        return $this->render('admin/requests/edit.html.twig', [
            'request_entity' => $requestEntity,
            'form' => $form,
            'page_title' => 'Edit Request',
            'active_menu' => 'requests'
        ]);
    }

    #[Route('/{id}', name: 'admin_requests_delete', methods: ['POST'])]
    public function delete(HttpRequest $request, Request $requestEntity): Response
    {
        if ($this->isCsrfTokenValid('delete'.$requestEntity->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($requestEntity);
            $this->entityManager->flush();
            $this->addFlash('success', 'Request deleted successfully.');
        }

        return $this->redirectToRoute('admin_requests_index');
    }

    #[Route('/api/bulk-action', name: 'admin_requests_bulk_action', methods: ['POST'])]
    public function bulkAction(HttpRequest $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? '';
        $ids = $data['ids'] ?? [];

        if (empty($ids) || empty($action)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid parameters']);
        }

        $requests = $this->entityManager
            ->getRepository(Request::class)
            ->findBy(['id' => $ids]);

        switch ($action) {
            case 'approve':
                foreach ($requests as $req) {
                    $req->setStatus('approved');
                    $req->setUpdatedAt(new \DateTimeImmutable());
                }
                break;
            case 'reject':
                foreach ($requests as $req) {
                    $req->setStatus('rejected');
                    $req->setUpdatedAt(new \DateTimeImmutable());
                }
                break;
            case 'delete':
                foreach ($requests as $req) {
                    $this->entityManager->remove($req);
                }
                break;
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('%d requests processed successfully', count($requests))
        ]);
    }
}