require('dotenv').config();
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');
const crypto = require('crypto');
const axios = require('axios');

// Import services and middleware
const redisService = require('./services/redisService');
const { globalLimiter, notificationLimiter, webhookLimiter } = require('./middleware/rateLimiter');
const { validateNotification, validateQueryParams, validateIdParam } = require('./middleware/validation');

const app = express();
const PORT = process.env.PORT || 3001;
const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET || 'your-secret-key-here';
const LARAVEL_API_URL = process.env.LARAVEL_API_URL || 'http://laravel-app:8000';

// Middleware
app.use(helmet({
  contentSecurityPolicy: {
    directives: {
      defaultSrc: ["'self'"],
      styleSrc: ["'self'", "'unsafe-inline'"],
    },
  },
  hsts: {
    maxAge: 31536000,
    includeSubDomains: true,
    preload: true,
  },
}));
app.use(cors());
app.use(morgan('combined'));
app.use(express.json({ limit: '10mb' }));
app.use(globalLimiter); // Apply global rate limiting

// Signature verification middleware
const verifySignature = (req, res, next) => {
  const signature = req.headers['x-signature'];

  if (!signature) {
    return res.status(401).json({
      error: {
        code: 'MISSING_SIGNATURE',
        message: 'X-Signature header is required'
      }
    });
  }

  const body = JSON.stringify(req.body);
  const expectedSignature = `sha256=${crypto
    .createHmac('sha256', WEBHOOK_SECRET)
    .update(body)
    .digest('hex')}`;

  if (signature !== expectedSignature) {
    return res.status(401).json({
      error: {
        code: 'INVALID_SIGNATURE',
        message: 'Invalid webhook signature'
      }
    });
  }

  next();
};

// Laravel Token verification middleware
const verifyToken = async (req, res, next) => {
  const authHeader = req.headers.authorization;
  const token = authHeader?.replace('Bearer ', '');

  if (!token) {
    return res.status(401).json({
      error: {
        code: 'UNAUTHORIZED',
        message: 'Authentication token required'
      }
    });
  }

  try {
    // Verify token with Laravel API
    const response = await axios.get(`${LARAVEL_API_URL}/api/auth/me`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json'
      }
    });

    // Save user data to request
    req.user = response.data.data;
    next();
  } catch (error) {
    return res.status(401).json({
      error: {
        code: 'INVALID_TOKEN',
        message: 'Invalid or expired authentication token'
      }
    });
  }
};

// Routes

// Health check
app.get('/health', async (req, res) => {
  const redisHealthy = await redisService.healthCheck();

  res.json({
    status: redisHealthy ? 'OK' : 'DEGRADED',
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    service: 'task-notification-service',
    redis: redisHealthy ? 'connected' : 'disconnected',
  });
});

// Receive notification (with rate limiting and validation)
app.post('/notify', webhookLimiter, verifySignature, validateNotification, async (req, res) => {
  const {
    userId, taskId, message, timestamp
  } = req.body;

  try {
    // Idempotency check: prevent duplicate notifications for the same task completion
    const idempotencyKey = `webhook:${userId}:${taskId}:${timestamp}`;
    const exists = await redisService.redis.get(idempotencyKey);

    if (exists) {
      // Duplicate webhook detected, return success without creating notification
      return res.status(200).json({
        message: 'Notification already processed (duplicate webhook)',
        notificationId: parseInt(exists, 10),
      });
    }

    // Generate unique notification ID
    const notificationId = await redisService.generateNotificationId();

    // Store idempotency key (expires in 10 minutes)
    await redisService.redis.setex(idempotencyKey, 600, notificationId.toString());

    // Create notification object
    const notification = {
      id: notificationId,
      userId,
      taskId,
      message,
      timestamp,
      status: 'unread',
      isRead: false,
      receivedAt: new Date().toISOString(),
    };

    // Save to Redis
    const saved = await redisService.saveNotification(notification);

    if (!saved) {
      return res.status(500).json({
        error: {
          code: 'STORAGE_ERROR',
          message: 'Failed to save notification',
        },
      });
    }

    res.status(201).json({
      data: {
        message: 'Notification received successfully',
        notification_id: notification.id,
      },
    });
  } catch (error) {
    res.status(500).json({
      error: {
        code: 'INTERNAL_ERROR',
        message: 'Failed to process notification',
      },
    });
  }
});

