/**
 * Smoke test — verifies all 3 endpoints respond correctly under minimal load.
 * 1 VU, ~10 iterations.
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://nginx';

export const options = {
    vus: 1,
    iterations: 10,
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<5000'],
    },
};

export default function () {
    // POST /products
    const createRes = http.post(
        `${BASE_URL}/products`,
        JSON.stringify({ name: 'Smoke Test Chaleco', semanticDescription: 'Chaleco de ciclismo para test de humo.' }),
        { headers: { 'Content-Type': 'application/json' } },
    );
    check(createRes, {
        'POST /products → 201': (r) => r.status === 201,
        'response has id': (r) => JSON.parse(r.body).id !== undefined,
    });

    const productId = JSON.parse(createRes.body).id;

    // POST /products/{id}/index
    const indexRes = http.post(`${BASE_URL}/products/${productId}/index`);
    check(indexRes, {
        'POST /products/{id}/index → 204': (r) => r.status === 204,
    });

    // GET /products/search
    const searchRes = http.get(`${BASE_URL}/products/search?q=chaleco+ciclismo+hombre&limit=5`);
    check(searchRes, {
        'GET /products/search → 200': (r) => r.status === 200,
        'search returns array': (r) => Array.isArray(JSON.parse(r.body)),
    });

    sleep(1);
}
