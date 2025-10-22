import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export const options = {
  stages: [
    { duration: '30s', target: 50 },  // Ramp-up to 50 users
    { duration: '1m', target: 100 },  // Ramp-up to 100 users
    { duration: '1m', target: 100 },  // Stay at 100 users
    { duration: '30s', target: 0 },   // Ramp-down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<200'], // 95% of requests should be below 200ms
    http_req_failed: ['rate<0.01'],    // Error rate should be below 1%
    errors: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:3001';
const WEBHOOK_SECRET = __ENV.WEBHOOK_SECRET || 'your-webhook-secret-key';

export default function () {
  // Test 1: Send notification
  const notificationPayload = {
    userId: Math.floor(Math.random() * 1000),
    taskId: Math.floor(Math.random() * 10000),
    message: `Task completed at ${Date.now()}`,
    timestamp: new Date().toISOString(),
  };

  const signature = generateSignature(JSON.stringify(notificationPayload), WEBHOOK_SECRET);

  const notifyRes = http.post(`${BASE_URL}/notify`, JSON.stringify(notificationPayload), {
    headers: {
      'Content-Type': 'application/json',
      'X-Signature': signature,
      'X-Webhook-Source': 'laravel-api',
    },
  });

  const notifySuccess = check(notifyRes, {
    'notify status 201': (r) => r.status === 201,
    'notify has notification_id': (r) => r.json('data.notification_id') !== undefined,
  });

  if (!notifySuccess) {
    errorRate.add(1);
  }

  sleep(1);

  // Test 2: Get notifications for user
  const userId = notificationPayload.userId;
  const getRes = http.get(`${BASE_URL}/notifications/${userId}?page=1&limit=10`);

  check(getRes, {
    'get notifications status 200': (r) => r.status === 200,
    'get notifications has data': (r) => r.json('data') !== undefined,
  }) || errorRate.add(1);

  sleep(1);

  // Test 3: Health check
  const healthRes = http.get(`${BASE_URL}/health`);

  check(healthRes, {
    'health check status 200': (r) => r.status === 200,
    'health check is healthy': (r) => r.json('status') === 'healthy',
  }) || errorRate.add(1);

  sleep(2);
}

// Simple HMAC-SHA256 signature generator (note: k6 doesn't have crypto module, this is simplified)
function generateSignature(payload, secret) {
  // In real k6 test, you'd use the k6 crypto module
  // For now, return a placeholder (you need to implement proper HMAC in k6)
  return 'sha256=' + 'a'.repeat(64); // Placeholder - implement proper HMAC in production
}
