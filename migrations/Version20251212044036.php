<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212044036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4208F64F1');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4CB944F1A');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4CDF80196');
        $this->addSql('ALTER TABLE course_category DROP FOREIGN KEY FK_AFF8749712469DE2');
        $this->addSql('ALTER TABLE course_category DROP FOREIGN KEY FK_AFF87497591CC992');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D613FECDF');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E1591CC992');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E1CB944F1A');
        $this->addSql('DROP TABLE session');
        $this->addSql('DROP TABLE course_category');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE enrollment');
        $this->addSql('ALTER TABLE activity_log CHANGE action action VARCHAR(50) NOT NULL, CHANGE target_type target_type VARCHAR(100) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX UNIQ_64C19C15E237E06 ON category');
        $this->addSql('ALTER TABLE category ADD description LONGTEXT DEFAULT NULL, ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE course DROP enrollment_capacity, CHANGE created_by_id created_by_id INT NOT NULL, CHANGE title title VARCHAR(255) NOT NULL, CHANGE level level VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE image_file image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE lessons DROP FOREIGN KEY FK_3F4218D9591CC992');
        $this->addSql('ALTER TABLE lessons ADD scheduled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP video_url, DROP material_file, DROP lesson_order, CHANGE course_id course_id INT DEFAULT NULL, CHANGE title title VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE lessons ADD CONSTRAINT FK_3F4218D9591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33208F64F1');
        $this->addSql('DROP INDEX IDX_B723AF33208F64F1 ON student');
        $this->addSql('ALTER TABLE student DROP tutor_id, DROP parent_name, DROP parent_email, DROP status, CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE last_name last_name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE grade grade VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE image_file image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE tutor DROP status, CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE last_name last_name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE specialty specialty VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE image_file image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE last_name last_name VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE session (id INT AUTO_INCREMENT NOT NULL, student_id INT NOT NULL, tutor_id INT NOT NULL, lesson_id INT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_D044D5D4CB944F1A (student_id), INDEX IDX_D044D5D4208F64F1 (tutor_id), INDEX IDX_D044D5D4CDF80196 (lesson_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE course_category (course_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_AFF87497591CC992 (course_id), INDEX IDX_AFF8749712469DE2 (category_id), PRIMARY KEY(course_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, amount NUMERIC(8, 2) NOT NULL, status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, paid_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_6D28840D613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE enrollment (id INT AUTO_INCREMENT NOT NULL, student_id INT NOT NULL, course_id INT NOT NULL, status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, enrolled_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_DBDCD7E1CB944F1A (student_id), INDEX IDX_DBDCD7E1591CC992 (course_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4208F64F1 FOREIGN KEY (tutor_id) REFERENCES tutor (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4CB944F1A FOREIGN KEY (student_id) REFERENCES student (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4CDF80196 FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE course_category ADD CONSTRAINT FK_AFF8749712469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_category ADD CONSTRAINT FK_AFF87497591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E1591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E1CB944F1A FOREIGN KEY (student_id) REFERENCES student (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE student ADD tutor_id INT DEFAULT NULL, ADD parent_name VARCHAR(100) DEFAULT NULL, ADD parent_email VARCHAR(180) DEFAULT NULL, ADD status VARCHAR(20) DEFAULT NULL, CHANGE first_name first_name VARCHAR(100) NOT NULL, CHANGE last_name last_name VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE grade grade VARCHAR(20) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE image image_file VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33208F64F1 FOREIGN KEY (tutor_id) REFERENCES tutor (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_B723AF33208F64F1 ON student (tutor_id)');
        $this->addSql('ALTER TABLE lessons DROP FOREIGN KEY FK_3F4218D9591CC992');
        $this->addSql('ALTER TABLE lessons ADD video_url VARCHAR(255) DEFAULT NULL, ADD material_file VARCHAR(255) DEFAULT NULL, ADD lesson_order INT DEFAULT NULL, DROP scheduled_at, CHANGE course_id course_id INT NOT NULL, CHANGE title title VARCHAR(100) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE lessons ADD CONSTRAINT FK_3F4218D9591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course ADD enrollment_capacity INT DEFAULT NULL, CHANGE created_by_id created_by_id INT DEFAULT NULL, CHANGE title title VARCHAR(150) NOT NULL, CHANGE level level VARCHAR(30) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE image image_file VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE category DROP description, DROP created_at, DROP updated_at, CHANGE name name VARCHAR(100) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C15E237E06 ON category (name)');
        $this->addSql('ALTER TABLE `user` CHANGE first_name first_name VARCHAR(80) NOT NULL, CHANGE last_name last_name VARCHAR(80) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE tutor ADD status VARCHAR(100) DEFAULT NULL, CHANGE first_name first_name VARCHAR(100) NOT NULL, CHANGE last_name last_name VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE specialty specialty VARCHAR(100) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE image image_file VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE activity_log CHANGE action action VARCHAR(20) NOT NULL, CHANGE target_type target_type VARCHAR(50) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
    }
}
