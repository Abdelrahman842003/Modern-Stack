const { generateSignature, verifySignature } = require('../src/utils/signature');

describe('Signature Utility', () => {
  const SECRET = 'test-secret-key';
  const payload = {
    user_id: 1,
    task_id: 123,
    message: 'Test notification',
    timestamp: '2025-10-20T12:00:00Z',
  };

  describe('generateSignature', () => {
    it('should generate a valid SHA-256 signature', () => {
      const signature = generateSignature(SECRET, payload);
      
      expect(signature).toMatch(/^sha256=[a-f0-9]{64}$/);
    });

    it('should generate consistent signatures for same input', () => {
      const sig1 = generateSignature(SECRET, payload);
      const sig2 = generateSignature(SECRET, payload);
      
      expect(sig1).toBe(sig2);
    });

    it('should generate different signatures for different payloads', () => {
      const payload2 = { ...payload, message: 'Different message' };
      
      const sig1 = generateSignature(SECRET, payload);
      const sig2 = generateSignature(SECRET, payload2);
      
      expect(sig1).not.toBe(sig2);
    });

    it('should generate different signatures for different secrets', () => {
      const sig1 = generateSignature(SECRET, payload);
      const sig2 = generateSignature('different-secret', payload);
      
      expect(sig1).not.toBe(sig2);
    });
  });

  describe('verifySignature', () => {
    it('should verify a valid signature', () => {
      const signature = generateSignature(SECRET, payload);
      const isValid = verifySignature(signature, SECRET, payload);
      
      expect(isValid).toBe(true);
    });

    it('should reject an invalid signature', () => {
      const isValid = verifySignature('sha256=invalid', SECRET, payload);
      
      expect(isValid).toBe(false);
    });

    it('should reject signature with wrong secret', () => {
      const signature = generateSignature(SECRET, payload);
      const isValid = verifySignature(signature, 'wrong-secret', payload);
      
      expect(isValid).toBe(false);
    });

    it('should reject signature for modified payload', () => {
      const signature = generateSignature(SECRET, payload);
      const modifiedPayload = { ...payload, message: 'Modified' };
      const isValid = verifySignature(signature, SECRET, modifiedPayload);
      
      expect(isValid).toBe(false);
    });

    it('should handle malformed signatures gracefully', () => {
      const isValid = verifySignature('invalid-format', SECRET, payload);
      
      expect(isValid).toBe(false);
    });
  });
});
