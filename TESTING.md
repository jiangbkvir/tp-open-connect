# TP Open Connect 测试说明

## 安装检查

```bash
# 执行迁移
php artisan migrate --path=plugins/TpOpenConnect/database/migrations --realpath --force

# 检查路由
php artisan route:list | grep -E 'api/v1/(sso/verify|user/info|asset/trade)|tp-open-connect'

# 确认数据表
php artisan db:table api_apps
php artisan db:table api_asset_logs
php artisan db:table api_operation_logs
```

确认存在以下表：
- `api_apps` - 第三方接入应用配置
- `api_asset_logs` - 资产交易日志
- `api_operation_logs` - 操作日志

## 管理后台测试

### 1. 创建应用测试

| 场景 | 期望结果 |
|------|---------|
| 填写表单点击创建 | 弹出"接入应用创建成功"弹窗 |
| 弹窗显示 App ID | ✅ 显示且可复制 |
| 弹窗显示 App Secret | ✅ 显示且可复制（黄色背景高亮）|
| 弹窗显示警告提示 | ⚠️ Secret 只显示这一次 |
| 点击"我已保存"关闭 | 跳转到应用详情页或列表页 |
| 刷新页面后查看 Secret | ❌ 无法查看（只能重新生成）|

**关键检查点**：
- Secret 必须只显示一次
- Secret 必须有醒目的视觉提示（黄色背景）
- 必须有明确的警告文案

### 2. 重新生成 Secret 测试

| 场景 | 期望结果 |
|------|---------|
| 进入详情页 | 看到"重新生成 Secret"按钮 |
| 点击"重新生成 Secret" | 弹出确认对话框 |
| 确认对话框显示警告 | "这将使旧的 App Secret 失效" |
| 点击"取消" | 关闭对话框，无操作 |
| 点击"确定" | 弹出新的 Secret 显示弹窗 |
| 新 Secret 弹窗显示内容 | 新的 Secret 值和复制按钮 |
| 新 Secret 弹窗警告 | "只显示这一次" |
| 点击"关闭" | 弹窗关闭，Secret 清空 |
| 再次点击查看 | ❌ 无法再次查看旧 Secret |

### 3. 编辑应用测试

| 场景 | 期望结果 |
|------|---------|
| 编辑应用名称 | ✅ 保存成功 |
| 编辑回调域名 | ✅ 保存成功 |
| 修改权限开关 | ✅ 保存成功 |
| 开启/关闭来源校验 | ✅ 保存成功 |
| 查看 Secret | ❌ 无法直接查看（需要重新生成）|

### 4. 删除应用测试

| 场景 | 期望结果 |
|------|---------|
| 点击删除 | 弹出确认对话框 |
| 确认删除 | 应用被删除，返回列表页 |
| 删除后操作日志 | 记录删除操作 |

## 核心功能测试

### 5. 认证安全测试

| 场景 | 期望结果 | 日志记录 |
|------|---------|---------|
| 缺少 X-App-ID | 401 | 是 |
| 缺少 X-Timestamp | 401 | 是 |
| 缺少 X-Nonce | 401 | 是 |
| 缺少 X-Signature | 401 | 是 |
| 错误的签名 | 401 | 是 |
| 过期的时间戳（>60秒） | 401 | 是 |
| 时间戳为负数或0 | 401 | 是 |
| Nonce 重放（5分钟内） | 401 | 是 |
| 非标准 Nonce 格式（特殊字符） | 401 | 是 |
| 被禁用的 App | 403 | 是 |
| 不存在的 App ID | 403 | 是 |
| App ID 格式非法（包含特殊字符） | 401 | 是 |
| 来源校验开启 + 非法 Origin | 403 | 是 |
| 来源校验关闭 + 非法 Origin | 200 | 否 |

### 6. SSO 测试

| 场景 | 期望结果 |
|------|---------|
| 正常 SSO Key 核销 | 200，返回用户信息 |
| 已使用的 Key 再次核销 | 400 |
| 过期（30秒）Key 核销 | 400 |
| 其他应用的 Key 核销 | 400 |
| 未启用 SSO 权限 | 403 |
| 未确认邮箱用户 | 403 |
| 已禁用用户 | 403 |
| 格式错误的 Key | 400 |

### 7. 用户查询测试

| 场景 | 期望结果 |
|------|---------|
| 正常查询（有权限） | 200，返回用户数据 |
| 用户 ID 不存在 | 400（统一响应） |
| 用户未确认 | 400（统一响应） |
| 用户已禁用 | 400（统一响应） |
| 无 query 权限 | 200，不返回资产字段 |
| 非法 user_id（负数） | 400 |
| 非法 user_id（字符串） | 400 |
| user_id = 0 | 400 |

### 8. 资产交易测试

| 场景 | 期望结果 |
|------|---------|
| 正常增加资产 | 200，余额增加 |
| 正常扣减资产 | 200，余额减少 |
| 余额不足扣减 | 400 |
| 重复订单号（相同参数） | 200，返回历史结果 |
| 重复订单号（不同参数） | 409 |
| 无 deduct 权限 | 403 |
| 无 increase 权限 | 403 |
| 非法 asset_type | 400 |
| 非法 action | 400 |
| 负数金额 | 400 |
| 零金额 | 400 |
| 超大金额（>2^64） | 400 |
| 非法金额（字符串） | 400 |
| 金额含小数 | 400 |
| 并发交易（同一用户） | 429 或正常串行 |
| 非法 user_id | 400 |

