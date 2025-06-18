<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250610111738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE permission ADD granted_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE permission ADD CONSTRAINT FK_E04992AA3151C11F FOREIGN KEY (granted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_E04992AA3151C11F ON permission (granted_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE permission DROP FOREIGN KEY FK_E04992AA3151C11F');
        $this->addSql('DROP INDEX IDX_E04992AA3151C11F ON permission');
        $this->addSql('ALTER TABLE permission DROP granted_by_id');
    }
}
