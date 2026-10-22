import assert from 'node:assert/strict';
const base = process.argv[2];
if (!/^http:\/\/localhost:889[1234]$/.test(base || '')) throw new Error('Dedicated test profile URL required');
function client() {
  const jar = new Map();
  return async (path, fields) => {
    const response = await fetch(base + path, {
      method: fields ? 'POST' : 'GET', redirect: 'manual',
      headers: { cookie: [...jar].map(([key, value]) => `${key}=${value}`).join('; ') },
      body: fields ? new URLSearchParams(fields) : undefined,
    });
    for (const cookie of response.headers.getSetCookie()) {
      const [key, ...value] = cookie.split(';')[0].split('=');
      jar.set(key, value.join('='));
    }
    return { status: response.status, location: response.headers.get('location'), text: await response.text() };
  };
}
const first = client();
const second = client();
let checks = 0;
function check(condition, message) { assert.ok(condition, message); checks++; console.log(`PASS: ${message}`); }
const page = await first('/product/kubek-prosty/');
check(page.status === 200, 'Product page loads');
const product = page.text.match(/data-product-id="(\d+)"/);
check(product, 'Add-to-list button is rendered');
const fresh = JSON.parse((await first('/wp-admin/admin-ajax.php', { action: 'listnest_refresh_nonce' })).text);
const fields = { action: 'listnest_add_to_list', security: fresh.data.nonce, list_key: 'probki', product_id: product[1], quantity: '2' };
let result = await first('/wp-admin/admin-ajax.php', { ...fields, security: 'invalid' });
check(result.status === 403, 'Bad nonce rejected');
result = await first('/wp-admin/admin-ajax.php', { ...fields, 'list_key[]': 'probki' });
check(result.status === 400, 'Array input rejected');
result = await first('/wp-admin/admin-ajax.php', fields);
check(JSON.parse(result.text).success === true, 'Guest can add product');
let list = await first('/probki/');
const nonce = list.text.match(/name="listnest_form_nonce" value="([a-f0-9]+)"/);
check(nonce, 'Inquiry form available for nonempty list');
const isolated = await second('/probki/');
check(!isolated.text.includes('name="listnest_form_nonce"'), 'Second guest cannot see first guest list');
result = await first('/probki/', { listnest_form_list: 'probki', listnest_form_nonce: nonce[1], listnest_name: 'Test', listnest_email: 'bad', _wp_http_referer: '/probki/' });
check(result.status === 200 && result.text.includes('aria-invalid="true"'), 'Validation error accessible without JavaScript');
result = await first('/probki/', { listnest_form_list: 'probki', listnest_form_nonce: nonce[1], listnest_name: 'Test Client', listnest_email: 'test@example.com', listnest_consent: '1', _wp_http_referer: '/probki/' });
check(result.status === 302 && result.location.includes('listnest_sent=probki'), 'Successful submission redirects');
list = await first('/probki/');
check(!list.text.includes('name="listnest_form_nonce"'), 'List cleared after submission');
console.log(`${checks} HTTP checks passed`);
