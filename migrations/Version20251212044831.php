<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212044831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix missing columns: image in course/tutor/student, description in category, scheduled_at in lessons';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->connection;
        $dbName = $connection->getDatabase();

        // Helper function to check if column exists
        $columnExists = function($table, $column) use ($connection, $dbName) {
            $sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?";
            $result = $connection->fetchAssociative($sql, [$dbName, $table, $column]);
            return (int)$result['cnt'] > 0;
        };

        // Fix course table
        if ($columnExists('course', 'image_file')) {
            $this->addSql('ALTER TABLE course CHANGE image_file image VARCHAR(255) DEFAULT NULL');
        } elseif (!$columnExists('course', 'image')) {
            $this->addSql('ALTER TABLE course ADD image VARCHAR(255) DEFAULT NULL');
        }

        // Fix tutor table
        if ($columnExists('tutor', 'image_file')) {
            $this->addSql('ALTER TABLE tutor CHANGE image_file image VARCHAR(255) DEFAULT NULL');
        } elseif (!$columnExists('tutor', 'image')) {
            $this->addSql('ALTER TABLE tutor ADD image VARCHAR(255) DEFAULT NULL');
        }

        // Fix student table
        if ($columnExists('student', 'image_file')) {
            $this->addSql('ALTER TABLE student CHANGE image_file image VARCHAR(255) DEFAULT NULL');
        } elseif (!$columnExists('student', 'image')) {
            $this->addSql('ALTER TABLE student ADD image VARCHAR(255) DEFAULT NULL');
        }

        // Add description to category if it doesn't exist
        if (!$columnExists('category', 'description')) {
            $this->addSql('ALTER TABLE category ADD description LONGTEXT DEFAULT NULL');
        }

        // Add scheduled_at to lessons if it doesn't exist
        if (!$columnExists('lessons', 'scheduled_at')) {
            $this->addSql("ALTER TABLE lessons ADD scheduled_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }
    }

    public function down(Schema $schema): void
    {
        // Revert changes if needed
        $this->addSql('ALTER TABLE course CHANGE image image_file VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE tutor CHANGE image image_file VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE student CHANGE image image_file VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE category DROP description');
        $this->addSql('ALTER TABLE lessons DROP scheduled_at');
    }
}
