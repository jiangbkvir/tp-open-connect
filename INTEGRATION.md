# TP Open Connect 第三方对接文档

## 1. 前置配置

主站管理员创建第三方接入应用后提供 `app_id` 和 `app_secret`，并配置接入应用的白名单根地址，例如 `https://app.example.com`。第三方只应在服务端保存 `app_secret`。

**注意**：请确认主站管理员已开启所需的权限（如 SSO 验证、资产查询、资产交易等）。

## 2. 主站固定跳转

用户登录主站后，浏览器访问：

```text
GET /tp-open-connect/sso/generate?app_id=your_app_id
```

主站会校验接入应用状态、SSO 权限和白名单，生成有效期 30 秒且只能使用一次的 `sso_key`，然后 302 跳转到该接入应用白名单中的第一个地址，并附加 `sso_key` 参数。

## 3. 对外接口认证

以下接口全部需要 HMAC 认证：

```text
POST /api/v1/sso/verify
GET  /api/v1/user/info?user_id=1001
POST /api/v1/asset/trade
```

### 3.1 签名算法

签名原文固定为七行（注意：每行末尾没有额外字符，包括空格）：

```text
METHOD
PATH
CANONICAL_QUERY
APP_ID
TIMESTAMP
NONCE
BODY
```

- `METHOD`：HTTP 方法，大写，如 `POST`、`GET`
- `PATH`：请求路径（不包含域名和查询参数）
- `CANONICAL_QUERY`：查询参数按参数名排序，使用 RFC3986 编码
- `APP_ID`：第三方接入应用 ID
- `TIMESTAMP`：Unix 时间戳（秒）
- `NONCE`：32 位随机字符串（16-128 位，字母数字和 `._:-`）
- `BODY`：请求体原始字符串（POST 请求），GET 请求为空字符串

**路径说明**：如果主站部署在子目录（例如 `https://main-site.com/nexusphp`），签名路径应包含 `/nexusphp` 前缀；根目录部署时则使用 `/api/v1/...`。

### 3.2 请求头

```text
X-App-ID: your_app_id
X-Timestamp: 1696089600
X-Nonce: abc123def456ghi789...
X-Signature: a1b2c3d4e5f6...
Content-Type: application/json
```

### 3.3 Python 示例

```python
import hashlib, hmac, json, time, secrets, requests

app_id = 'your_app_id'
app_secret = 'your_app_secret'

# SSO 核销示例
sso_key = 'xxxxxxxxxxxxxxxx'  # 从 URL 参数获取
path = '/api/v1/sso/verify'
body = json.dumps({'sso_key': sso_key}, separators=(',', ':'), ensure_ascii=False)

timestamp = str(int(time.time()))
nonce = secrets.token_hex(16)

# 构造签名原文（注意：查询参数为空，所以第三行为空）
payload = f'POST\n{path}\n\n{app_id}\n{timestamp}\n{nonce}\n{body}'

signature = hmac.new(
    app_secret.encode('utf-8'),
    payload.encode('utf-8'),
    hashlib.sha256
).hexdigest()

response = requests.post(
    'https://main-site.com' + path,
    data=body.encode('utf-8'),
    headers={
        'Content-Type': 'application/json',
        'X-App-ID': app_id,
        'X-Timestamp': timestamp,
        'X-Nonce': nonce,
        'X-Signature': signature,
    },
)

result = response.json()
if result['code'] == 200:
    user_data = result['data']
    print(f"用户: {user_data['username']}, 等级: {user_data['class_name']}")
else:
    print(f"错误: {result['message']}")
```

### 3.4 PHP 示例

```php
<?php

$appId = 'your_app_id';
$appSecret = 'your_app_secret';
$baseUrl = 'https://main-site.com'; // 根据实际部署调整

// 构造请求
$path = '/api/v1/user/info';
$query = ['user_id' => 1001];
ksort($query); // 参数按名排序
$queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

$method = 'GET';
$timestamp = time();
$nonce = bin2hex(random_bytes(16));
$body = ''; // GET 请求为空

// 构造签名原文
$payload = implode("\n", [
    $method,
    $path,
    $queryString,
    $appId,
    $timestamp,
    $nonce,
    $body,
]);

$signature = hash_hmac('sha256', $payload, $appSecret);

// 发送请求
$url = $baseUrl . $path . '?' . $queryString;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-App-ID: ' . $appId,
    'X-Timestamp: ' . $timestamp,
    'X-Nonce: ' . $nonce,
    'X-Signature: ' . $signature,
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
print_r($data);
```

## 4. SSO 核销

第三方收到跳转参数中的 `sso_key` 后调用 `/api/v1/sso/verify`。

### 请求

```text
POST /api/v1/sso/verify
Content-Type: application/json

{
    "sso_key": "从 URL 参数获取的密钥"
}
```

### 响应示例

```json
{
    "code": 200,
    "data": {
        "user_id": 1001,
        "username": "user_example",
        "email": "user@example.com",
        "class": 1,
        "class_name": "User"
    }
}
```

