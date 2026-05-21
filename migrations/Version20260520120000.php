<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link students to parent user accounts for mobile family monitoring';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE student ADD parent_user_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE student ADD CONSTRAINT FK_B723AF33_ParentUser FOREIGN KEY (parent_user_id) REFERENCES `user` (id) ON DELETE SET NULL'
        );
        $this->addSql('CREATE INDEX IDX_B723AF33_ParentUser ON student (parent_user_id)');
        $this->addSql('ALTER TABLE student MODIFY email VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33_ParentUser');
        $this->addSql('DROP INDEX IDX_B723AF33_ParentUser ON student');
        $this->addSql('ALTER TABLE student DROP parent_user_id');
        $this->addSql('ALTER TABLE student MODIFY email VARCHAR(255) NOT NULL');
    }
}
