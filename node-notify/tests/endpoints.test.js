const request = require('supertest');
const app = require('../src/index');
const { generateSignature } = require('../src/utils/signature');

const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET || 'your-secret-key-here';

describe('Notification Service API', () => {
  describe('GET /health', () => {
    it('should return health status', async () => {
      const response = await request(app).get('/health');

      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('status', 'OK');
      expect(response.body).toHaveProperty('timestamp');
      expect(response.body).toHaveProperty('uptime');
      expect(response.body).toHaveProperty('service', 'task-notification-service');
    });
  });

  describe('POST /notify', () => {
    const validPayload = {
      userId: 1,
      taskId: 123,
      message: 'Task "Test Task" has been completed',
      timestamp: new Date().toISOString(),
    };

    it('should accept valid notification with correct signature', async () => {
      const signature = generateSignature(WEBHOOK_SECRET, validPayload);

      const response = await request(app)
        .post('/notify')
        .set('X-Signature', signature)
        .send(validPayload);

      expect(response.status).toBe(201);
      expect(response.body).toHaveProperty('data');
      expect(response.body.data).toHaveProperty('message', 'Notification received successfully');
      expect(response.body.data).toHaveProperty('notification_id');
    });

    it('should reject notification without signature', async () => {
      const response = await request(app)
        .post('/notify')
        .send(validPayload);

      expect(response.status).toBe(401);
      expect(response.body).toHaveProperty('error');
      expect(response.body.error).toHaveProperty('code', 'MISSING_SIGNATURE');
    });

    it('should reject notification with invalid signature', async () => {
      const response = await request(app)
        .post('/notify')
        .set('X-Signature', 'sha256=invalid')
        .send(validPayload);

      expect(response.status).toBe(401);
      expect(response.body).toHaveProperty('error');
      expect(response.body.error).toHaveProperty('code', 'INVALID_SIGNATURE');
    });

    it('should reject notification with missing user_id', async () => {
      const invalidPayload = { ...validPayload };
      delete invalidPayload.userId;

      const signature = generateSignature(WEBHOOK_SECRET, invalidPayload);

      const response = await request(app)
        .post('/notify')
        .set('X-Signature', signature)
        .send(invalidPayload);

      expect(response.status).toBe(400);
      expect(response.body).toHaveProperty('error');
      expect(response.body.error).toHaveProperty('code', 'INVALID_PAYLOAD');
    });

    it('should reject notification with missing task_id', async () => {
      const invalidPayload = { ...validPayload };
      delete invalidPayload.taskId;

      const signature = generateSignature(WEBHOOK_SECRET, invalidPayload);

      const response = await request(app)
        .post('/notify')
        .set('X-Signature', signature)
        .send(invalidPayload);

      expect(response.status).toBe(400);
      expect(response.body.error).toHaveProperty('code', 'INVALID_PAYLOAD');
    });

    it('should reject notification with missing message', async () => {
      const invalidPayload = { ...validPayload };
      delete invalidPayload.message;

      const signature = generateSignature(WEBHOOK_SECRET, invalidPayload);

      const response = await request(app)
        .post('/notify')
        .set('X-Signature', signature)
        .send(invalidPayload);

      expect(response.status).toBe(400);
      expect(response.body.error).toHaveProperty('code', 'INVALID_PAYLOAD');
    });

    it('should reject notification with missing timestamp', async () => {
      const invalidPayload = { ...validPayload };
      delete invalidPayload.timestamp;

      const signature = generateSignature(WEBHOOK_SECRET, invalidPayload);

      const response = await request(app)
        .post('/notify')
        .set('X-Signature', signature)
        .send(invalidPayload);

      expect(response.status).toBe(400);
      expect(response.body.error).toHaveProperty('code', 'INVALID_PAYLOAD');
    });

    it('should store notification with correct fields', async () => {
      const signature = generateSignature(WEBHOOK_SECRET, validPayload);

      const response = await request(app)
        .post('/notify')
        .set('X-Signature', signature)
        .send(validPayload);

      expect(response.status).toBe(201);

      // Verify notification was stored
      const notificationsResponse = await request(app).get('/notifications');
      const notifications = notificationsResponse.body.data;

      const stored = notifications.find((n) => n.userId === validPayload.userId
        && n.taskId === validPayload.taskId);

      expect(stored).toBeDefined();
      expect(stored).toHaveProperty('id');
      expect(stored).toHaveProperty('userId', validPayload.userId);
      expect(stored).toHaveProperty('taskId', validPayload.taskId);
      expect(stored).toHaveProperty('message', validPayload.message);
      expect(stored).toHaveProperty('timestamp', validPayload.timestamp);
      expect(stored).toHaveProperty('received_at');
    });
  });

  describe('GET /notifications', () => {
    it('should return all notifications', async () => {
      const response = await request(app).get('/notifications');

      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('data');
      expect(response.body).toHaveProperty('meta');
      expect(response.body.meta).toHaveProperty('total');
      expect(Array.isArray(response.body.data)).toBe(true);
    });

    it('should return notifications with correct structure', async () => {
      // First, send a notification
      const payload = {
        userId: 999,
        taskId: 888,
        message: 'Test notification structure',
        timestamp: new Date().toISOString(),
      };

      const signature = generateSignature(WEBHOOK_SECRET, payload);

      await request(app)
        .post('/notify')
        .set('X-Signature', signature)
        .send(payload);

      // Then retrieve all notifications
      const response = await request(app).get('/notifications');

      expect(response.status).toBe(200);
      expect(response.body.data.length).toBeGreaterThan(0);

      const notification = response.body.data.find((n) => n.userId === 999);
      expect(notification).toHaveProperty('id');
      expect(notification).toHaveProperty('userId');
      expect(notification).toHaveProperty('taskId');
      expect(notification).toHaveProperty('message');
      expect(notification).toHaveProperty('timestamp');
      expect(notification).toHaveProperty('received_at');
    });
  });

  describe('404 Handler', () => {
    it('should return 404 for non-existent routes', async () => {
      const response = await request(app).get('/non-existent-route');

      expect(response.status).toBe(404);
      expect(response.body).toHaveProperty('error');
      expect(response.body.error).toHaveProperty('code', 'NOT_FOUND');
    });
  });
});
