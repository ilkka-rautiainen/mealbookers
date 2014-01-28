
CREATE TABLE config (
    sql_version integer NOT NULL,
    data_version integer NOT NULL
);

INSERT INTO config (sql_version, data_version) VALUES
(0, 0);