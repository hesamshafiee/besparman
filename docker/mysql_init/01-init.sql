-- دیتابیس اصلی تست‌ها برای PHPUnit
CREATE DATABASE IF NOT EXISTS bm_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- دسترسی کامل یوزر bm به دیتابیس تست
GRANT ALL PRIVILEGES ON bm_test.* TO 'bm'@'%' IDENTIFIED BY 'ZvXzmqqZiFPfeFv8UW4rwdym';


-- دیتابیس Metabase
CREATE DATABASE IF NOT EXISTS metabase CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- یوزر read-only برای متابیس که فقط به دیتابیس اصلی bm دسترسی SELECT داره
CREATE USER IF NOT EXISTS 'metabase_ro'@'%' IDENTIFIED BY 'Mb!12345678';
GRANT SELECT ON bm.* TO 'metabase_ro'@'%';

-- یوزر مخصوص خود متابیس برای internal metadata
CREATE USER IF NOT EXISTS 'metabase'@'%' IDENTIFIED BY 'Strong-MB-Pass-Here';
GRANT ALL PRIVILEGES ON metabase.* TO 'metabase'@'%';

FLUSH PRIVILEGES;
