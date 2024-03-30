<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240330180516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album ADD created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE artist ADD created_at DATETIME NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_159968791657DAE ON artist (fullname)');
        $this->addSql('ALTER TABLE song ADD created_at DATETIME NOT NULL, DROP create_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album DROP created_at');
        $this->addSql('DROP INDEX UNIQ_159968791657DAE ON artist');
        $this->addSql('ALTER TABLE artist DROP created_at');
        $this->addSql('ALTER TABLE song ADD create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP created_at');
    }
}