// Get all notifications (Protected with rate limiting and validation)
app.get('/notifications', notificationLimiter, verifyToken, validateQueryParams, async (req, res) => {
  const { page, limit, status } = req.query;

  try {
    // Get notifications from Redis
    let userNotifications = await redisService.getNotifications(req.user.id, page, limit);

    // Filter by status if provided
    if (status) {
      userNotifications = userNotifications.filter(
        (n) => (status === 'read' && n.isRead) || (status === 'unread' && !n.isRead)
      );
    }

    // Get total count
    const total = await redisService.getNotificationCount(req.user.id);

    res.json({
      data: userNotifications,
      meta: {
        total,
        page,
        limit,
        user_id: req.user.id,
      },
    });
  } catch (error) {
    res.status(500).json({
      error: {
        code: 'INTERNAL_ERROR',
        message: 'Failed to fetch notifications',
      },
    });
  }
});

// Get notification by ID (Protected with validation)
app.get('/notifications/:id', notificationLimiter, verifyToken, validateIdParam, async (req, res) => {
  const { id } = req.params;

  try {
    const notification = await redisService.getNotificationById(id);

    if (!notification) {
      return res.status(404).json({
        error: {
          code: 'NOT_FOUND',
          message: 'Notification not found',
        },
      });
    }

    // Check if notification belongs to the authenticated user
    if (notification.userId !== req.user.id) {
      return res.status(403).json({
        error: {
          code: 'FORBIDDEN',
          message: 'You do not have permission to access this notification',
        },
      });
    }

    res.json({
      data: notification,
    });
  } catch (error) {
    res.status(500).json({
      error: {
        code: 'INTERNAL_ERROR',
        message: 'Failed to fetch notification',
      },
    });
  }
});

// Delete all notifications for authenticated user (Protected)
app.delete('/notifications', notificationLimiter, verifyToken, async (req, res) => {
  try {
    const count = await redisService.deleteAllNotifications(req.user.id);

    res.json({
      message: `${count} notification(s) deleted successfully`,
      data: {
        deleted_count: count,
      },
    });
  } catch (error) {
    res.status(500).json({
      error: {
        code: 'INTERNAL_ERROR',
        message: 'Failed to delete notifications',
      },
    });
  }
});

// Delete notification by ID (Protected with validation)
app.delete('/notifications/:id', notificationLimiter, verifyToken, validateIdParam, async (req, res) => {
  const { id } = req.params;

  try {
    // Get notification first to check ownership
    const notification = await redisService.getNotificationById(id);

    if (!notification) {
      return res.status(404).json({
        error: {
          code: 'NOT_FOUND',
          message: 'Notification not found',
        },
      });
    }

    // Check ownership
    if (notification.userId !== req.user.id) {
      return res.status(403).json({
        error: {
          code: 'FORBIDDEN',
          message: 'You do not have permission to delete this notification',
        },
      });
    }

    // Delete notification
    await redisService.deleteNotification(id, req.user.id);

    res.json({
      message: 'Notification deleted successfully',
      data: notification,
    });
  } catch (error) {
    res.status(500).json({
      error: {
        code: 'INTERNAL_ERROR',
        message: 'Failed to delete notification',
      },
    });
  }
});

// Mark notification as read (Protected with validation)
app.patch('/notifications/:id/read', notificationLimiter, verifyToken, validateIdParam, async (req, res) => {
  const { id } = req.params;

  try {
    const notification = await redisService.getNotificationById(id);

    if (!notification) {
      return res.status(404).json({
        error: {
          code: 'NOT_FOUND',
          message: 'Notification not found',
        },
      });
    }

    // Check ownership
    if (notification.userId !== req.user.id) {
      return res.status(403).json({
        error: {
          code: 'FORBIDDEN',
          message: 'You do not have permission to modify this notification',
        },
      });
    }

    // Update notification
    const updatedNotification = await redisService.updateNotification(id, {
      isRead: true,
      status: 'read',
      readAt: new Date().toISOString(),
    });

    res.json({
      message: 'Notification marked as read',
      data: updatedNotification,
    });
  } catch (error) {
    res.status(500).json({
      error: {
        code: 'INTERNAL_ERROR',
        message: 'Failed to update notification',
      },
    });
  }
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({
    error: {
      code: 'NOT_FOUND',
      message: 'Route not found'
    }
  });
});

// Error handler
app.use((err, req, res, _next) => {
  res.status(500).json({
    error: {
      code: 'INTERNAL_SERVER_ERROR',
      message: 'An unexpected error occurred'
    }
  });
});

// Start server
if (require.main === module) {
  app.listen(PORT, '0.0.0.0', () => {
    // Server started successfully
  });
}

module.exports = app;
