<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class DocumentService
{
    public function __construct(
        private SluggerInterface $slugger,
        private string $uploadsDirectory
    ) {}

    public function uploadDocument(UploadedFile $file, User $user, ?string $documentType = null): Document
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $file->move($this->uploadsDirectory, $fileName);

        $document = new Document();
        $document->setName($originalFilename);
        $document->setOriginalName($file->getClientOriginalName());
        $document->setFilePath($fileName);
        $document->setMimeType($file->getMimeType() ?? 'application/octet-stream');
        $document->setFileSize($file->getSize());
        $document->setDocumentType($documentType ?? 'other');
        $document->setUploadedBy($user);
        $document->setCreatedAt(new \DateTimeImmutable());
        $document->setUpdatedAt(new \DateTimeImmutable());

        return $document;
    }

    public function getDocumentPath(Document $document): string
    {
        return $this->uploadsDirectory . '/' . $document->getFilePath();
    }

    public function deleteDocument(Document $document): bool
    {
        $filePath = $this->getDocumentPath($document);
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true; // File doesn't exist, consider it deleted
    }

    public function validateDocument(Document $document, User $validator): void
    {
        $document->setStatus('verified');
        $document->setVerifiedAt(new \DateTimeImmutable());
        $document->setVerifiedBy($validator);
        $document->setUpdatedAt(new \DateTimeImmutable());
    }

    public function rejectDocument(Document $document, User $validator, string $reason = ''): void
    {
        $document->setStatus('rejected');
        $document->setNotes($reason);
        $document->setVerifiedAt(new \DateTimeImmutable());
        $document->setVerifiedBy($validator);
        $document->setUpdatedAt(new \DateTimeImmutable());
    }
}