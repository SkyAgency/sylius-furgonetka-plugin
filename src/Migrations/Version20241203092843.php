<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241203092843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creating table for plugin logs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE furgonetka_logs (id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', level VARCHAR(16) NOT NULL, data LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE furgonetka_logs');
    }
}
