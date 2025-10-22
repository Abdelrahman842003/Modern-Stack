const Joi = require('joi');

/**
 * Validation schema for notification payload
 */
const notificationSchema = Joi.object({
  userId: Joi.number().integer().positive().required()
    .messages({
      'number.base': 'userId must be a number',
      'number.integer': 'userId must be an integer',
      'number.positive': 'userId must be positive',
      'any.required': 'userId is required',
    }),
  
  taskId: Joi.number().integer().positive().required()
    .messages({
      'number.base': 'taskId must be a number',
      'number.integer': 'taskId must be an integer',
      'number.positive': 'taskId must be positive',
      'any.required': 'taskId is required',
    }),
  
  message: Joi.string().min(1).max(500).required()
    .messages({
      'string.base': 'message must be a string',
      'string.empty': 'message cannot be empty',
      'string.min': 'message must be at least 1 character long',
      'string.max': 'message cannot exceed 500 characters',
      'any.required': 'message is required',
    }),
  
  timestamp: Joi.string().isoDate().required()
    .messages({
      'string.base': 'timestamp must be a string',
      'string.isoDate': 'timestamp must be a valid ISO 8601 date',
      'any.required': 'timestamp is required',
    }),
});

/**
 * Middleware to validate notification payload
 */
const validateNotification = (req, res, next) => {
  const { error, value } = notificationSchema.validate(req.body, {
    abortEarly: false, // Return all errors, not just the first one
    stripUnknown: true, // Remove unknown fields
  });

  if (error) {
    const errors = error.details.map(detail => ({
      field: detail.path.join('.'),
      message: detail.message,
    }));

    return res.status(400).json({
      error: {
        code: 'VALIDATION_ERROR',
        message: 'Invalid request payload',
        details: errors,
      },
    });
  }

  // Replace request body with validated and sanitized data
  req.body = value;
  next();
};

/**
 * Query parameter validation schema
 */
const queryParamsSchema = Joi.object({
  page: Joi.number().integer().min(1).default(1)
    .messages({
      'number.base': 'page must be a number',
      'number.integer': 'page must be an integer',
      'number.min': 'page must be at least 1',
    }),
  
  limit: Joi.number().integer().min(1).max(100).default(10)
    .messages({
      'number.base': 'limit must be a number',
      'number.integer': 'limit must be an integer',
      'number.min': 'limit must be at least 1',
      'number.max': 'limit cannot exceed 100',
    }),
  
  status: Joi.string().valid('read', 'unread').optional()
    .messages({
      'string.base': 'status must be a string',
      'any.only': 'status must be either "read" or "unread"',
    }),
});

/**
 * Middleware to validate query parameters
 */
const validateQueryParams = (req, res, next) => {
  const { error, value } = queryParamsSchema.validate(req.query, {
    abortEarly: false,
    stripUnknown: true,
  });

  if (error) {
    const errors = error.details.map(detail => ({
      field: detail.path.join('.'),
      message: detail.message,
    }));

    return res.status(400).json({
      error: {
        code: 'VALIDATION_ERROR',
        message: 'Invalid query parameters',
        details: errors,
      },
    });
  }

  // Replace query with validated data
  req.query = value;
  next();
};

/**
 * ID parameter validation
 */
const validateIdParam = (req, res, next) => {
  const id = parseInt(req.params.id, 10);

  if (Number.isNaN(id) || id < 1) {
    return res.status(400).json({
      error: {
        code: 'INVALID_ID',
        message: 'ID must be a positive integer',
      },
    });
  }

  req.params.id = id;
  next();
};

module.exports = {
  validateNotification,
  validateQueryParams,
  validateIdParam,
  notificationSchema,
  queryParamsSchema,
};
