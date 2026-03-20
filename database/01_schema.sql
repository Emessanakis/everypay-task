CREATE TABLE IF NOT EXISTS merchants (
    id           VARCHAR(36)                    PRIMARY KEY DEFAULT (UUID()),
    name         VARCHAR(255)                   NOT NULL,
    email        VARCHAR(255)                   NOT NULL,
    api_key      VARCHAR(64)                    NOT NULL UNIQUE,
    role         ENUM('merchant', 'admin')      NOT NULL DEFAULT 'merchant',
    psp_provider VARCHAR(50)                    DEFAULT NULL,
    created_at   TIMESTAMP                      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS charges (
    id             VARCHAR(36)  PRIMARY KEY DEFAULT (UUID()),
    merchant_id    VARCHAR(36)  NOT NULL,
    amount         INT          NOT NULL,
    currency       VARCHAR(3)   NOT NULL DEFAULT 'EUR',
    status         VARCHAR(50)  NOT NULL,
    transaction_id VARCHAR(255)          DEFAULT NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (merchant_id) REFERENCES merchants (id)
);
