-- Replication user for the change-data-capture pipeline (<service-name>-cdc service).
-- This script is intended ONLY for the local environment. Outside it this user is provisioned
-- by the deployment stack, and its credentials live in a secret store, or, where there is
-- none, in the deployed environment alone. Never in a tracked file.
--
-- Runs on first MySQL container start via docker-entrypoint-initdb.d, after
-- 01-init-security.sh. The <service-name>-cdc service connects with these credentials
-- and stores its replication state in the cdc schema (see docker-compose.yml).

CREATE USER IF NOT EXISTS 'cdc'@'%' IDENTIFIED BY 'cdc';

GRANT ALL ON cdc.* TO 'cdc'@'%';
GRANT SELECT, REPLICATION CLIENT, REPLICATION SLAVE ON *.* TO 'cdc'@'%';

FLUSH PRIVILEGES;
