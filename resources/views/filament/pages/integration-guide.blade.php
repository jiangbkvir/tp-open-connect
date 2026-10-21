<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TP Open Connect 对接方案</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background: #f3f4f6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        h1 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 24px;
            color: #1f2937;
        }
        h2 {
            font-size: 20px;
            font-weight: 600;
            margin-top: 32px;
            margin-bottom: 16px;
            color: #1f2937;
        }
        h3 {
            font-size: 16px;
            font-weight: 600;
            margin-top: 24px;
            margin-bottom: 12px;
            color: #374151;
        }
        .intro {
            border-left: 4px solid #3b82f6;
            padding-left: 16px;
            margin-bottom: 32px;
            color: #6b7280;
        }
        .feature-list {
            background: #f9fafb;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 24px;
            list-style: none;
        }
        .feature-list li {
            padding: 8px 0;
            display: flex;
            align-items: center;
        }
        .check {
            color: #10b981;
            margin-right: 12px;
        }
        .warning-box {
            background: #fef3c7;
            border: 1px solid #fde68a;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        .error-box {
            background: #fff5f5;
            border: 1px solid #fecaca;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        .success-box {
            background: #f0fdf4;
            border: 1px solid #86efac;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-bottom: 16px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #d1d5db;
        }
        thead {
            background: #e5e7eb;
        }
        code {
            background: #e5e7eb;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
        }
        pre {
            background: white;
            padding: 16px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 13px;
            margin: 12px 0;
            border: 1px solid #e5e7eb;
        }
        .note {
            margin-top: 32px;
            padding: 20px;
            background: #eff6ff;
            border-radius: 6px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>📖 TP Open Connect 第三方接入应用对接方案</h1>

            <div class="intro">
                本文档详细说明如何将第三方接入应用与 NexusPHP 主站对接，实现单点登录（SSO）和资产同步功能。
            </div>

            <h2>一、功能概述</h2>
            <ul class="feature-list">
                <li><span class="check">✓</span><span><strong>单点登录（SSO）</strong> - 用户在第三方接入应用登录时，通过主站验证身份</span></li>
                <li><span class="check">✓</span><span><strong>用户信息查询</strong> - 获取用户的上传量、下载量、魔力值等资产数据</span></li>
                <li><span class="check">✓</span><span><strong>资产交易</strong> - 增加或扣减用户资产（魔力值、上传量、下载量、签到卡）</span></li>
                <li><span class="check">✓</span><span><strong>安全保障</strong> - HMAC-SHA256 签名、防重放攻击、细粒度权限控制</span></li>
            </ul>

            <h2>二、对接前置条件</h2>
            <div class="error-box">
                <ol>
                    <li><strong>获取 App ID 和 App Secret</strong><br>联系主站管理员申请第三方接入应用凭证</li>
                    <li><strong>配置权限</strong><br>确认已获得所需的 API 权限（SSO、资产查询、资产交易等）</li>
                    <li><strong>了解主站 API 地址</strong><br>示例：https://main-site.com/api/v1/</li>
                    <li><strong>确认第三方接入应用服务端支持</strong><br>PHP 7.4+ / Python 3.7+ / Node.js 14+ / 其他支持 HMAC-SHA256 的语言</li>
                </ol>
            </div>

            <h2>三、API 接口说明</h2>

            <h3>3.1 请求签名规则</h3>
            <div class="success-box">
                <p>所有 API 请求必须包含以下请求头：</p>
                <table>
                    <thead>
                        <tr>
                            <th>请求头</th>
                            <th>说明</th>
                            <th>示例</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>X-App-ID</code></td>
                            <td>第三方接入应用 ID</td>
                            <td>site_001</td>
                        </tr>
                        <tr>
                            <td><code>X-Timestamp</code></td>
                            <td>Unix 时间戳（秒）</td>
                            <td>1696089600</td>
                        </tr>
                        <tr>
                            <td><code>X-Nonce</code></td>
                            <td>随机字符串（32位）</td>
                            <td>abc123...</td>
                        </tr>
                        <tr>
                            <td><code>X-Signature</code></td>
                            <td>HMAC-SHA256 签名</td>
                            <td>a1b2c3...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="success-box">
                <p><strong>签名算法：</strong></p>
                <pre><code>// 构造签名字符串
payload = "{Method}\n{Path}\n{CanonicalQuery}\n{AppID}\n{Timestamp}\n{Nonce}\n{RequestBody}"

// 计算 HMAC-SHA256
signature = HMAC_SHA256(payload, AppSecret)

// 示例（Python）
import hmac
import hashlib

    payload = f"{method}\n{path}\n{canonical_query}\n{app_id}\n{timestamp}\n{nonce}\n{body}"
signature = hmac.new(
    app_secret.encode('utf-8'),
    payload.encode('utf-8'),
    hashlib.sha256
).hexdigest()</code></pre>
            </div>

            <h3>3.2 单点登录（SSO）</h3>
            <div class="info-box">
                <p><strong>接口地址：</strong> <code>POST /api/v1/sso/verify</code></p>
            </div>

            <div class="card">
                <p><strong>请求参数：</strong></p>
                <pre><code>{
    "sso_key": "用户从主站获取的临时密钥"
}</code></pre>
            </div>

            <div class="card">
                <p><strong>响应示例：</strong></p>
                <pre><code>{
    "code": 200,
    "message": "SSO 核销成功",
    "data": {
        "user_id": 1001,
        "username": "user_example",
        "email": "user@example.com",
        "class": 1
    }
}</code></pre>
            </div>

            <div class="warning-box">
                <p><strong>注意：</strong>SSO Key 只能使用一次，核销后立即失效。</p>
            </div>

            <h3>3.3 用户信息查询</h3>
            <div class="info-box">
                <p><strong>接口地址：</strong> <code>GET /api/v1/user/info?user_id={用户ID}</code></p>
            </div>

            <h3>3.4 资产交易</h3>
            <div class="info-box">
                <p><strong>接口地址：</strong> <code>POST /api/v1/asset/trade</code></p>
            </div>

            <div class="card">
                <p><strong>请求参数：</strong></p>
                <pre><code>{
    "user_id": 1001,
    "out_trade_no": "ORDER_20260904_001",
    "asset_type": "bonus",
    "action": "increase",
    "amount": "100",
    "remark": "签到奖励"
}</code></pre>
            </div>

            <div class="info-box">
                <p><strong>参数说明：</strong></p>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><code>user_id</code> - 用户 ID（必填）</li>
                    <li><code>out_trade_no</code> - 外部订单号，用于幂等性（必填，建议格式：站点名_时间戳_随机数）</li>
                    <li><code>asset_type</code> - 资产类型：<code>upload</code>、<code>download</code>、<code>bonus</code>、<code>signin_card</code></li>
                    <li><code>action</code> - 操作类型：<code>increase</code>（增加）、<code>deduct</code>（扣减）</li>
                    <li><code>amount</code> - 交易数量（必填，必须大于 0）</li>
                    <li><code>remark</code> - 交易备注（选填）</li>
                </ul>
            </div>

            <h2>四、完整对接示例</h2>

            <h3>4.1 Python 示例</h3>
            <div class="card">
                <pre><code>import hmac
import hashlib
import json
import time
import requests
import random

class TPOpenConnectClient:
    def __init__(self, base_url, app_id, app_secret):
        self.base_url = base_url
        self.app_id = app_id
        self.app_secret = app_secret

    def generate_signature(self, method, path, canonical_query, timestamp, nonce, body):
        """生成 HMAC-SHA256 签名"""
        payload = f"{method}\n{path}\n{canonical_query}\n{self.app_id}\n{timestamp}\n{nonce}\n{body}"
        signature = hmac.new(
            self.app_secret.encode('utf-8'),
            payload.encode('utf-8'),
            hashlib.sha256
        ).hexdigest()
        return signature

    def send_request(self, method, endpoint, data=None):
        """发送 API 请求"""
        timestamp = int(time.time())
        nonce = hashlib.sha256(str(random.random()).encode()).hexdigest()[:32]
        body = json.dumps(data, separators=(',', ':')) if data else ""
        path, _, query = endpoint.partition('?')
        method = method.upper()
        canonical_query = query

        signature = self.generate_signature(method, path, canonical_query, timestamp, nonce, body)

        headers = {
            'X-App-ID': self.app_id,
            'X-Timestamp': str(timestamp),
            'X-Nonce': nonce,
            'X-Signature': signature,
            'Content-Type': 'application/json'
        }

        url = f"{self.base_url}{endpoint}"

        if method == 'GET':
            response = requests.get(url, headers=headers)
        else:
            response = requests.post(url, headers=headers, data=body)

        return response.json()

# 使用示例
client = TPOpenConnectClient(
    base_url='https://main-site.com',
    app_id='your_app_id',
    app_secret='your_app_secret'
)

# 查询用户信息
user_info = client.send_request('GET', '/api/v1/user/info?user_id=1001')
print(user_info)

# 增加魔力值
result = client.send_request('POST', '/api/v1/asset/trade', {
    'user_id': 1001,
    'out_trade_no': f'SUBSITE_{int(time.time())}_{random.randint(1000,9999)}',
    'asset_type': 'bonus',
    'action': 'increase',
    'amount': '100',
    'remark': '签到奖励'
})
print(result)</code></pre>
            </div>

            <h2>五、注意事项</h2>
            <div class="warning-box">
                <ol>
                    <li><strong>时间戳校验</strong> - 请求时间戳与服务器时间误差不能超过 ±300 秒（5 分钟）</li>
                    <li><strong>Nonce 唯一性</strong> - 每个 Nonce 只能使用一次，防止重放攻击</li>
                    <li><strong>幂等性保证</strong> - 使用唯一的 out_trade_no，相同的订单号不会重复交易</li>
                    <li><strong>权限控制</strong> - 确保 App ID 已获得相应的 API 权限</li>
                    <li><strong>资产精度</strong> - 所有资产数值使用字符串类型，避免大数精度丢失</li>
                    <li><strong>并发控制</strong> - 对同一用户的并发交易会加锁，失败返回 HTTP 429</li>
                </ol>
            </div>

            <h2>六、错误码说明</h2>
            <table>
                <thead>
                    <tr>
                        <th>HTTP 状态码</th>
                        <th>说明</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code style="color: #10b981;">200</code></td>
                        <td>成功</td>
                    </tr>
                    <tr>
                        <td><code style="color: #ef4444;">400</code></td>
                        <td>参数错误 / 余额不足</td>
                    </tr>
                    <tr>
                        <td><code style="color: #ef4444;">401</code></td>
                        <td>签名错误 / 请求过期 / 重放攻击</td>
                    </tr>
                    <tr>
                        <td><code style="color: #ef4444;">403</code></td>
                        <td>权限不足 / 第三方接入应用被禁用</td>
                    </tr>
                    <tr>
                        <td><code style="color: #ef4444;">404</code></td>
                        <td>资源不存在</td>
                    </tr>
                    <tr>
                        <td><code style="color: #f59e0b;">429</code></td>
                        <td>请求过于频繁（并发锁）</td>
                    </tr>
                    <tr>
                        <td><code style="color: #ef4444;">500</code></td>
                        <td>服务器内部错误</td>
                    </tr>
                </tbody>
            </table>

            <div class="note">
                <p>如有疑问，请联系主站管理员或查阅详细文档</p>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2>七、主站固定跳转</h2>

            <div class="info-box">
                <p><strong>接口地址：</strong> <code>GET /tp-open-connect/sso/generate?app_id=mobile_app_001</code></p>
                <p><strong>认证方式：</strong> Session 认证（需要登录）</p>
                <p>主站校验应用状态、SSO 权限和白名单后，生成一次性 Key 并 302 跳转到白名单中的第一个地址。</p>
            </div>

            <div class="card">
                <p><strong>前端按钮示例：</strong></p>
                <pre><code>&lt;a href="/tp-open-connect/sso/generate?app_id=mobile_app_001"&gt;打开第三方接入应用&lt;/a&gt;</code></pre>
            </div>

            <div class="warning-box">
                <p><strong>注意：</strong></p>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>该入口仅供主站前端调用，不需要 HMAC 签名</li>
                    <li>需要用户登录（Session 认证）</li>
                    <li>跳转地址由主站后台白名单配置决定，前端不能自定义跳转地址</li>
                    <li>自动使用当前登录用户的 ID 生成 SSO Key</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
