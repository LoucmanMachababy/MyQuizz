<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250627130200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE quiz_history (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, categorie_id INT NOT NULL, score INT NOT NULL, total_questions INT NOT NULL, completed_at DATETIME NOT NULL, user_ip VARCHAR(45) DEFAULT NULL, INDEX IDX_35E0DDEBA76ED395 (user_id), INDEX IDX_35E0DDEBBCF5E72D (categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_history ADD CONSTRAINT FK_35E0DDEBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_history ADD CONSTRAINT FK_35E0DDEBBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD last_login_at DATETIME DEFAULT NULL, ADD created_at DATETIME NOT NULL, ADD is_active TINYINT(1) NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL, ADD PRIMARY KEY (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_history DROP FOREIGN KEY FK_35E0DDEBA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_history DROP FOREIGN KEY FK_35E0DDEBBCF5E72D
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE quiz_history
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user MODIFY id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_8D93D649E7927C74 ON user
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX `primary` ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP last_login_at, DROP created_at, DROP is_active, CHANGE id id INT NOT NULL
        SQL);
    }
}
