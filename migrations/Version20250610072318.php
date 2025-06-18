<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250610072318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE historic ADD worker_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE historic ADD CONSTRAINT FK_AD52EF566B20BA36 FOREIGN KEY (worker_id) REFERENCES worker (id)');
        $this->addSql('CREATE INDEX IDX_AD52EF566B20BA36 ON historic (worker_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE historic DROP FOREIGN KEY FK_AD52EF566B20BA36');
        $this->addSql('DROP INDEX IDX_AD52EF566B20BA36 ON historic');
        $this->addSql('ALTER TABLE historic DROP worker_id');
    }
}
