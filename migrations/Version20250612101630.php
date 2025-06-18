<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250612101630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE department_permission (id INT AUTO_INCREMENT NOT NULL, department_id INT NOT NULL, application_id INT DEFAULT NULL, sub_application_id INT DEFAULT NULL, INDEX IDX_72409E46AE80F5DF (department_id), INDEX IDX_72409E463E030ACD (application_id), INDEX IDX_72409E465FA758D9 (sub_application_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE department_permission_role (department_permission_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_EDF357BB8B48D234 (department_permission_id), INDEX IDX_EDF357BBD60322AC (role_id), PRIMARY KEY(department_permission_id, role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE department_permission ADD CONSTRAINT FK_72409E46AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id)');
        $this->addSql('ALTER TABLE department_permission ADD CONSTRAINT FK_72409E463E030ACD FOREIGN KEY (application_id) REFERENCES application (id)');
        $this->addSql('ALTER TABLE department_permission ADD CONSTRAINT FK_72409E465FA758D9 FOREIGN KEY (sub_application_id) REFERENCES sub_application (id)');
        $this->addSql('ALTER TABLE department_permission_role ADD CONSTRAINT FK_EDF357BB8B48D234 FOREIGN KEY (department_permission_id) REFERENCES department_permission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE department_permission_role ADD CONSTRAINT FK_EDF357BBD60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE department_permission DROP FOREIGN KEY FK_72409E46AE80F5DF');
        $this->addSql('ALTER TABLE department_permission DROP FOREIGN KEY FK_72409E463E030ACD');
        $this->addSql('ALTER TABLE department_permission DROP FOREIGN KEY FK_72409E465FA758D9');
        $this->addSql('ALTER TABLE department_permission_role DROP FOREIGN KEY FK_EDF357BB8B48D234');
        $this->addSql('ALTER TABLE department_permission_role DROP FOREIGN KEY FK_EDF357BBD60322AC');
        $this->addSql('DROP TABLE department_permission');
        $this->addSql('DROP TABLE department_permission_role');
    }
}
