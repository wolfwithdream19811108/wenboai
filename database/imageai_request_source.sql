-- 为图片生成请求日志增加调用来源。
-- 可重复执行，兼容不支持 ADD COLUMN IF NOT EXISTS 的 MySQL 版本。

SET @schema_name = DATABASE();

SET @source_column_missing = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @schema_name
       AND TABLE_NAME = 'cmf_imageai_api_request_log'
       AND COLUMN_NAME = 'source') = 0,
    1,
    0
);

SET @add_source_sql = IF(
    @source_column_missing = 1,
    'ALTER TABLE `cmf_imageai_api_request_log` ADD COLUMN `source` varchar(20) NOT NULL DEFAULT ''api'' COMMENT ''web/api/api_test'' AFTER `model`',
    'SELECT 1'
);
PREPARE add_source_stmt FROM @add_source_sql;
EXECUTE add_source_stmt;
DEALLOCATE PREPARE add_source_stmt;

-- 只在首次增加字段时回填旧记录，重复执行不会改写后续准确记录的来源。
SET @backfill_source_sql = IF(
    @source_column_missing = 1,
    'UPDATE `cmf_imageai_api_request_log` SET `source` = CASE WHEN `api_key_id` > 0 THEN ''api'' WHEN `user_id` > 0 THEN ''web'' ELSE ''api_test'' END WHERE `source` = ''api''',
    'SELECT 1'
);
PREPARE backfill_source_stmt FROM @backfill_source_sql;
EXECUTE backfill_source_stmt;
DEALLOCATE PREPARE backfill_source_stmt;

SET @add_source_index_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @schema_name
       AND TABLE_NAME = 'cmf_imageai_api_request_log'
       AND INDEX_NAME = 'idx_source_time') = 0,
    'ALTER TABLE `cmf_imageai_api_request_log` ADD INDEX `idx_source_time` (`source`, `create_time`)',
    'SELECT 1'
);
PREPARE add_source_index_stmt FROM @add_source_index_sql;
EXECUTE add_source_index_stmt;
DEALLOCATE PREPARE add_source_index_stmt;
