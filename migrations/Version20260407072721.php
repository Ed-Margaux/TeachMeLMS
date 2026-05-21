<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407072721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tutor RENAME INDEX idx_122b405b03a8386 TO IDX_99074648B03A8386');
        $this->addSql('ALTER TABLE user CHANGE status status VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` CHANGE status status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE tutor RENAME INDEX idx_99074648b03a8386 TO IDX_122B405B03A8386');
    }
}