**字段说明**：
- `user_id` - 用户 ID
- `username` - 用户名
- `email` - 邮箱
- `class` - 用户等级（数字，0-16）
- `class_name` - 用户等级名称（如 User、Power User、Elite User、VIP 等）

**注意事项**：
- SSO Key 只能使用一次，核销后立即失效
- Key 与生成时的 `app_id` 和用户 ID 绑定，其他应用不能核销
- 有效期 30 秒，过期返回 400 错误

## 5. 用户信息查询

```text
GET /api/v1/user/info?user_id=1001
```

返回用户信息，具体字段取决于该应用的权限配置。

### 响应示例

```json
{
    "code": 200,
    "data": {
        "user_id": 1001,
        "username": "user_example",
        "class": 3,
        "class_name": "Elite User",
        "uploaded_bytes": "10737418240",
        "downloaded_bytes": "5368709120",
        "bonus_points": "500",
        "signin_cards": 5
    }
}
```

**字段说明**：
- `user_id` - 用户 ID
- `username` - 用户名
- `class` - 用户等级（数字）
- `class_name` - 用户等级名称
- `uploaded_bytes` - 上传量（字节，需有 query 权限）
- `downloaded_bytes` - 下载量（字节，需有 query 权限）
- `bonus_points` - 魔力值（需有 query 权限）
- `signin_cards` - 签到卡数量（需有 query 权限）

## 6. 资产交易

```text
POST /api/v1/asset/trade
Content-Type: application/json

{
    "user_id": 1001,
    "out_trade_no": "ORDER_20260915_001",
    "asset_type": "bonus",
    "action": "increase",
    "amount": "100",
    "remark": "签到奖励"
}
```

### 参数说明

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `user_id` | integer | 是 | 用户 ID |
| `out_trade_no` | string | 是 | 外部订单号，64字符以内，全局唯一 |
| `asset_type` | string | 是 | 资产类型：`upload`、`download`、`bonus`、`signin_card` |
| `action` | string | 是 | 操作类型：`increase`（增加）、`deduct`（扣减） |
| `amount` | string | 是 | 交易数量，正整数，字符串类型 |
| `remark` | string | 否 | 交易备注，255字符以内 |

### 响应示例

```json
{
    "code": 200,
    "data": {
        "trade_id": "TXA1B2C3D4E5F6...",
        "out_trade_no": "ORDER_20260915_001",
        "asset_type": "bonus",
        "action": "increase",
        "amount": "100",
        "before_amount": "500",
        "after_amount": "600"
    }
}
```

### 幂等性说明

- 使用 `out_trade_no` 保证幂等性
- 相同订单号重复请求会返回历史结果（HTTP 200）
- 相同订单号但参数（用户、资产类型、操作、金额）不同返回 409 错误

## 7. 失败处理

| HTTP 状态码 | 说明 |
|------------|------|
| `200` | 成功 |
| `400` | 参数错误 / 余额不足 / 用户不存在（统一返回，防止枚举） |
| `401` | 签名错误 / 请求过期（时间戳超过60秒）/ Nonce 重放（5分钟内重复） |
| `403` | 权限不足 / 第三方接入应用被禁用 / 请求来源未授权 |
| `404` | 资源不存在（如 SSO Key 无效） |
| `409` | 幂等订单参数冲突 |
| `429` | 请求过于频繁（并发锁或频率限制） |
| `500` | 服务器内部错误 |

**成功响应不会写外部接口失败日志；失败请求会写入主站操作日志。**

## 8. 安全建议

### 8.1 服务端保存密钥

`app_secret` 只应在服务端保存，不要暴露给客户端或前端代码。

### 8.2 处理时间同步

时间戳校验窗口为 60 秒，确保服务器时间与主站同步（可使用 NTP）。

### 8.3 幂等订单号生成

建议格式：`{站点标识}_{时间戳}_{随机数}`，例如：

```php
$outTradeNo = 'MYAPP_' . time() . '_' . mt_rand(1000, 9999);
```

### 8.4 错误重试策略

- 400/401/403/404/409：不重试，检查参数或权限
- 429：等待后重试（建议指数退避）
- 500：等待后重试，最多 3 次

## 9. 常见问题

### Q1: 签名失败返回 401

检查以下几点：
1. `app_secret` 是否正确（注意是 Secret，不是 ID）
2. 签名原文的换行符是否正确（Unix 风格 `\n`）
3. 路径是否包含子目录前缀
4. 查询参数是否按名排序并 RFC3986 编码
5. JSON 请求体是否完全一致（空格、换行、Unicode 转义）

### Q2: 时间戳过期返回 401

检查服务器时间与主站时间是否同步，建议使用 NTP 同步。

### Q3: 用户查询返回 400 "请求参数错误"

这是统一的错误响应，可能是：
- 用户 ID 不存在
- 用户未确认邮箱
- 用户被禁用

如需确认具体原因，请联系主站管理员。

### Q4: 资产交易提示 "无权操作"

请确认主站管理员已为该应用配置相应的资产交易权限。

