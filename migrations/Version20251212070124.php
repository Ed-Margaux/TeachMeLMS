<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212070124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `message` (id INT AUTO_INCREMENT NOT NULL, sender_id INT NOT NULL, recipient_id INT NOT NULL, subject VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, is_read TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B6BD307FF624B39D (sender_id), INDEX IDX_B6BD307FE92F8F78 (recipient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `message` ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE `message` ADD CONSTRAINT FK_B6BD307FE92F8F78 FOREIGN KEY (recipient_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE course DROP enrollment_capacity, CHANGE created_by_id created_by_id INT NOT NULL, CHANGE title title VARCHAR(255) NOT NULL, CHANGE level level VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE lessons DROP FOREIGN KEY FK_3F4218D9591CC992');
        $this->addSql('ALTER TABLE lessons DROP video_url, DROP material_file, DROP lesson_order, CHANGE course_id course_id INT DEFAULT NULL, CHANGE title title VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE lessons ADD CONSTRAINT FK_3F4218D9591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33208F64F1');
        $this->addSql('DROP INDEX IDX_B723AF33208F64F1 ON student');
        $this->addSql('ALTER TABLE student DROP tutor_id, DROP parent_name, DROP parent_email, DROP status, CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE last_name last_name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE grade grade VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE tutor DROP status, CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE last_name last_name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE specialty specialty VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE last_name last_name VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `message` DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE `message` DROP FOREIGN KEY FK_B6BD307FE92F8F78');
        $this->addSql('DROP TABLE `message`');
        $this->addSql('ALTER TABLE tutor ADD status VARCHAR(100) DEFAULT NULL, CHANGE first_name first_name VARCHAR(100) NOT NULL, CHANGE last_name last_name VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE specialty specialty VARCHAR(100) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE lessons DROP FOREIGN KEY FK_3F4218D9591CC992');
        $this->addSql('ALTER TABLE lessons ADD video_url VARCHAR(255) DEFAULT NULL, ADD material_file VARCHAR(255) DEFAULT NULL, ADD lesson_order INT DEFAULT NULL, CHANGE course_id course_id INT NOT NULL, CHANGE title title VARCHAR(100) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE lessons ADD CONSTRAINT FK_3F4218D9591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course ADD enrollment_capacity INT DEFAULT NULL, CHANGE created_by_id created_by_id INT DEFAULT NULL, CHANGE title title VARCHAR(150) NOT NULL, CHANGE level level VARCHAR(30) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE student ADD tutor_id INT DEFAULT NULL, ADD parent_name VARCHAR(100) DEFAULT NULL, ADD parent_email VARCHAR(180) DEFAULT NULL, ADD status VARCHAR(20) DEFAULT NULL, CHANGE first_name first_name VARCHAR(100) NOT NULL, CHANGE last_name last_name VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE grade grade VARCHAR(20) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33208F64F1 FOREIGN KEY (tutor_id) REFERENCES tutor (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_B723AF33208F64F1 ON student (tutor_id)');
        $this->addSql('ALTER TABLE `user` CHANGE first_name first_name VARCHAR(80) NOT NULL, CHANGE last_name last_name VARCHAR(80) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
    }
}
