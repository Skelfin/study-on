<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240822101608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_169e6fb96de44026');
        $this->addSql('DROP INDEX uniq_169e6fb95e237e06');
        $this->addSql('ALTER TABLE lesson RENAME COLUMN "order" TO position');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE lesson RENAME COLUMN position TO "order"');
        $this->addSql('CREATE UNIQUE INDEX uniq_169e6fb96de44026 ON course (description)');
        $this->addSql('CREATE UNIQUE INDEX uniq_169e6fb95e237e06 ON course (name)');
    }
}
