
/* MySQL/MariaDB   */
CREATE TABLE IF NOT EXISTS mutex_auto_tags (
    tag VARCHAR(50) NOT NULL PRIMARY KEY, /* Limited quantity */
    title VARCHAR(250) NOT NULL UNIQUE KEY,
    hash VARCHAR(30) NOT NULL,
    unlock_seconds INT(6) NOT NULL,
    revision_time INT(11) NOT NULL,
    date_create TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* PostgreSQL  */
CREATE TABLE IF NOT EXISTS mutex_auto_tags (
    tag VARCHAR(50) NOT NULL,
    title VARCHAR(250) NOT NULL,
    hash VARCHAR(30) NOT NULL,
    unlock_seconds INTEGER NOT NULL,
    revision_time INTEGER NOT NULL,
    date_create TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (tag), /* Limited quantity */
    UNIQUE (title)
);