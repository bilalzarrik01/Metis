CREATE TABLE members (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ;

SELECT * FROM members ;

INSERT INTO members (name, email)
VALUES ('Bilal Zarrik', 'bilalkk@email.com');




CREATE TABLE projects (
    id INT(11) NOT NULL AUTO_INCREMENT,
    member_id INT(11) NOT NULL,
    title VARCHAR(150) NOT NULL,
    type ENUM('court','long') NOT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY member_id_idx (member_id),
    CONSTRAINT fk_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ;
INSERT INTO projects (member_id, title, type)
VALUES (1, 'Projet Gestion', 'court');


CREATE TABLE activities (
    id INT(11) NOT NULL AUTO_INCREMENT,
    project_id INT(11) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('en_cours','terminee') DEFAULT 'en_cours',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY project_id_idx (project_id),
    CONSTRAINT fk_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
SELECT *from  activities ;
INSERT INTO activities (project_id, description, status)
VALUES (2, 'Développement de la partie backend', 'en_cours');



