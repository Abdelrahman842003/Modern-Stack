import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export const options = {
  stages: [
    { duration: '30s', target: 20 }, // Ramp-up to 20 users
    { duration: '1m', target: 50 },  // Ramp-up to 50 users
    { duration: '2m', target: 100 }, // Ramp-up to 100 users
    { duration: '1m', target: 50 },  // Ramp-down to 50 users
    { duration: '30s', target: 0 },  // Ramp-down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% of requests should be below 500ms
    http_req_failed: ['rate<0.05'],    // Error rate should be below 5%
    errors: ['rate<0.05'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
let authToken = '';

export function setup() {
  // Register a test user
  const registerRes = http.post(`${BASE_URL}/api/register`, JSON.stringify({
    name: 'Load Test User',
    email: `loadtest_${Date.now()}@example.com`,
    password: 'password123',
    password_confirmation: 'password123',
  }), {
    headers: { 'Content-Type': 'application/json' },
  });

  check(registerRes, {
    'registration successful': (r) => r.status === 201,
  });

  const token = registerRes.json('data.token');
  return { token };
}

export default function (data) {
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': `Bearer ${data.token}`,
  };

  // Test 1: List all tasks
  const listRes = http.get(`${BASE_URL}/api/tasks`, { headers });
  check(listRes, {
    'list tasks status 200': (r) => r.status === 200,
    'list tasks has data': (r) => r.json('data') !== undefined,
  }) || errorRate.add(1);

  sleep(1);

  // Test 2: Create a task
  const createRes = http.post(`${BASE_URL}/api/tasks`, JSON.stringify({
    title: `Load Test Task ${Date.now()}`,
    description: 'This is a load test task',
    status: 'pending',
    priority: 'medium',
    due_date: '2025-12-31',
  }), { headers });

  const taskCreated = check(createRes, {
    'create task status 201': (r) => r.status === 201,
    'create task has id': (r) => r.json('data.id') !== undefined,
  });

  if (!taskCreated) {
    errorRate.add(1);
    return;
  }

  const taskId = createRes.json('data.id');
  sleep(1);

  // Test 3: Get single task
  const getRes = http.get(`${BASE_URL}/api/tasks/${taskId}`, { headers });
  check(getRes, {
    'get task status 200': (r) => r.status === 200,
    'get task correct id': (r) => r.json('data.id') === taskId,
  }) || errorRate.add(1);

  sleep(1);

  // Test 4: Update task
  const updateRes = http.put(`${BASE_URL}/api/tasks/${taskId}`, JSON.stringify({
    title: `Updated Load Test Task ${Date.now()}`,
    status: 'in_progress',
  }), { headers });

  check(updateRes, {
    'update task status 200': (r) => r.status === 200,
    'update task status changed': (r) => r.json('data.status') === 'in_progress',
  }) || errorRate.add(1);

  sleep(1);

  // Test 5: Complete task (triggers webhook)
  const completeRes = http.post(`${BASE_URL}/api/tasks/${taskId}/complete`, null, { headers });
  check(completeRes, {
    'complete task status 200': (r) => r.status === 200,
    'complete task status is completed': (r) => r.json('data.status') === 'completed',
  }) || errorRate.add(1);

  sleep(1);

  // Test 6: Delete task
  const deleteRes = http.del(`${BASE_URL}/api/tasks/${taskId}`, null, { headers });
  check(deleteRes, {
    'delete task status 204': (r) => r.status === 204,
  }) || errorRate.add(1);

  sleep(2);
}

export function teardown(data) {
  // Cleanup: Logout
  http.post(`${BASE_URL}/api/logout`, null, {
    headers: {
      'Authorization': `Bearer ${data.token}`,
      'Accept': 'application/json',
    },
  });
}
