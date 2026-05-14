/**
 * Load test — sustained normal load on the search endpoint.
 * 10 VUs for 60 seconds.
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://nginx';

const QUERIES = [
    'chaleco térmico ciclismo hombre',
    'culote largo mujer invierno',
    'gorra resistente al agua',
    'maillot manga larga ciclismo',
    'guantes ciclismo invierno',
    'cortavientos hombre azul',
    'calcetines técnicos ciclismo',
    'camiseta técnica manga corta',
];

export const options = {
    vus: 10,
    duration: '60s',
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(50)<2000', 'p(95)<5000'],
    },
};

export default function () {
    const query = QUERIES[Math.floor(Math.random() * QUERIES.length)];
    const res = http.get(`${BASE_URL}/products/search?q=${encodeURIComponent(query)}&limit=10`);

    check(res, {
        'status 200': (r) => r.status === 200,
        'returns results': (r) => Array.isArray(JSON.parse(r.body)) && JSON.parse(r.body).length > 0,
    });

    sleep(1);
}
