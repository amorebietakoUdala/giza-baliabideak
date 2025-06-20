<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250611111543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE application_user (application_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_7A7FBEC13E030ACD (application_id), INDEX IDX_7A7FBEC1A76ED395 (user_id), PRIMARY KEY(application_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE application_user ADD CONSTRAINT FK_7A7FBEC13E030ACD FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application_user ADD CONSTRAINT FK_7A7FBEC1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application_user DROP FOREIGN KEY FK_7A7FBEC13E030ACD');
        $this->addSql('ALTER TABLE application_user DROP FOREIGN KEY FK_7A7FBEC1A76ED395');
        $this->addSql('DROP TABLE application_user');
    }
}
