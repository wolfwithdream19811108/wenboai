-- 回滚请求来源字段。可重复执行。

SET @schema_name = DATABASE();

SET @drop_source_index_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @schema_name
       AND TABLE_NAME = 'cmf_imageai_api_request_log'
       AND INDEX_NAME = 'idx_source_time') > 0,
    'ALTER TABLE `cmf_imageai_api_request_log` DROP INDEX `idx_source_time`',
    'SELECT 1'
);
PREPARE drop_source_index_stmt FROM @drop_source_index_sql;
EXECUTE drop_source_index_stmt;
DEALLOCATE PREPARE drop_source_index_stmt;

SET @drop_source_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @schema_name
       AND TABLE_NAME = 'cmf_imageai_api_request_log'
       AND COLUMN_NAME = 'source') > 0,
    'ALTER TABLE `cmf_imageai_api_request_log` DROP COLUMN `source`',
    'SELECT 1'
);
PREPARE drop_source_stmt FROM @drop_source_sql;
EXECUTE drop_source_stmt;
DEALLOCATE PREPARE drop_source_stmt;
