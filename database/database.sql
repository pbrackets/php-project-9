CREATE TABLE urls (
    id int PRIMARY KEY AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    created_at TIMESTAMP,
    UNIQUE (name)
);

CREATE TABLE url_checks (
    id int PRIMARY KEY NOT NULL AUTO_INCREMENT,
    url_id int,
    status_code int,
    h1 varchar(255),
    title varchar(255),
    description varchar(255),
    created_at TIMESTAMP,
    FOREIGN KEY (url_id) REFERENCES Urls (id)
);
