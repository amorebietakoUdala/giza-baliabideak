<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241210134304 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE worker_job DROP INDEX FK_5FDD3D486B20BA36, ADD UNIQUE INDEX UNIQ_5FDD3D486B20BA36 (worker_id)');
        $this->addSql('ALTER TABLE worker_job RENAME INDEX fk_5fdd3d48be04ea9 TO IDX_5FDD3D48BE04EA9');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE available_at available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE worker_job DROP INDEX UNIQ_5FDD3D486B20BA36, ADD INDEX FK_5FDD3D486B20BA36 (worker_id)');
        $this->addSql('ALTER TABLE worker_job RENAME INDEX idx_5fdd3d48be04ea9 TO FK_5FDD3D48BE04EA9');
    }
}
