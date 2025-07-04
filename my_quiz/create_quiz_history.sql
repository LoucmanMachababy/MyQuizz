CREATE TABLE quiz_history (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT DEFAULT NULL,
    categorie_id INT NOT NULL,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    completed_at DATETIME NOT NULL,
    user_ip VARCHAR(45) DEFAULT NULL,
    INDEX IDX_35E0DDEBA76ED395 (user_id),
    INDEX IDX_35E0DDEBBCF5E72D (categorie_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

ALTER TABLE quiz_history ADD CONSTRAINT FK_35E0DDEBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);
ALTER TABLE quiz_history ADD CONSTRAINT FK_35E0DDEBBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id); 