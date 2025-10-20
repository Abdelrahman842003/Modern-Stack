const crypto = require('crypto');

/**
 * Generate HMAC SHA-256 signature
 * @param {string} secret - Webhook secret
 * @param {object} payload - Request body
 * @returns {string} - Signature in format: sha256=hex
 */
function generateSignature(secret, payload) {
  const body = JSON.stringify(payload);
  const hash = crypto
    .createHmac('sha256', secret)
    .update(body)
    .digest('hex');
  return `sha256=${hash}`;
}

/**
 * Verify HMAC SHA-256 signature
 * @param {string} signature - Received signature
 * @param {string} secret - Webhook secret
 * @param {object} payload - Request body
 * @returns {boolean} - True if valid
 */
function verifySignature(signature, secret, payload) {
  const expectedSignature = generateSignature(secret, payload);
  return signature === expectedSignature;
}

module.exports = {
  generateSignature,
  verifySignature,
};
