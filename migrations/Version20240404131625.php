<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240404131625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist DROP FOREIGN KEY FK_1599687CA4E3B88');
        $this->addSql('DROP INDEX IDX_1599687CA4E3B88 ON artist');
        $this->addSql('ALTER TABLE artist DROP artist_has_label_id');
        $this->addSql('ALTER TABLE artist_has_label ADD id_artist_id INT NOT NULL, ADD id_label_id INT NOT NULL');
        $this->addSql('ALTER TABLE artist_has_label ADD CONSTRAINT FK_E9FA2BDE37A2B0DF FOREIGN KEY (id_artist_id) REFERENCES artist (id)');
        $this->addSql('ALTER TABLE artist_has_label ADD CONSTRAINT FK_E9FA2BDE6362C3AC FOREIGN KEY (id_label_id) REFERENCES label (id)');
        $this->addSql('CREATE INDEX IDX_E9FA2BDE37A2B0DF ON artist_has_label (id_artist_id)');
        $this->addSql('CREATE INDEX IDX_E9FA2BDE6362C3AC ON artist_has_label (id_label_id)');
        $this->addSql('ALTER TABLE label DROP FOREIGN KEY FK_EA750E8CA4E3B88');
        $this->addSql('DROP INDEX IDX_EA750E8CA4E3B88 ON label');
        $this->addSql('ALTER TABLE label DROP artist_has_label_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist ADD artist_has_label_id INT NOT NULL');
        $this->addSql('ALTER TABLE artist ADD CONSTRAINT FK_1599687CA4E3B88 FOREIGN KEY (artist_has_label_id) REFERENCES artist_has_label (id)');
        $this->addSql('CREATE INDEX IDX_1599687CA4E3B88 ON artist (artist_has_label_id)');
        $this->addSql('ALTER TABLE artist_has_label DROP FOREIGN KEY FK_E9FA2BDE37A2B0DF');
        $this->addSql('ALTER TABLE artist_has_label DROP FOREIGN KEY FK_E9FA2BDE6362C3AC');
        $this->addSql('DROP INDEX IDX_E9FA2BDE37A2B0DF ON artist_has_label');
        $this->addSql('DROP INDEX IDX_E9FA2BDE6362C3AC ON artist_has_label');
        $this->addSql('ALTER TABLE artist_has_label DROP id_artist_id, DROP id_label_id');
        $this->addSql('ALTER TABLE label ADD artist_has_label_id INT NOT NULL');
        $this->addSql('ALTER TABLE label ADD CONSTRAINT FK_EA750E8CA4E3B88 FOREIGN KEY (artist_has_label_id) REFERENCES artist_has_label (id)');
        $this->addSql('CREATE INDEX IDX_EA750E8CA4E3B88 ON label (artist_has_label_id)');
    }
}
