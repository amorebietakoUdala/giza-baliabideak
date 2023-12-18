<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230320074435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, app_owners_emails VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE application_role (application_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_A085E2E23E030ACD (application_id), INDEX IDX_A085E2E2D60322AC (role_id), PRIMARY KEY(application_id, role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE department (id INT AUTO_INCREMENT NOT NULL, name_es VARCHAR(255) NOT NULL, name_eu VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE historic (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, operation VARCHAR(255) NOT NULL, details VARCHAR(4096) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_AD52EF56A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job (id INT AUTO_INCREMENT NOT NULL, code INT NOT NULL, title_es VARCHAR(255) NOT NULL, title_eu VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_user (job_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_A5FA008BE04EA9 (job_id), INDEX IDX_A5FA008A76ED395 (user_id), PRIMARY KEY(job_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_permission (id INT AUTO_INCREMENT NOT NULL, job_id INT NOT NULL, application_id INT DEFAULT NULL, sub_application_id INT DEFAULT NULL, INDEX IDX_8A3E02B7BE04EA9 (job_id), INDEX IDX_8A3E02B73E030ACD (application_id), INDEX IDX_8A3E02B75FA758D9 (sub_application_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_permission_role (job_permission_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_ED3B5EA555075206 (job_permission_id), INDEX IDX_ED3B5EA5D60322AC (role_id), PRIMARY KEY(job_permission_id, role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE permission (id INT AUTO_INCREMENT NOT NULL, worker_id INT NOT NULL, application_id INT DEFAULT NULL, sub_application_id INT DEFAULT NULL, INDEX IDX_E04992AA6B20BA36 (worker_id), INDEX IDX_E04992AA3E030ACD (application_id), INDEX IDX_E04992AA5FA758D9 (sub_application_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE permission_role (permission_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_6A711CAFED90CCA (permission_id), INDEX IDX_6A711CAD60322AC (role_id), PRIMARY KEY(permission_id, role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name_es VARCHAR(255) NOT NULL, name_eu VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sub_application (id INT AUTO_INCREMENT NOT NULL, application_id INT DEFAULT NULL, name_es VARCHAR(255) NOT NULL, name_eu VARCHAR(255) NOT NULL, INDEX IDX_41A2A85F3E030ACD (application_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, activated TINYINT(1) DEFAULT 1 NOT NULL, last_login DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE worker (id INT AUTO_INCREMENT NOT NULL, department_id INT NOT NULL, job_id INT DEFAULT NULL, validated_by_id INT DEFAULT NULL, dni VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, surname1 VARCHAR(255) NOT NULL, surname2 VARCHAR(255) NOT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, expedient_number VARCHAR(255) DEFAULT NULL, status INT DEFAULT NULL, no_end_date TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_9FB2BF62AE80F5DF (department_id), INDEX IDX_9FB2BF62BE04EA9 (job_id), INDEX IDX_9FB2BF62C69DE5E5 (validated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE application_role ADD CONSTRAINT FK_A085E2E23E030ACD FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application_role ADD CONSTRAINT FK_A085E2E2D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE historic ADD CONSTRAINT FK_AD52EF56A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE job_user ADD CONSTRAINT FK_A5FA008BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_user ADD CONSTRAINT FK_A5FA008A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_permission ADD CONSTRAINT FK_8A3E02B7BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id)');
        $this->addSql('ALTER TABLE job_permission ADD CONSTRAINT FK_8A3E02B73E030ACD FOREIGN KEY (application_id) REFERENCES application (id)');
        $this->addSql('ALTER TABLE job_permission ADD CONSTRAINT FK_8A3E02B75FA758D9 FOREIGN KEY (sub_application_id) REFERENCES sub_application (id)');
        $this->addSql('ALTER TABLE job_permission_role ADD CONSTRAINT FK_ED3B5EA555075206 FOREIGN KEY (job_permission_id) REFERENCES job_permission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_permission_role ADD CONSTRAINT FK_ED3B5EA5D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE permission ADD CONSTRAINT FK_E04992AA6B20BA36 FOREIGN KEY (worker_id) REFERENCES worker (id)');
        $this->addSql('ALTER TABLE permission ADD CONSTRAINT FK_E04992AA3E030ACD FOREIGN KEY (application_id) REFERENCES application (id)');
        $this->addSql('ALTER TABLE permission ADD CONSTRAINT FK_E04992AA5FA758D9 FOREIGN KEY (sub_application_id) REFERENCES sub_application (id)');
        $this->addSql('ALTER TABLE permission_role ADD CONSTRAINT FK_6A711CAFED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE permission_role ADD CONSTRAINT FK_6A711CAD60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sub_application ADD CONSTRAINT FK_41A2A85F3E030ACD FOREIGN KEY (application_id) REFERENCES application (id)');
        $this->addSql('ALTER TABLE worker ADD CONSTRAINT FK_9FB2BF62AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id)');
        $this->addSql('ALTER TABLE worker ADD CONSTRAINT FK_9FB2BF62BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id)');
        $this->addSql('ALTER TABLE worker ADD CONSTRAINT FK_9FB2BF62C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application_role DROP FOREIGN KEY FK_A085E2E23E030ACD');
        $this->addSql('ALTER TABLE application_role DROP FOREIGN KEY FK_A085E2E2D60322AC');
        $this->addSql('ALTER TABLE historic DROP FOREIGN KEY FK_AD52EF56A76ED395');
        $this->addSql('ALTER TABLE job_user DROP FOREIGN KEY FK_A5FA008BE04EA9');
        $this->addSql('ALTER TABLE job_user DROP FOREIGN KEY FK_A5FA008A76ED395');
        $this->addSql('ALTER TABLE job_permission DROP FOREIGN KEY FK_8A3E02B7BE04EA9');
        $this->addSql('ALTER TABLE job_permission DROP FOREIGN KEY FK_8A3E02B73E030ACD');
        $this->addSql('ALTER TABLE job_permission DROP FOREIGN KEY FK_8A3E02B75FA758D9');
        $this->addSql('ALTER TABLE job_permission_role DROP FOREIGN KEY FK_ED3B5EA555075206');
        $this->addSql('ALTER TABLE job_permission_role DROP FOREIGN KEY FK_ED3B5EA5D60322AC');
        $this->addSql('ALTER TABLE permission DROP FOREIGN KEY FK_E04992AA6B20BA36');
        $this->addSql('ALTER TABLE permission DROP FOREIGN KEY FK_E04992AA3E030ACD');
        $this->addSql('ALTER TABLE permission DROP FOREIGN KEY FK_E04992AA5FA758D9');
        $this->addSql('ALTER TABLE permission_role DROP FOREIGN KEY FK_6A711CAFED90CCA');
        $this->addSql('ALTER TABLE permission_role DROP FOREIGN KEY FK_6A711CAD60322AC');
        $this->addSql('ALTER TABLE sub_application DROP FOREIGN KEY FK_41A2A85F3E030ACD');
        $this->addSql('ALTER TABLE worker DROP FOREIGN KEY FK_9FB2BF62AE80F5DF');
        $this->addSql('ALTER TABLE worker DROP FOREIGN KEY FK_9FB2BF62BE04EA9');
        $this->addSql('ALTER TABLE worker DROP FOREIGN KEY FK_9FB2BF62C69DE5E5');
        $this->addSql('DROP TABLE application');
        $this->addSql('DROP TABLE application_role');
        $this->addSql('DROP TABLE department');
        $this->addSql('DROP TABLE historic');
        $this->addSql('DROP TABLE job');
        $this->addSql('DROP TABLE job_user');
        $this->addSql('DROP TABLE job_permission');
        $this->addSql('DROP TABLE job_permission_role');
        $this->addSql('DROP TABLE permission');
        $this->addSql('DROP TABLE permission_role');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE sub_application');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE worker');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
