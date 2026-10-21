<?php
/**
 * TP Open Connect - Public Entry
 *
 * This is a placeholder file. The actual functionality is provided through API endpoints.
 *
 * API Endpoints:
 * - POST /api/v1/sso/verify - SSO verification
 * - GET  /api/v1/user/info - Get user information
 * - POST /api/v1/asset/trade - Asset trading
 *
 * Admin Endpoints:
 * - GET  /admin/api/tp-open-connect/apps - List apps
 * - POST /admin/api/tp-open-connect/apps/save - Save app
 * - POST /admin/api/tp-open-connect/apps/delete - Delete app
 * - GET  /admin/api/tp-open-connect/trade-logs - View trade logs
 */

header('Content-Type: application/json');
echo json_encode([
    'name' => 'TP Open Connect',
    'version' => '1.0.0',
    'description' => 'SSO Open Connection Plugin for NexusPHP',
    'endpoints' => [
        'api' => [
            'POST /api/v1/sso/verify',
            'GET /api/v1/user/info',
            'POST /api/v1/asset/trade',
        ],
        'admin' => [
            'GET /admin/api/tp-open-connect/apps',
            'POST /admin/api/tp-open-connect/apps/save',
            'POST /admin/api/tp-open-connect/apps/delete',
            'GET /admin/api/tp-open-connect/trade-logs',
        ],
    ],
]);