import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  scenarios: { reader: { executor: 'constant-arrival-rate', rate: 300, timeUnit: '1s', duration: '10m', preAllocatedVUs: 300, maxVUs: 3000 } },
  thresholds: { http_req_failed: ['rate<0.001'], http_req_duration: ['p(95)<500'] },
};

const base = __ENV.READER_BASE_URL;
if (!base) throw new Error('READER_BASE_URL is required');

export default function () {
  const roll = Math.random();
  const path = roll < 0.80 ? '/reader-api/v1/bootstrap' : roll < 0.95 ? '/reader-api/v1/favorites/292225' : '/reader-api/v1/threads/292225/comments';
  const response = http.get(`${base}${path}`, { redirects: 0, tags: { reader_route: path } });
  check(response, { 'bounded Reader response': item => [200, 401, 404, 429, 503].includes(item.status), 'private response': item => /no-store/.test(item.headers['Cache-Control'] || '') });
  sleep(0.1);
}
