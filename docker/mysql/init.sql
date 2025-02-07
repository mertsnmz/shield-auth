# Create database if not exists
CREATE DATABASE IF NOT EXISTS shield_auth_db;

# Create user and grant privileges
CREATE USER IF NOT EXISTS 'shield_auth_user'@'%' IDENTIFIED BY '46t#kf86T';
GRANT ALL PRIVILEGES ON shield_auth_db.* TO 'shield_auth_user'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES; 