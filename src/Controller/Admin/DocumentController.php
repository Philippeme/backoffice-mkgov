<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Form\DocumentType;
use App\Service\DocumentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/documents')]
class DocumentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginator,
        private DocumentService $documentService
    ) {}

    #[Route('/', name: 'admin_documents_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $queryBuilder = $this->entityManager
            ->getRepository(Document::class)
            ->createQueryBuilder('d')
            ->leftJoin('d.uploadedBy', 'u')
            ->leftJoin('d.request', 'r')
            ->addSelect('u', 'r')
            ->orderBy('d.createdAt', 'DESC');

        // Apply filters
        if ($search = $request->query->get('search')) {
            $queryBuilder->andWhere('d.name LIKE :search OR d.originalName LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        if ($status = $request->query->get('status')) {
            $queryBuilder->andWhere('d.status = :status')
                        ->setParameter('status', $status);
        }

        if ($documentType = $request->query->get('document_type')) {
            $queryBuilder->andWhere('d.documentType = :document_type')
                        ->setParameter('document_type', $documentType);
        }

        if ($dateFrom = $request->query->get('date_from')) {
            $queryBuilder->andWhere('d.createdAt >= :date_from')
                        ->setParameter('date_from', new \DateTime($dateFrom));
        }

        if ($dateTo = $request->query->get('date_to')) {
            $queryBuilder->andWhere('d.createdAt <= :date_to')
                        ->setParameter('date_to', new \DateTime($dateTo . ' 23:59:59'));
        }

        $pagination = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/documents/index.html.twig', [
            'documents' => $pagination,
            'page_title' => 'Documents Management',
            'active_menu' => 'documents'
        ]);
    }

    #[Route('/new', name: 'admin_documents_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();
            
            if ($uploadedFile) {
                $document = $this->documentService->uploadDocument(
                    $uploadedFile,
                    $this->getUser(),
                    $document->getDocumentType()
                );
                
                // Set other form data
                $document->setName($form->get('name')->getData());
                $document->setStatus($form->get('status')->getData());
                $document->setNotes($form->get('notes')->getData());
                $document->setRequest($form->get('request')->getData());
                
                $this->entityManager->persist($document);
                $this->entityManager->flush();

                $this->addFlash('success', 'Document uploaded successfully.');
                return $this->redirectToRoute('admin_documents_index');
            }
        }

        return $this->render('admin/documents/new.html.twig', [
            'document' => $document,
            'form' => $form,
            'page_title' => 'Upload Document',
            'active_menu' => 'documents'
        ]);
    }

    #[Route('/{id}', name: 'admin_documents_show', methods: ['GET'])]
    public function show(Document $document): Response
    {
        return $this->render('admin/documents/show.html.twig', [
            'document' => $document,
            'page_title' => 'Document Details',
            'active_menu' => 'documents'
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_documents_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Document $document): Response
    {
        $form = $this->createForm(DocumentType::class, $document, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $document->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->addFlash('success', 'Document updated successfully.');
            return $this->redirectToRoute('admin_documents_index');
        }

        return $this->render('admin/documents/edit.html.twig', [
            'document' => $document,
            'form' => $form,
            'page_title' => 'Edit Document',
            'active_menu' => 'documents'
        ]);
    }

    #[Route('/{id}/download', name: 'admin_documents_download', methods: ['GET'])]
    public function download(Document $document): BinaryFileResponse
    {
        $filePath = $this->documentService->getDocumentPath($document);
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found.');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $document->getOriginalName()
        );

        return $response;
    }

    #[Route('/{id}/verify', name: 'admin_documents_verify', methods: ['POST'])]
    public function verify(Document $document): JsonResponse
    {
        $this->documentService->validateDocument($document, $this->getUser());
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Document verified successfully'
        ]);
    }

    #[Route('/{id}/reject', name: 'admin_documents_reject', methods: ['POST'])]
    public function reject(Request $request, Document $document): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? '';

        $this->documentService->rejectDocument($document, $this->getUser(), $reason);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Document rejected successfully'
        ]);
    }

    #[Route('/{id}', name: 'admin_documents_delete', methods: ['POST'])]
    public function delete(Request $request, Document $document): Response
    {
        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->request->get('_token'))) {
            $this->documentService->deleteDocument($document);
            $this->entityManager->remove($document);
            $this->entityManager->flush();
            $this->addFlash('success', 'Document deleted successfully.');
        }

        return $this->redirectToRoute('admin_documents_index');
    }

    #[Route('/api/bulk-action', name: 'admin_documents_bulk_action', methods: ['POST'])]
    public function bulkAction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? '';
        $ids = $data['ids'] ?? [];

        if (empty($ids) || empty($action)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid parameters']);
        }

        $documents = $this->entityManager
            ->getRepository(Document::class)
            ->findBy(['id' => $ids]);

        switch ($action) {
            case 'verify':
                foreach ($documents as $document) {
                    $this->documentService->validateDocument($document, $this->getUser());
                }
                break;
            case 'reject':
                foreach ($documents as $document) {
                    $this->documentService->rejectDocument($document, $this->getUser(), 'Bulk rejection');
                }
                break;
            case 'delete':
                foreach ($documents as $document) {
                    $this->documentService->deleteDocument($document);
                    $this->entityManager->remove($document);
                }
                break;
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('%d documents processed successfully', count($documents))
        ]);
    }
}