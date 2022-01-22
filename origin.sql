
/* MySQL/MariaDB   */
CREATE TABLE IF NOT EXISTS mutex_auto_tags (
    tag VARCHAR(50) NOT NULL PRIMARY KEY,
    title VARCHAR(250) NOT NULL UNIQUE KEY,
    hash VARCHAR(30) NOT NULL,
    unlock_seconds INT(6) NOT NULL,
    revision_time INT(11) NOT NULL,
    date_create TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

/* PostgreSQL  */
CREATE TABLE IF NOT EXISTS mutex_auto_tags (
    tag VARCHAR(50) NOT NULL,
    title VARCHAR(250) NOT NULL,
    hash VARCHAR(30) NOT NULL,
    unlock_seconds INTEGER NOT NULL,
    revision_time INTEGER NOT NULL,
    date_create TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (tag),
    UNIQUE (title)
);