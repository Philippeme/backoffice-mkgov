<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * MK Gov Database Schema - Initial Migration
 * Creates all necessary tables for the MK Gov backoffice application
 */
final class Version20250129000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial MK Gov database schema creation';
    }

    public function up(Schema $schema): void
    {
        // Users table
        $this->addSql('CREATE TABLE users (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL UNIQUE,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            phone_number VARCHAR(20) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            must_change_password TINYINT(1) NOT NULL DEFAULT 0,
            permissions JSON NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            last_login_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            INDEX IDX_users_email (email),
            INDEX IDX_users_is_active (is_active)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Public Entities table
        $this->addSql('CREATE TABLE public_entities (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(100) NOT NULL,
            description TEXT DEFAULT NULL,
            address VARCHAR(255) DEFAULT NULL,
            phone_number VARCHAR(20) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            website VARCHAR(255) DEFAULT NULL,
            region VARCHAR(100) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            services JSON NOT NULL,
            operating_hours JSON NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            INDEX IDX_public_entities_region (region),
            INDEX IDX_public_entities_type (type),
            INDEX IDX_public_entities_is_active (is_active)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Procedures table
        $this->addSql('CREATE TABLE procedures (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT \'draft\',
            category VARCHAR(100) NOT NULL,
            workflow_steps JSON NOT NULL,
            required_documents JSON NOT NULL,
            estimated_duration INT NOT NULL DEFAULT 0,
            cost NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) NOT NULL DEFAULT \'XAF\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            INDEX IDX_procedures_category (category),
            INDEX IDX_procedures_status (status),
            INDEX IDX_procedures_created_at (created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Requests table
        $this->addSql('CREATE TABLE requests (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            procedure_id INT DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT \'pending\',
            service_family VARCHAR(100) NOT NULL,
            tracking_number VARCHAR(20) NOT NULL UNIQUE,
            request_data JSON NOT NULL,
            timeline JSON NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            INDEX IDX_requests_user_id (user_id),
            INDEX IDX_requests_procedure_id (procedure_id),
            INDEX IDX_requests_status (status),
            INDEX IDX_requests_service_family (service_family),
            INDEX IDX_requests_tracking_number (tracking_number),
            INDEX IDX_requests_created_at (created_at),
            CONSTRAINT FK_requests_user_id FOREIGN KEY (user_id) REFERENCES users (id),
            CONSTRAINT FK_requests_procedure_id FOREIGN KEY (procedure_id) REFERENCES procedures (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Documents table
        $this->addSql('CREATE TABLE documents (
            id INT AUTO_INCREMENT NOT NULL,
            request_id INT DEFAULT NULL,
            uploaded_by_id INT NOT NULL,
            verified_by_id INT DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size INT NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT \'pending\',
            document_type VARCHAR(100) NOT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            INDEX IDX_documents_request_id (request_id),
            INDEX IDX_documents_uploaded_by_id (uploaded_by_id),
            INDEX IDX_documents_verified_by_id (verified_by_id),
            INDEX IDX_documents_status (status),
            INDEX IDX_documents_document_type (document_type),
            INDEX IDX_documents_created_at (created_at),
            CONSTRAINT FK_documents_request_id FOREIGN KEY (request_id) REFERENCES requests (id),
            CONSTRAINT FK_documents_uploaded_by_id FOREIGN KEY (uploaded_by_id) REFERENCES users (id),
            CONSTRAINT FK_documents_verified_by_id FOREIGN KEY (verified_by_id) REFERENCES users (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // System Logs table
        $this->addSql('CREATE TABLE system_logs (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            level VARCHAR(50) NOT NULL,
            category VARCHAR(100) NOT NULL,
            message VARCHAR(255) NOT NULL,
            context JSON NOT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            INDEX IDX_system_logs_user_id (user_id),
            INDEX IDX_system_logs_level (level),
            INDEX IDX_system_logs_category (category),
            INDEX IDX_system_logs_created_at (created_at),
            CONSTRAINT FK_system_logs_user_id FOREIGN KEY (user_id) REFERENCES users (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // Drop tables in reverse order to respect foreign key constraints
        $this->addSql('DROP TABLE system_logs');
        $this->addSql('DROP TABLE documents');
        $this->addSql('DROP TABLE requests');
        $this->addSql('DROP TABLE procedures');
        $this->addSql('DROP TABLE public_entities');
        $this->addSql('DROP TABLE users');
    }
}