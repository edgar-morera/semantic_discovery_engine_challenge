/**
 * Stress test — ramps up VUs to find the breaking point.
 * Ramp: 1 → 10 → 30 → 50 VUs, then ramp down.
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://nginx';

const QUERIES = [
    'chaleco térmico ciclismo',
    'culote largo mujer',
    'gorra cicloturismo',
    'maillot manga larga',
    'cortavientos ciclismo',
];

export const options = {
    stages: [
        { duration: '30s', target: 10 },
        { duration: '30s', target: 30 },
        { duration: '30s', target: 50 },
        { duration: '20s', target: 0 },
    ],
    thresholds: {
        http_req_failed: ['rate<0.05'],
        http_req_duration: ['p(95)<8000'],
    },
};

export default function () {
    const query = QUERIES[Math.floor(Math.random() * QUERIES.length)];
    const res = http.get(`${BASE_URL}/products/search?q=${encodeURIComponent(query)}&limit=10`);

    check(res, {
        'status 200': (r) => r.status === 200,
    });

    sleep(0.5);
}