### 9. 幂等性测试

```bash
# 使用相同 out_trade_no 发送两次请求
# 第一次：成功 200
# 第二次：返回第一次的结果（200），code 相同，data 相同

# 使用相同 out_trade_no 但不同金额
# 第一次：成功 200，金额 100
# 第二次：返回 409（参数冲突）
```

### 10. 频率限制测试

| 接口 | 限制 | 测试方法 |
|------|------|---------|
| SSO 验证 | 60次/分钟 | 快速发送 61 次 |
| 用户查询 | 60次/分钟 | 快速发送 61 次 |
| 资产交易 | 30次/分钟 | 快速发送 31 次 |

期望：超过限制后返回 429

### 11. 并发测试

```bash
# 使用 ab 或类似工具模拟并发
ab -n 100 -c 10 -T 'application/json' \
   -H 'X-App-ID: xxx' \
   -H 'X-Timestamp: xxx' \
   -H 'X-Nonce: xxx' \
   -H 'X-Signature: xxx' \
   -p trade.json \
   http://localhost/api/v1/asset/trade
```

期望：无重复交易，无数据不一致

### 12. 来源校验开关测试

| 场景 | verify_origin | Origin 头 | 期望结果 |
|------|---------------|-----------|---------|
| 合法来源 | 1 | 在白名单中 | 200 |
| 非法来源 | 1 | 不在白名单中 | 403 |
| 无来源 | 1 | 无 | 200（允许服务端请求） |
| 任意来源 | 0 | 任意 | 200 |

### 13. 签名一致性测试

```python
# 使用以下测试向量验证签名算法

# 测试向量 1：简单 POST
method = "POST"
path = "/api/v1/sso/verify"
query = {}
app_id = "test_app"
timestamp = "1696089600"
nonce = "abcd1234abcd1234"
body = '{"sso_key":"test"}'

payload = f"{method}\n{path}\n\n{app_id}\n{timestamp}\n{nonce}\n{body}"
# 使用已知 secret 计算签名，与文档示例对比
```

签名原文必须严格使用：
```
METHOD\nPATH\nCANONICAL_QUERY\nAPP_ID\nTIMESTAMP\nNONCE\nBODY
```

注意：
- 查询参数为空时，第三行为空字符串（不是空格）
- 查询参数需要按名排序
- POST 请求体必须保持原样，不做任何格式化

### 14. 日志记录测试

| 场景 | api_operation_logs |
|------|-------------------|
| 外部接口成功 | 不记录 |
| 外部接口失败 | 记录（interface_type=external） |
| 管理员创建 | 记录（interface_type=internal, action=admin.app.create） |
| 管理员修改 | 记录（interface_type=internal, action=admin.app.update） |
| 管理员删除 | 记录（interface_type=internal, action=admin.app.delete） |
| 重置 Secret | 记录（interface_type=internal, action=admin.app.regenerate_secret） |

### 15. 边界测试

| 参数 | 边界值 | 期望 |
|------|--------|------|
| out_trade_no | 空字符串 | 400 |
| out_trade_no | 65字符 | 400 |
| out_trade_no | 64字符 | 200 |
| remark | 256字符 | 400 |
| remark | 255字符 | 200 |
| user_id | 2147483647 (MAX_INT) | 200 |
| user_id | 2147483648 (溢出) | 400 |
| amount | "0" | 400 |
| amount | "18446744073709551615" (MAX_UINT64) | 200 |
| amount | "18446744073709551616" (溢出) | 400 |
| timestamp | time() + 61 | 401 |
| timestamp | time() - 61 | 401 |
| nonce | 15字符 | 400 |
| nonce | 16字符 | 200 |
| nonce | 128字符 | 200 |
| nonce | 129字符 | 400 |

## 回归测试命令

```bash
# 完整回归测试脚本
cd /path/to/nexusphp

# 1. 清理缓存
php artisan optimize:clear

# 2. 执行测试用例
./vendor/bin/phpunit plugins/TpOpenConnect/tests/

# 3. 检查日志
mysql -e "SELECT * FROM api_operation_logs ORDER BY id DESC LIMIT 10;"

# 4. 检查交易记录
mysql -e "SELECT * FROM api_asset_logs ORDER BY id DESC LIMIT 10;"
```

## 生产环境上线前检查

- [ ] 所有接口返回符合预期
- [ ] 权限控制生效
- [ ] 并发无竞态条件
- [ ] 日志正常记录
- [ ] 来源校验开关配置正确（生产建议开启）
- [ ] 数据库迁移已执行
- [ ] Redis 连接正常
- [ ] 时间同步已配置（NTP）
- [ ] **创建应用后 Secret 正确显示（只显示一次）**
- [ ] **重新生成 Secret 功能正常（弹窗显示新 Secret）**
