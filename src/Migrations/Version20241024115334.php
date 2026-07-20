<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241024115334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creating table for plugin settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE furgonetka_settings (name VARCHAR(255) NOT NULL, value LONGTEXT NOT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE furgonetka_settings');
    }
}
