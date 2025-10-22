const Redis = require('ioredis');

// Redis client with retry strategy
const redis = new Redis({
  host: process.env.REDIS_HOST || 'redis',
  port: process.env.REDIS_PORT || 6379,
  retryStrategy: (times) => {
    const delay = Math.min(times * 50, 2000);
    return delay;
  },
  maxRetriesPerRequest: 3,
  enableReadyCheck: true,
  lazyConnect: false,
});

// Event handlers
redis.on('error', (err) => {
  // Silent error handling
});

/**
 * Save notification to Redis
 * @param {Object} notification - Notification object
 * @returns {Promise<boolean>}
 */
async function saveNotification(notification) {
  try {
    const key = `notification:${notification.id}`;
    const userKey = `notifications:user:${notification.userId}`;
    
    // Store notification data (expires in 30 days)
    await redis.setex(key, 30 * 24 * 60 * 60, JSON.stringify(notification));
    
    // Add to user's sorted set (sorted by timestamp)
    await redis.zadd(userKey, Date.now(), notification.id.toString());
    
    // Set expiry on user's notification list
    await redis.expire(userKey, 30 * 24 * 60 * 60);
    
    return true;
  } catch (error) {
    return false;
  }
}

/**
 * Get notifications for a user with pagination
 * @param {number} userId - User ID
 * @param {number} page - Page number (default: 1)
 * @param {number} limit - Items per page (default: 10)
 * @returns {Promise<Array>}
 */
async function getNotifications(userId, page = 1, limit = 10) {
  try {
    const userKey = `notifications:user:${userId}`;
    const start = (page - 1) * limit;
    const end = start + limit - 1;
    
    // Get notification IDs from sorted set (newest first)
    const notificationIds = await redis.zrevrange(userKey, start, end);
    
    if (notificationIds.length === 0) {
      return [];
    }
    
    // Get all notifications
    const notifications = await Promise.all(
      notificationIds.map(async (id) => {
        const data = await redis.get(`notification:${id}`);
        return data ? JSON.parse(data) : null;
      })
    );
    
    // Filter out null values (expired notifications)
    return notifications.filter(n => n !== null);
  } catch (error) {
    return [];
  }
}

/**
 * Get a single notification by ID
 * @param {string|number} notificationId - Notification ID
 * @returns {Promise<Object|null>}
 */
async function getNotificationById(notificationId) {
  try {
    const key = `notification:${notificationId}`;
    const data = await redis.get(key);
    return data ? JSON.parse(data) : null;
  } catch (error) {
    return null;
  }
}

/**
 * Delete notification
 * @param {string|number} notificationId - Notification ID
 * @param {number} userId - User ID
 * @returns {Promise<boolean>}
 */
async function deleteNotification(notificationId, userId) {
  try {
    const key = `notification:${notificationId}`;
    const userKey = `notifications:user:${userId}`;
    
    // Delete notification data
    await redis.del(key);
    
    // Remove from user's sorted set
    await redis.zrem(userKey, notificationId.toString());
    
    return true;
  } catch (error) {
    return false;
  }
}

/**
 * Delete all notifications for a user
 * @param {number} userId - User ID
 * @returns {Promise<number>} Number of deleted notifications
 */
async function deleteAllNotifications(userId) {
  try {
    const userKey = `notifications:user:${userId}`;
    
    // Get all notification IDs
    const notificationIds = await redis.zrange(userKey, 0, -1);
    
    if (notificationIds.length === 0) {
      return 0;
    }
    
    // Delete all notifications
    const keys = notificationIds.map(id => `notification:${id}`);
    await redis.del(...keys);
    
    // Delete user's sorted set
    await redis.del(userKey);
    
    return notificationIds.length;
  } catch (error) {
    return 0;
  }
}

/**
 * Update notification (e.g., mark as read)
 * @param {string|number} notificationId - Notification ID
 * @param {Object} updates - Fields to update
 * @returns {Promise<Object|null>}
 */
async function updateNotification(notificationId, updates) {
  try {
    const key = `notification:${notificationId}`;
    const data = await redis.get(key);
    
    if (!data) {
      return null;
    }
    
    const notification = JSON.parse(data);
    const updatedNotification = { ...notification, ...updates };
    
    // Get remaining TTL and preserve it
    const ttl = await redis.ttl(key);
    if (ttl > 0) {
      await redis.setex(key, ttl, JSON.stringify(updatedNotification));
    } else {
      await redis.set(key, JSON.stringify(updatedNotification));
    }
    
    return updatedNotification;
  } catch (error) {
    return null;
  }
}

/**
 * Get total count of notifications for a user
 * @param {number} userId - User ID
 * @returns {Promise<number>}
 */
async function getNotificationCount(userId) {
  try {
    const userKey = `notifications:user:${userId}`;
    return await redis.zcard(userKey);
  } catch (error) {
    return 0;
  }
}

/**
 * Generate unique notification ID
 * @returns {Promise<number>}
 */
async function generateNotificationId() {
  try {
    return await redis.incr('notification:id:counter');
  } catch (error) {
    // Fallback to timestamp-based ID
    return Date.now();
  }
}

/**
 * Health check for Redis
 * @returns {Promise<boolean>}
 */
async function healthCheck() {
  try {
    await redis.ping();
    return true;
  } catch (error) {
    return false;
  }
}

module.exports = {
  redis,
  saveNotification,
  getNotifications,
  getNotificationById,
  deleteNotification,
  deleteAllNotifications,
  updateNotification,
  getNotificationCount,
  generateNotificationId,
  healthCheck,
};
