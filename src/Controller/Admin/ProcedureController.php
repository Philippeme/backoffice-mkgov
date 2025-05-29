<?php

namespace App\Controller\Admin;

use App\Entity\Procedure;
use App\Form\ProcedureType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
    
#[Route('/admin/procedures')]
class ProcedureController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginator
    ) {}

    #[Route('/', name: 'admin_procedures_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $queryBuilder = $this->entityManager
            ->getRepository(Procedure::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->orderBy('p.createdAt', 'DESC');

        // Apply filters
        if ($search = $request->query->get('search')) {
            $queryBuilder->andWhere('p.name LIKE :search OR p.description LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        if ($category = $request->query->get('category')) {
            $queryBuilder->andWhere('c.id = :category')
                        ->setParameter('category', $category);
        }

        if ($status = $request->query->get('status')) {
            $queryBuilder->andWhere('p.status = :status')
                        ->setParameter('status', $status);
        }

        $pagination = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/procedures/index.html.twig', [
            'procedures' => $pagination,
            'page_title' => 'Procedures Management',
            'active_menu' => 'procedures'
        ]);
    }

    #[Route('/new', name: 'admin_procedures_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $procedure = new Procedure();
        $form = $this->createForm(ProcedureType::class, $procedure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $procedure->setCreatedAt(new \DateTimeImmutable());
            $procedure->setUpdatedAt(new \DateTimeImmutable());
            
            $this->entityManager->persist($procedure);
            $this->entityManager->flush();

            $this->addFlash('success', 'Procedure created successfully.');
            return $this->redirectToRoute('admin_procedures_index');
        }

        return $this->render('admin/procedures/new.html.twig', [
            'procedure' => $procedure,
            'form' => $form,
            'page_title' => 'New Procedure',
            'active_menu' => 'procedures'
        ]);
    }

    #[Route('/{id}', name: 'admin_procedures_show', methods: ['GET'])]
    public function show(Procedure $procedure): Response
    {
        return $this->render('admin/procedures/show.html.twig', [
            'procedure' => $procedure,
            'page_title' => 'Procedure Details',
            'active_menu' => 'procedures'
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_procedures_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Procedure $procedure): Response
    {
        $form = $this->createForm(ProcedureType::class, $procedure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $procedure->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->addFlash('success', 'Procedure updated successfully.');
            return $this->redirectToRoute('admin_procedures_index');
        }

        return $this->render('admin/procedures/edit.html.twig', [
            'procedure' => $procedure,
            'form' => $form,
            'page_title' => 'Edit Procedure',
            'active_menu' => 'procedures'
        ]);
    }

    #[Route('/{id}', name: 'admin_procedures_delete', methods: ['POST'])]
    public function delete(Request $request, Procedure $procedure): Response
    {
        if ($this->isCsrfTokenValid('delete'.$procedure->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($procedure);
            $this->entityManager->flush();
            $this->addFlash('success', 'Procedure deleted successfully.');
        }

        return $this->redirectToRoute('admin_procedures_index');
    }

    #[Route('/{id}/workflow', name: 'admin_procedures_workflow', methods: ['GET', 'POST'])]
    public function manageWorkflow(Request $request, Procedure $procedure): Response
    {
        if ($request->isMethod('POST')) {
            $workflowData = json_decode($request->getContent(), true);
            $procedure->setWorkflowSteps($workflowData['steps']);
            $procedure->setUpdatedAt(new \DateTimeImmutable());
            
            $this->entityManager->flush();
            
            return new JsonResponse(['success' => true, 'message' => 'Workflow updated successfully']);
        }

        return $this->render('admin/procedures/workflow.html.twig', [
            'procedure' => $procedure,
            'page_title' => 'Manage Workflow - ' . $procedure->getName(),
            'active_menu' => 'procedures'
        ]);
    }

    #[Route('/api/validate-workflow', name: 'admin_procedures_validate_workflow', methods: ['POST'])]
    public function validateWorkflow(Request $request): JsonResponse
    {
        $workflowData = json_decode($request->getContent(), true);
        
        // Validate workflow logic
        $errors = $this->validateWorkflowData($workflowData);
        
        if (empty($errors)) {
            return new JsonResponse(['valid' => true]);
        }
        
        return new JsonResponse(['valid' => false, 'errors' => $errors]);
    }

    private function validateWorkflowData(array $workflowData): array
    {
        $errors = [];
        
        if (empty($workflowData['steps'])) {
            $errors[] = 'Workflow must have at least one step';
        }
        
        foreach ($workflowData['steps'] as $index => $step) {
            if (empty($step['name'])) {
                $errors[] = "Step " . ($index + 1) . " must have a name";
            }
            
            if (empty($step['type'])) {
                $errors[] = "Step " . ($index + 1) . " must have a type";
            }
            
            if (isset($step['conditions']) && !is_array($step['conditions'])) {
                $errors[] = "Step " . ($index + 1) . " conditions must be an array";
            }
        }
        
        return $errors;
    }

    #[Route('/{id}/duplicate', name: 'admin_procedures_duplicate', methods: ['POST'])]
    public function duplicate(Procedure $procedure): Response
    {
        $newProcedure = clone $procedure;
        $newProcedure->setName($procedure->getName() . ' (Copy)');
        $newProcedure->setCreatedAt(new \DateTimeImmutable());
        $newProcedure->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($newProcedure);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Procedure duplicated successfully.');
        return $this->redirectToRoute('admin_procedures_edit', ['id' => $newProcedure->getId()]);
    }
}