-- TP Open Connect 插件数据库表
-- 请在 NexusPHP 数据库中执行此 SQL

-- 1. 第三方接入应用凭证与细粒度权限配置表
CREATE TABLE IF NOT EXISTS `api_apps` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `app_id` VARCHAR(64) NOT NULL UNIQUE COMMENT '第三方接入应用 App ID',
  `app_secret` VARCHAR(128) NOT NULL COMMENT '第三方接入应用 App Secret',
  `app_name` VARCHAR(100) NOT NULL COMMENT '接入应用名称',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 1启用, 0禁用',
  `redirect_uris` JSON NULL COMMENT 'SSO 允许的回调域名白名单',
  `asset_rules` JSON NULL COMMENT '资产与接口细粒度开关规则',
  `verify_origin` TINYINT(1) DEFAULT 0 COMMENT '是否校验请求来源: 1启用, 0禁用(默认,方便开发测试)',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_app_id` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='第三方接入应用 API 权限配置表';

-- 2. 多资产交易流水审计表
CREATE TABLE IF NOT EXISTS `api_asset_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `trade_id` VARCHAR(64) NOT NULL UNIQUE COMMENT '全局唯一交易流水号',
  `out_trade_no` VARCHAR(64) NOT NULL COMMENT '接入应用外部唯一交易单号',
  `app_id` VARCHAR(64) NOT NULL COMMENT '调用的接入应用 App ID',
  `user_id` INT UNSIGNED NOT NULL COMMENT '关联主站用户 ID',
  `asset_type` ENUM('upload', 'download', 'bonus', 'signin_card') NOT NULL COMMENT '资产类型',
  `action` ENUM('deduct', 'increase') NOT NULL COMMENT '交易行为: deduct扣减, increase增加',
  `amount` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '交易数量/字节数',
  `before_amount` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '交易前资产余额',
  `after_amount` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '交易后资产余额',
  `remark` VARCHAR(255) DEFAULT '' COMMENT '交易备注说明',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_app_out_trade` (`app_id`, `out_trade_no`),
  INDEX `idx_user_asset` (`user_id`, `asset_type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API 资产交易日志审计表';

-- 3. 对外接口失败与管理员配置变更日志
CREATE TABLE IF NOT EXISTS `api_operation_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `interface_type` ENUM('external', 'internal') NOT NULL,
  `action` VARCHAR(64) NOT NULL,
  `method` VARCHAR(10) NULL,
  `path` VARCHAR(255) NULL,
  `app_id` VARCHAR(64) NULL,
  `operator_id` INT UNSIGNED NULL,
  `user_id` INT UNSIGNED NULL,
  `status_code` SMALLINT UNSIGNED NULL,
  `result` VARCHAR(255) NOT NULL DEFAULT '',
  `ip` VARCHAR(45) NULL,
  `request_id` VARCHAR(128) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_operation_type_time` (`interface_type`, `created_at`),
  INDEX `idx_operation_app_time` (`app_id`, `created_at`),
  INDEX `idx_operation_operator_time` (`operator_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='TP Open Connect 接口与管理操作日志';

-- 安装完成提示
SELECT 'TP Open Connect 数据表创建成功！' AS message;
SELECT COUNT(*) AS tables_count FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name LIKE 'api_%';
