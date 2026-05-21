<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add parent_email on student to link web admin records to parent accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE student ADD parent_email VARCHAR(180) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_student_parent_email ON student (parent_email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_student_parent_email ON student');
        $this->addSql('ALTER TABLE student DROP parent_email');
    }
}
