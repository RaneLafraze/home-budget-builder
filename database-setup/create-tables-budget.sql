# Clear all the tables
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS entries;
DROP TABLE IF EXISTS rules;
DROP TABLE IF EXISTS conditions;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS funds;

DROP USER IF EXISTS 'web-agent-budget'@'localhost';
SET FOREIGN_KEY_CHECKS = 1;

# Now add the tables

CREATE TABLE entries (
    id INT AUTO_INCREMENT,
    summary VARCHAR(255),
    timeAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id)
);

# Defines how money will be distributed, if conditions are true
CREATE TABLE rules (
    id INT AUTO_INCREMENT,
    ruleType ENUM('fixed', 'percent'),
    amountModifier DECIMAL(7,2),
    
    PRIMARY KEY (id)
);

CREATE TABLE conditions (
    id INT AUTO_INCREMENT,
    sourceRule INT NOT NULL,
    
    PRIMARY KEY (id),
    FOREIGN KEY (sourceRule) REFERENCES rules (id)
);

CREATE TABLE funds (
    id INT AUTO_INCREMENT,
    fundName VARCHAR(255),
    ruleSet INT DEFAULT 1,
    startEntry INT,
    endEntry INT,
    
    PRIMARY KEY (id),
    FOREIGN KEY (ruleSet) REFERENCES rules (id),
    FOREIGN KEY (startEntry) REFERENCES entries (id),
    FOREIGN KEY (endEntry) REFERENCES entries (id)
);

CREATE TABLE transactions (
    id INT AUTO_INCREMENT,
    amount DECIMAL(7, 2) NOT NULL,
    balance DECIMAL(7,2) DEFAULT 0.00,
    attachedFund INT NOT NULL,
    attachedEntry INT NOT NULL,
    
    PRIMARY KEY (id),
    FOREIGN KEY (attachedFund) REFERENCES funds (id)
    FOREIGN KEY (attachedEntry) REFERENCES entries (id)
);

# Create user(s) with needed permissions
CREATE USER 'web-agent-budget'@'localhost' IDENTIFIED BY 'light-complex-p@ssword';
GRANT ALTER, UPDATE, SELECT, DELETE ON budget.entries TO 'web-agent-budget'@'localhost';
GRANT ALTER, UPDATE, SELECT, DELETE ON budget.rules TO 'web-agent-budget'@'localhost';
GRANT ALTER, UPDATE, SELECT, DELETE ON budget.conditions TO 'web-agent-budget'@'localhost';
GRANT ALTER, UPDATE, SELECT, DELETE ON budget.funds TO 'web-agent-budget'@'localhost';
GRANT ALTER, UPDATE, SELECT, DELETE ON budget.transactions TO 'web-agent-budget'@'localhost';

FLUSH PRIVILEGES;

