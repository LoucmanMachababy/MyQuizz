<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250627141018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE question ADD CONSTRAINT FK_B6F7494EC9486A13 FOREIGN KEY (id_categorie) REFERENCES categorie (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_history ADD CONSTRAINT FK_35E0DDEBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_history ADD CONSTRAINT FK_35E0DDEBBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC7E62CA5DB FOREIGN KEY (id_question) REFERENCES question (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD points INT DEFAULT 0 NOT NULL, ADD quizzes_completed INT DEFAULT 0 NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE question DROP FOREIGN KEY FK_B6F7494EC9486A13
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_history DROP FOREIGN KEY FK_35E0DDEBA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_history DROP FOREIGN KEY FK_35E0DDEBBCF5E72D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC7E62CA5DB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP points, DROP quizzes_completed
        SQL);
    }
}
