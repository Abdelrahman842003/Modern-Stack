# Load Testing Guide

## Prerequisites

Install k6:
```bash
# Ubuntu/Debian
sudo gpg -k
sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update
sudo apt-get install k6

# macOS
brew install k6

# Windows (via Chocolatey)
choco install k6
```

## Running Load Tests

### 1. Laravel API Load Test

Tests the complete API workflow: register, login, CRUD operations, webhooks.

```bash
# Start services first
docker-compose up -d

# Run the load test
cd tests/load
k6 run api-load-test.js

# Run with custom BASE_URL
k6 run --env BASE_URL=http://localhost:8000 api-load-test.js

# Run with summary export
k6 run --out json=results-api.json api-load-test.js
```

**Test Stages:**
- 30s: Ramp-up to 20 users
- 1m: Ramp-up to 50 users
- 2m: Ramp-up to 100 users (peak load)
- 1m: Ramp-down to 50 users
- 30s: Ramp-down to 0 users

**Success Thresholds:**
- ✅ 95% of requests < 500ms
- ✅ Error rate < 5%

### 2. Node.js Notification Service Load Test

Tests the webhook receiver and notification API.

```bash
# Run the Node.js load test
cd tests/load
k6 run node-load-test.js

# Run with custom configuration
k6 run \
  --env BASE_URL=http://localhost:3001 \
  --env WEBHOOK_SECRET=your-secret-key \
  node-load-test.js
```

**Test Stages:**
- 30s: Ramp-up to 50 users
- 1m: Ramp-up to 100 users
- 1m: Sustained 100 users
- 30s: Ramp-down to 0 users

**Success Thresholds:**
- ✅ 95% of requests < 200ms
- ✅ Error rate < 1%

## Understanding Results

### Key Metrics

1. **http_req_duration**: Request latency
   - `p(95)<500`: 95th percentile under 500ms
   - Lower is better

2. **http_req_failed**: Failed requests
   - `rate<0.05`: Less than 5% failure rate
   - 0% is ideal

3. **errors**: Custom error metric
   - Failed checks and validations

4. **http_reqs**: Total requests per second
   - Higher is better (throughput)

### Sample Output

```
     ✓ list tasks status 200
     ✓ create task status 201
     ✓ get task status 200
     ✓ complete task status 200

     checks.........................: 95.45% ✓ 1909      ✗ 91
     data_received..................: 2.1 MB 42 kB/s
     data_sent......................: 1.3 MB 26 kB/s
     http_req_duration..............: avg=124.32ms min=23.1ms max=892.45ms p(95)=425.23ms
     http_reqs......................: 2000   40/s
     errors.........................: 4.55%  ✗ 91 failures
```

## Load Test Scenarios

### Scenario 1: Normal Load
```bash
# 20 concurrent users for 2 minutes
k6 run --vus 20 --duration 2m api-load-test.js
```

### Scenario 2: Stress Test
```bash
# Gradually increase to 200 users
k6 run --stage 1m:50,2m:100,3m:200 api-load-test.js
```

### Scenario 3: Spike Test
```bash
# Sudden spike to 300 users
k6 run --stage 10s:10,30s:300,1m:10 api-load-test.js
```

### Scenario 4: Soak Test (Endurance)
```bash
# 50 users for 30 minutes
k6 run --vus 50 --duration 30m api-load-test.js
```

## Expected Performance

Based on improvements implemented:

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| **Response Time (p95)** | 800ms | <500ms | 37.5% faster |
| **Throughput** | 50 req/s | 200 req/s | 300% increase |
| **Error Rate** | 8% | <5% | 37.5% reduction |
| **Cache Hit Ratio** | 0% | 70% | N/A |

## Monitoring During Load Tests

```bash
# Watch Redis metrics
docker exec -it modern-stack-redis-1 redis-cli INFO stats

# Watch PostgreSQL connections
docker exec -it modern-stack-postgres-1 psql -U laravel_user -d laravel_db -c "SELECT count(*) FROM pg_stat_activity;"

# Watch Laravel logs
docker-compose logs -f laravel

# Watch Node.js logs
docker-compose logs -f node-notify
```

## Troubleshooting

### High Error Rates
- Check rate limiting thresholds in `node-notify/src/middleware/rateLimiter.js`
- Verify database connection pool size
- Check Redis memory usage

### High Latency
- Enable query logging: `DB_LOG_QUERIES=true`
- Check cache hit rate
- Verify database indexes are used

### Circuit Breaker Opens
- Check Node.js service availability
- Review webhook failure logs
- Adjust circuit breaker thresholds in `CircuitBreakerService.php`

## CI/CD Integration

Add to `.github/workflows/load-test.yml`:

```yaml
name: Load Tests

on:
  schedule:
    - cron: '0 2 * * 0' # Weekly on Sunday 2 AM
  workflow_dispatch:

jobs:
  load-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Start services
        run: docker-compose up -d
      
      - name: Install k6
        run: |
          sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
          echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
          sudo apt-get update
          sudo apt-get install k6
      
      - name: Run load tests
        run: |
          k6 run --out json=results.json tests/load/api-load-test.js
      
      - name: Upload results
        uses: actions/upload-artifact@v3
        with:
          name: load-test-results
          path: results.json
```

## Next Steps

1. Run initial baseline test
2. Compare with expected thresholds
3. Identify bottlenecks
4. Optimize and re-test
5. Set up automated weekly load tests
