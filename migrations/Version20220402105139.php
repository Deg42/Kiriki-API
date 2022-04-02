<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220402105139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, host_id INT NOT NULL, winner_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, date DATE NOT NULL, INDEX IDX_232B318C1FB8D185 (host_id), INDEX IDX_232B318C5DFCD4B8 (winner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE player (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, reg_date DATE NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE player_game (id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, player_id INT NOT NULL, turn_order INT NOT NULL, is_turn TINYINT(1) NOT NULL, is_last_accepted TINYINT(1) DEFAULT NULL, points INT NOT NULL, UNIQUE INDEX UNIQ_813161BFE48FD905 (game_id), UNIQUE INDEX UNIQ_813161BF99E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C1FB8D185 FOREIGN KEY (host_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE player_game ADD CONSTRAINT FK_813161BFE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE player_game ADD CONSTRAINT FK_813161BF99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player_game DROP FOREIGN KEY FK_813161BFE48FD905');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C1FB8D185');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C5DFCD4B8');
        $this->addSql('ALTER TABLE player_game DROP FOREIGN KEY FK_813161BF99E6F5DF');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE player_game');
    }
}
