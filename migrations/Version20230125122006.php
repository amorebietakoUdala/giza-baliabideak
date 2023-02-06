<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230125122006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE application_historic (application_id INT NOT NULL, historic_id INT NOT NULL, INDEX IDX_126930453E030ACD (application_id), INDEX IDX_1269304552F34864 (historic_id), PRIMARY KEY(application_id, historic_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE department (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE historic (id INT AUTO_INCREMENT NOT NULL, department_id INT DEFAULT NULL, job_id INT DEFAULT NULL, user_id INT NOT NULL, dni VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, surname1 VARCHAR(255) NOT NULL, surname2 VARCHAR(255) NOT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, expedient_number VARCHAR(255) DEFAULT NULL, status INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_AD52EF56AE80F5DF (department_id), INDEX IDX_AD52EF56BE04EA9 (job_id), INDEX IDX_AD52EF56A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job (id INT AUTO_INCREMENT NOT NULL, code INT NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_application (job_id INT NOT NULL, application_id INT NOT NULL, INDEX IDX_C737C688BE04EA9 (job_id), INDEX IDX_C737C6883E030ACD (application_id), PRIMARY KEY(job_id, application_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_user (job_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_A5FA008BE04EA9 (job_id), INDEX IDX_A5FA008A76ED395 (user_id), PRIMARY KEY(job_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, activated TINYINT(1) DEFAULT 1 NOT NULL, last_login DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE worker (id INT AUTO_INCREMENT NOT NULL, department_id INT NOT NULL, job_id INT DEFAULT NULL, dni VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, surname1 VARCHAR(255) NOT NULL, surname2 VARCHAR(255) NOT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, expedient_number VARCHAR(255) DEFAULT NULL, status INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_9FB2BF62AE80F5DF (department_id), INDEX IDX_9FB2BF62BE04EA9 (job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE worker_application (worker_id INT NOT NULL, application_id INT NOT NULL, INDEX IDX_6DD3C1066B20BA36 (worker_id), INDEX IDX_6DD3C1063E030ACD (application_id), PRIMARY KEY(worker_id, application_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE application_historic ADD CONSTRAINT FK_126930453E030ACD FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application_historic ADD CONSTRAINT FK_1269304552F34864 FOREIGN KEY (historic_id) REFERENCES historic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE historic ADD CONSTRAINT FK_AD52EF56AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id)');
        $this->addSql('ALTER TABLE historic ADD CONSTRAINT FK_AD52EF56BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id)');
        $this->addSql('ALTER TABLE historic ADD CONSTRAINT FK_AD52EF56A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C688BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C6883E030ACD FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_user ADD CONSTRAINT FK_A5FA008BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_user ADD CONSTRAINT FK_A5FA008A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE worker ADD CONSTRAINT FK_9FB2BF62AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id)');
        $this->addSql('ALTER TABLE worker ADD CONSTRAINT FK_9FB2BF62BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id)');
        $this->addSql('ALTER TABLE worker_application ADD CONSTRAINT FK_6DD3C1066B20BA36 FOREIGN KEY (worker_id) REFERENCES worker (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE worker_application ADD CONSTRAINT FK_6DD3C1063E030ACD FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application_historic DROP FOREIGN KEY FK_126930453E030ACD');
        $this->addSql('ALTER TABLE application_historic DROP FOREIGN KEY FK_1269304552F34864');
        $this->addSql('ALTER TABLE historic DROP FOREIGN KEY FK_AD52EF56AE80F5DF');
        $this->addSql('ALTER TABLE historic DROP FOREIGN KEY FK_AD52EF56BE04EA9');
        $this->addSql('ALTER TABLE historic DROP FOREIGN KEY FK_AD52EF56A76ED395');
        $this->addSql('ALTER TABLE job_application DROP FOREIGN KEY FK_C737C688BE04EA9');
        $this->addSql('ALTER TABLE job_application DROP FOREIGN KEY FK_C737C6883E030ACD');
        $this->addSql('ALTER TABLE job_user DROP FOREIGN KEY FK_A5FA008BE04EA9');
        $this->addSql('ALTER TABLE job_user DROP FOREIGN KEY FK_A5FA008A76ED395');
        $this->addSql('ALTER TABLE worker DROP FOREIGN KEY FK_9FB2BF62AE80F5DF');
        $this->addSql('ALTER TABLE worker DROP FOREIGN KEY FK_9FB2BF62BE04EA9');
        $this->addSql('ALTER TABLE worker_application DROP FOREIGN KEY FK_6DD3C1066B20BA36');
        $this->addSql('ALTER TABLE worker_application DROP FOREIGN KEY FK_6DD3C1063E030ACD');
        $this->addSql('DROP TABLE application');
        $this->addSql('DROP TABLE application_historic');
        $this->addSql('DROP TABLE department');
        $this->addSql('DROP TABLE historic');
        $this->addSql('DROP TABLE job');
        $this->addSql('DROP TABLE job_application');
        $this->addSql('DROP TABLE job_user');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE worker');
        $this->addSql('DROP TABLE worker_application');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
