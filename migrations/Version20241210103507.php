<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241210103507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE worker_job (id INT AUTO_INCREMENT NOT NULL, worker_id INT NOT NULL, job_id INT NOT NULL, code INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE worker_job ADD CONSTRAINT FK_5FDD3D486B20BA36 FOREIGN KEY (worker_id) REFERENCES worker (id)');
        $this->addSql('ALTER TABLE worker_job ADD CONSTRAINT FK_5FDD3D48BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id)');
        // Migrate data
        $this->addSql('INSERT INTO worker_job (worker_id, job_id, code) SELECT w.id, j.id, j.code from worker w join job j on w.job_id = j.id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE worker_job DROP FOREIGN KEY FK_5FDD3D486B20BA36');
        $this->addSql('ALTER TABLE worker_job DROP FOREIGN KEY FK_5FDD3D48BE04EA9');
        $this->addSql('DROP TABLE worker_job');
    }
}
