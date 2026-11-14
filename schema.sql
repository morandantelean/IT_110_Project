CREATE TABLE IF NOT EXISTS messages (
    id         SERIAL PRIMARY KEY,
    author     VARCHAR(50)  NOT NULL,
    body       TEXT         NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);