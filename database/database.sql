CREATE TABLE urls (
    id int PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name varchar(255) NOT NULL,
    created_at TIMESTAMP,
    UNIQUE (name)
);

CREATE TABLE url_checks (
    id int PRIMARY KEY NOT NULL  GENERATED ALWAYS AS IDENTITY,
    url_id int,
    status_code int,
    h1 varchar(255),
    title varchar(255),
    description varchar(255),
    created_at TIMESTAMP,
    FOREIGN KEY (url_id) REFERENCES urls (id)
);