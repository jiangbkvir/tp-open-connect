# TP Open Connect

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/jiangbkvir/tp-open-connect/releases)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-10%2F11%2F12-FF2D20.svg)](https://laravel.com/)
[![Filament](https://img.shields.io/badge/Filament-3.x-FBBC04.svg)](https://filamentphp.com/)

NexusPHP 主站开放连接插件，为第三方接入应用提供 SSO 单点登录、用户信息查询和资产交易能力。

## 功能特性

- **SSO 单点登录** - 一次性密钥核销，安全便捷
- **HMAC-SHA256 签名** - 请求签名与 Nonce 防重放攻击
- **请求来源校验** - 可配置是否校验 Origin/Referer 域名白名单
- **用户信息查询** - 上传量、下载量、魔力值、签到卡
- **资产交易** - 增加/扣减资产、幂等交易、审计流水
- **细粒度权限控制** - 独立配置每个第三方接入应用的权限
- **Filament 管理界面** - 可视化管理第三方应用和交易日志

## 环境要求

- NexusPHP 1.9+
- PHP 8.2+
- MySQL 5.7+ 或 MariaDB 10.3+
- Redis 3+
- PHP 扩展：`bcmath`、`redis`、`pdo_mysql`、`json`

## 安装

### 方式一：手动放置后安装（推荐）

**第一步：放置插件文件**

将 `TpOpenConnect` 插件目录复制到 NexusPHP 的 `plugins` 目录：

```bash
cp -r /path/to/TpOpenConnect /path/to/nexusphp/plugins/
```

确保目录结构如下：
```
nexusphp/
├── plugins/
│   └── TpOpenConnect/
│       ├── plugin.json
│       ├── src/
│       ├── routes/
│       ├── database/
│       └── ...
```

**第二步：执行安装命令**

```bash
cd /path/to/nexusphp

# 方法 A：使用 Composer 本地仓库（推荐，支持自动加载）
composer config repositories.tp-open-connect path plugins/TpOpenConnect
composer require jiangbkvir/tp-open-connect:@dev
php artisan tp-open-connect:install
php artisan optimize:clear

# 方法 B：不使用 Composer（手动加载）
php artisan migrate --path=plugins/TpOpenConnect/database/migrations --realpath --force
php artisan optimize:clear
```

### 方式二：直接解压到 plugins 目录

```bash
cd /path/to/nexusphp/plugins
unzip tp-open-connect-v1.0.0.zip
mv tp-open-connect-v1.0.0 TpOpenConnect
cd /path/to/nexusphp
php artisan migrate --path=plugins/TpOpenConnect/database/migrations --realpath --force
php artisan optimize:clear
```

## 安装验证

```bash
# 检查迁移状态
php artisan migrate:status --path=plugins/TpOpenConnect/database/migrations

# 查看已注册的 API 路由
php artisan route:list --path=api/v1

# 查看管理后台路由
php artisan route:list --path=tp-open-connect

# 检查数据表
php artisan db:table api_apps
php artisan db:table api_asset_logs
php artisan db:table api_operation_logs
```

管理后台入口：`/nexusphp/tp-open-connect`

对接方案入口：`/nexusphp/tp-open-connect/integration-guide`

## 卸载

保留数据移除插件：

```bash
php artisan tp-open-connect:uninstall
```

同时删除数据表（谨慎操作）：

```bash
php artisan tp-open-connect:uninstall --drop-data
```

如果使用了 Composer 安装：

```bash
composer remove jiangbkvir/tp-open-connect
```

## 快速开始

### 1. 创建第三方接入应用

进入管理后台 `System -> TP Open Connect`，点击"新建应用"：

| 字段 | 说明 |
|---|---|
| 应用名称 | 第三方接入应用名称，如"移动端应用" |
| 状态 | 是否启用 |
| 校验请求来源 | 是否校验 API 请求的 Origin/Referer（开发测试建议关闭，生产建议开启）|
| 回调域名白名单 | 允许调用 API 的域名列表，如 `https://mobile.example.com` |
| SSO 验证 | 是否允许 SSO 登录 |
| 资产权限 | 配置各类资产的查询、扣减、增加权限 |

**开发环境建议**：关闭"校验请求来源"，方便本地测试

**生产环境建议**：开启"校验请求来源"，并配置正确的回调域名白名单

### 2. 主站前端集成 SSO

如果主站使用固定入口，登录后可直接跳转（`app_id` 只能指定已配置的第三方接入应用）：

```text
GET /tp-open-connect/sso/generate?app_id=mobile_app_001
```

该请求会校验当前主站登录态，生成一次性 `sso_key`，然后返回 302 跳转到该接入应用白名单中的第一个地址。第三方接入应用收到 `sso_key` 后，再使用自己的 App Secret 调用 SSO 核销接口。

在主站前端添加"跳转到第三方接入应用"按钮：

```javascript
async function jumpToMobile() {
  const response = await fetch('/tp-open-connect/sso/generate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ app_id: 'mobile_app_001' })
  });

  const result = await response.json();
  if (result.code === 200) {
    window.location.href = result.data.sso_url;
  }
}
```

详细第三方对接方式参见 [INTEGRATION.md](INTEGRATION.md)。

### 3. 第三方接入应用对接

第三方接入应用接收 `sso_key` 后调用主站 API 验证：

```php
$ssoKey = $_GET['sso_key'];

$timestamp = time();
$nonce = bin2hex(random_bytes(16));
$body = json_encode(['sso_key' => $ssoKey], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$method = 'POST';
$path = '/api/v1/sso/verify';
$query = '';
$payload = "{$method}\n{$path}\n{$query}\n{$appId}\n{$timestamp}\n{$nonce}\n{$body}";
$signature = hash_hmac('sha256', $payload, $appSecret);

$response = Http::withHeaders([
    'X-App-ID' => $appId,
    'X-Timestamp' => $timestamp,
    'X-Nonce' => $nonce,
    'X-Signature' => $signature,
])->withBody($body, 'application/json')
  ->post('https://main-site.com/api/v1/sso/verify');

$userData = $response->json()['data'];
```

完整的对接指南请访问 `/nexusphp/tp-open-connect/integration-guide`。

## API 接口

### 认证方式

所有 API 请求必须包含以下请求头：

| 请求头 | 说明 |
|---|---|
| `X-App-ID` | 第三方接入应用 ID |
| `X-Timestamp` | Unix 时间戳（秒） |
| `X-Nonce` | 32 位随机字符串 |
| `X-Signature` | HMAC-SHA256 签名 |

签名算法：

```php
$payload = "{$method}\n{$path}\n{$canonicalQuery}\n{$appId}\n{$timestamp}\n{$nonce}\n{$requestBody}";
$signature = hash_hmac('sha256', $payload, $appSecret);
```

### 接口列表

| 接口 | 方法 | 说明 |
|---|---|---|
| `/api/v1/sso/verify` | POST | SSO 密钥核销 |
| `/api/v1/user/info` | GET | 查询用户信息 |
| `/api/v1/asset/trade` | POST | 资产交易 |

### 主站接口（Session 认证）

| 接口 | 方法 | 说明 |
|---|---|---|
| `/tp-open-connect/sso/generate` | POST | 生成 SSO 跳转 URL |
| `/tp-open-connect/sso/generate?app_id=...` | GET | 登录后直接跳转到第三方接入应用 |
| `/nexusphp/tp-open-connect/integration-guide` | GET | 对接方案文档页面 |

## 安全机制

### 请求来源校验

可在管理后台配置"校验请求来源"开关：
- **关闭**（默认）：方便开发环境测试，不校验 Origin/Referer
- **开启**：生产环境建议开启，API 会校验请求来源域名是否在白名单中

### 时间戳校验

请求时间戳与服务器时间误差不能超过 **±60 秒**，过期请求将返回 401 错误。

### Nonce 防重放

每个 Nonce 只能使用一次，在 **5 分钟（300秒）** 内重复使用将返回 401 错误。

### 幂等性保证

资产交易使用 `out_trade_no` 作为唯一标识，相同的订单号不会重复执行交易。

### 并发控制

对同一用户的并发交易会加 Redis 锁，避免竞态条件。

### 频率限制

| 接口 | 限制 |
|---|---|
| SSO 验证 / 用户信息查询 | 60 次/分钟 |
| 资产交易 | 30 次/分钟 |

## 权限配置

每个第三方接入应用可以独立配置以下权限：

| 权限 | 说明 |
|---|---|
| `sso_verify` | SSO 单点登录 |
| `upload_query` / `upload_deduct` / `upload_increase` | 上传量查询/扣减/增加 |
| `download_query` / `download_deduct` / `download_increase` | 下载量查询/扣减/增加 |
| `bonus_query` / `bonus_deduct` / `bonus_increase` | 魔力值查询/扣减/增加 |
| `signin_card_query` / `signin_card_deduct` / `signin_card_increase` | 签到卡查询/扣减/增加 |

## 管理 API 权限

管理后台 API 仅开放给已登录的维护开发员（Sysop，等级 15）及以上账号。

未登录返回 401，低等级账号返回 403。

## 文档

- [INTEGRATION.md](INTEGRATION.md) - 第三方应用对接教程
- [TESTING.md](TESTING.md) - 测试说明
- `/nexusphp/tp-open-connect/integration-guide` - 第三方接入应用对接完整指南

## 升级

### 从旧版本升级

```bash
cd /path/to/nexusphp

# 1. 更新插件文件（覆盖 plugins/TpOpenConnect 目录）
# ...

# 2. 执行新迁移
php artisan migrate --path=plugins/TpOpenConnect/database/migrations --realpath --force

# 3. 清理缓存
php artisan optimize:clear
```

### 数据库变更日志

- **2026-09-15**: 添加 `verify_origin` 字段，控制请求来源校验开关

## Contributing

欢迎提交 Issue 和 Pull Request。

1. Fork 本仓库
2. 创建特性分支 (`git checkout -b feature/amazing-feature`)
3. 提交更改 (`git commit -m 'Add some amazing feature'`)
4. 推送到分支 (`git push origin feature/amazing-feature`)
5. 创建 Pull Request

## License

MIT License. See [LICENSE](LICENSE).

## Security Vulnerabilities

如发现安全漏洞，请发送邮件至 [security@github.com](mailto:security@github.com)，而不是创建公开 Issue。

## Credits

- [jiangbkvir](https://github.com/jiangbkvir)
- [All Contributors](../../contributors)

---

Made with ❤️ for the NexusPHP community.
