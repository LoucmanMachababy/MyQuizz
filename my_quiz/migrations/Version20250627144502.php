<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250627144502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE challenge (id INT AUTO_INCREMENT NOT NULL, challenger_id INT NOT NULL, challenged_id INT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', is_active TINYINT(1) NOT NULL, max_score INT NOT NULL, time_limit INT NOT NULL, category VARCHAR(255) DEFAULT NULL, difficulty VARCHAR(255) NOT NULL, questions JSON DEFAULT NULL COMMENT '(DC2Type:json)', question_count INT NOT NULL, is_accepted TINYINT(1) NOT NULL, accepted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', is_completed TINYINT(1) NOT NULL, completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', status VARCHAR(255) NOT NULL, INDEX IDX_D70989512D521FDF (challenger_id), INDEX IDX_D70989515820179C (challenged_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE challenge_result (id INT AUTO_INCREMENT NOT NULL, challenge_id INT NOT NULL, user_id INT NOT NULL, score INT NOT NULL, correct_answers INT NOT NULL, total_questions INT NOT NULL, started_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', duration INT NOT NULL, answers JSON DEFAULT NULL COMMENT '(DC2Type:json)', is_completed TINYINT(1) NOT NULL, INDEX IDX_E0D762D298A21AC6 (challenge_id), INDEX IDX_E0D762D2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, points INT NOT NULL, quizzes_completed INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', is_public TINYINT(1) NOT NULL, invite_code VARCHAR(255) DEFAULT NULL, INDEX IDX_C4E0A61F7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE team_user (team_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_5C722232296CD8AE (team_id), INDEX IDX_5C722232A76ED395 (user_id), PRIMARY KEY(team_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE team_quiz (id INT AUTO_INCREMENT NOT NULL, team_id INT NOT NULL, created_by_id INT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, score INT NOT NULL, total_questions INT NOT NULL, correct_answers INT NOT NULL, started_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', duration INT NOT NULL, questions JSON DEFAULT NULL COMMENT '(DC2Type:json)', is_active TINYINT(1) NOT NULL, category VARCHAR(255) DEFAULT NULL, difficulty VARCHAR(255) NOT NULL, INDEX IDX_75F30EE9296CD8AE (team_id), INDEX IDX_75F30EE9B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE team_quiz_participant (id INT AUTO_INCREMENT NOT NULL, team_quiz_id INT NOT NULL, user_id INT NOT NULL, score INT NOT NULL, correct_answers INT NOT NULL, total_questions INT NOT NULL, started_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', duration INT NOT NULL, answers JSON DEFAULT NULL COMMENT '(DC2Type:json)', is_active TINYINT(1) NOT NULL, position INT NOT NULL, INDEX IDX_4F277A907A45A042 (team_quiz_id), INDEX IDX_4F277A90A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_team (user_id INT NOT NULL, team_id INT NOT NULL, INDEX IDX_BE61EAD6A76ED395 (user_id), INDEX IDX_BE61EAD6296CD8AE (team_id), PRIMARY KEY(user_id, team_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE challenge ADD CONSTRAINT FK_D70989512D521FDF FOREIGN KEY (challenger_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE challenge ADD CONSTRAINT FK_D70989515820179C FOREIGN KEY (challenged_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE challenge_result ADD CONSTRAINT FK_E0D762D298A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE challenge_result ADD CONSTRAINT FK_E0D762D2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_user ADD CONSTRAINT FK_5C722232296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_user ADD CONSTRAINT FK_5C722232A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_quiz ADD CONSTRAINT FK_75F30EE9296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_quiz ADD CONSTRAINT FK_75F30EE9B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_quiz_participant ADD CONSTRAINT FK_4F277A907A45A042 FOREIGN KEY (team_quiz_id) REFERENCES team_quiz (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_quiz_participant ADD CONSTRAINT FK_4F277A90A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_team ADD CONSTRAINT FK_BE61EAD6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_team ADD CONSTRAINT FK_BE61EAD6296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE challenge DROP FOREIGN KEY FK_D70989512D521FDF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE challenge DROP FOREIGN KEY FK_D70989515820179C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE challenge_result DROP FOREIGN KEY FK_E0D762D298A21AC6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE challenge_result DROP FOREIGN KEY FK_E0D762D2A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F7E3C61F9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_user DROP FOREIGN KEY FK_5C722232296CD8AE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_user DROP FOREIGN KEY FK_5C722232A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_quiz DROP FOREIGN KEY FK_75F30EE9296CD8AE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_quiz DROP FOREIGN KEY FK_75F30EE9B03A8386
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_quiz_participant DROP FOREIGN KEY FK_4F277A907A45A042
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_quiz_participant DROP FOREIGN KEY FK_4F277A90A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_team DROP FOREIGN KEY FK_BE61EAD6A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_team DROP FOREIGN KEY FK_BE61EAD6296CD8AE
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE challenge
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE challenge_result
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE team
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE team_user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE team_quiz
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE team_quiz_participant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_team
        SQL);
    }
}
