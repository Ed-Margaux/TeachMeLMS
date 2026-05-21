<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Model C: enrollment requests and class_session schedule for parent mobile';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE enrollment (
                id INT AUTO_INCREMENT NOT NULL,
                student_id INT NOT NULL,
                course_id INT NOT NULL,
                tutor_id INT DEFAULT NULL,
                status VARCHAR(20) NOT NULL,
                parent_note LONGTEXT DEFAULT NULL,
                staff_note LONGTEXT DEFAULT NULL,
                requested_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                approved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                approved_by_id INT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_enrollment_student (student_id),
                INDEX IDX_enrollment_course (course_id),
                INDEX IDX_enrollment_tutor (tutor_id),
                INDEX IDX_enrollment_status (status),
                INDEX IDX_enrollment_approved_by (approved_by_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_enrollment_student FOREIGN KEY (student_id) REFERENCES student (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_enrollment_course FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_enrollment_tutor FOREIGN KEY (tutor_id) REFERENCES tutor (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_enrollment_approved_by FOREIGN KEY (approved_by_id) REFERENCES `user` (id) ON DELETE SET NULL');

        $this->addSql(<<<'SQL'
            CREATE TABLE class_session (
                id INT AUTO_INCREMENT NOT NULL,
                enrollment_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                scheduled_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                duration_minutes INT NOT NULL DEFAULT 45,
                meeting_url VARCHAR(500) DEFAULT NULL,
                status VARCHAR(20) NOT NULL,
                created_by_id INT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_class_session_enrollment (enrollment_id),
                INDEX IDX_class_session_scheduled (scheduled_at),
                INDEX IDX_class_session_status (status),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        $this->addSql('ALTER TABLE class_session ADD CONSTRAINT FK_class_session_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE class_session ADD CONSTRAINT FK_class_session_created_by FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE class_session DROP FOREIGN KEY FK_class_session_enrollment');
        $this->addSql('ALTER TABLE class_session DROP FOREIGN KEY FK_class_session_created_by');
        $this->addSql('DROP TABLE class_session');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_enrollment_student');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_enrollment_course');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_enrollment_tutor');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_enrollment_approved_by');
        $this->addSql('DROP TABLE enrollment');
    }
}
