<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260612120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creating table for checkout pickup points';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE furgonetka_pickup_point (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, shipping_method_code VARCHAR(255) NOT NULL, point_code VARCHAR(100) NOT NULL, point_name VARCHAR(255) NOT NULL, point_type VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_FURGONETKA_PICKUP_POINT_ORDER (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE furgonetka_pickup_point ADD CONSTRAINT FK_FURGONETKA_PICKUP_POINT_ORDER FOREIGN KEY (order_id) REFERENCES sylius_order (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE furgonetka_pickup_point DROP FOREIGN KEY FK_FURGONETKA_PICKUP_POINT_ORDER');
        $this->addSql('DROP TABLE furgonetka_pickup_point');
    }
}
