module.exports = {
  env: {
    node: true,
    es2021: true,
    jest: true
  },
  extends: 'airbnb-base',
  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module'
  },
  rules: {
    'no-console': 'off',
    'consistent-return': 'off',
    'comma-dangle': ['error', 'never']
  }
};
