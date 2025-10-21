require('dotenv').config();
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');
const crypto = require('crypto');

const app = express();
const PORT = process.env.PORT || 3001;
const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET || 'your-secret-key-here';

// In-memory storage for notifications
const notifications = [];

// Middleware
app.use(helmet());
app.use(cors());
app.use(morgan('combined'));
app.use(express.json());

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
    console.error('Invalid signature:', {
      received: signature,
      expected: expectedSignature
    });
    return res.status(401).json({
      error: {
        code: 'INVALID_SIGNATURE',
        message: 'Invalid webhook signature'
      }
    });
  }

  next();
};

// Routes

// Health check
app.get('/health', (req, res) => {
  res.json({
    status: 'OK',
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    service: 'task-notification-service',
  });
});

// Receive notification
app.post('/notify', verifySignature, (req, res) => {
  const {
    userId, taskId, message, timestamp
  } = req.body;

  // Validation
  if (!userId || !taskId || !message || !timestamp) {
    return res.status(400).json({
      error: {
        code: 'INVALID_PAYLOAD',
        message: 'Missing required fields: userId, taskId, message, timestamp',
      },
    });
  }

  // Store notification
  const notification = {
    id: notifications.length + 1,
    userId,
    taskId,
    message,
    timestamp,
    received_at: new Date().toISOString(),
  };

  notifications.push(notification);

  console.log('✅ Notification received:', notification);

  res.status(201).json({
    data: {
      message: 'Notification received successfully',
      notification_id: notification.id,
    },
  });
});

// Get all notifications
app.get('/notifications', (req, res) => {
  const { userId, status } = req.query;
  
  let filteredNotifications = notifications;
  
  // Filter by userId if provided
  if (userId) {
    filteredNotifications = filteredNotifications.filter(
      n => n.userId == userId
    );
  }
  
  // Filter by status if provided
  if (status) {
    filteredNotifications = filteredNotifications.filter(
      n => n.status === status
    );
  }
  
  res.json({
    data: filteredNotifications,
    meta: {
      total: filteredNotifications.length
    }
  });
});

// Get notification by ID
app.get('/notifications/:id', (req, res) => {
  const id = parseInt(req.params.id);
  const notification = notifications.find(n => n.id === id);
  
  if (!notification) {
    return res.status(404).json({
      error: {
        code: 'NOT_FOUND',
        message: 'Notification not found'
      }
    });
  }
  
  res.json({
    data: notification
  });
});

// Delete all notifications
app.delete('/notifications', (req, res) => {
  const count = notifications.length;
  notifications.length = 0;  // Clear array
  
  console.log(`🗑️  Deleted ${count} notifications`);
  
  res.json({
    data: {
      message: `${count} notification(s) deleted successfully`,
      deleted_count: count
    }
  });
});

// Delete notification by ID
app.delete('/notifications/:id', (req, res) => {
  const id = parseInt(req.params.id);
  const index = notifications.findIndex(n => n.id === id);
  
  if (index === -1) {
    return res.status(404).json({
      error: {
        code: 'NOT_FOUND',
        message: 'Notification not found'
      }
    });
  }
  
  const deleted = notifications.splice(index, 1)[0];
  console.log(`🗑️  Deleted notification #${id}`);
  
  res.json({
    data: {
      message: 'Notification deleted successfully',
      deleted: deleted
    }
  });
});

// Mark notification as read
app.patch('/notifications/:id/read', (req, res) => {
  const id = parseInt(req.params.id);
  const notification = notifications.find(n => n.id === id);
  
  if (!notification) {
    return res.status(404).json({
      error: {
        code: 'NOT_FOUND',
        message: 'Notification not found'
      }
    });
  }
  
  notification.status = 'read';
  notification.read_at = new Date().toISOString();
  
  console.log(`✅ Notification #${id} marked as read`);
  
  res.json({
    data: notification
  });
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
  console.error('Error:', err);
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
    console.log(`🚀 Notification service running on port ${PORT}`);
    console.log(`📡 Webhook secret: ${WEBHOOK_SECRET.substring(0, 10)}...`);
  });
}

module.exports = app;
