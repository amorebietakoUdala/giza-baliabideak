<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241210130410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job DROP code');
        $this->addSql('ALTER TABLE worker DROP FOREIGN KEY FK_9FB2BF62BE04EA9');
        $this->addSql('DROP INDEX IDX_9FB2BF62BE04EA9 ON worker');
        $this->addSql('ALTER TABLE worker DROP job_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job ADD code INT NOT NULL');
        $this->addSql('ALTER TABLE worker ADD job_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE worker ADD CONSTRAINT FK_9FB2BF62BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_9FB2BF62BE04EA9 ON worker (job_id)');
    }
}
