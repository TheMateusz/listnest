import assert from 'node:assert/strict';
const base = process.argv[2];
if (!/^http:\/\/localhost:889[1234]$/.test(base || '')) throw new Error('Test URL required');
const jar = new Map();
async function request(path, body) {
  const response = await fetch(base + path, {
    method: body ? 'POST' : 'GET', redirect: 'manual',
    headers: { cookie: [...jar].map(([k, v]) => `${k}=${v}`).join('; ') }, body,
  });
  for (const cookie of response.headers.getSetCookie()) {
    const [key, ...value] = cookie.split(';')[0].split('=');
    jar.set(key, value.join('='));
  }
  return { response, text: await response.text() };
}
const product = (await request('/product/kubek-prosty/')).text.match(/data-product-id="(\d+)"/)[1];
const fresh = JSON.parse((await request('/wp-admin/admin-ajax.php', new URLSearchParams({ action: 'listnest_refresh_nonce' }))).text);
const add = JSON.parse((await request('/wp-admin/admin-ajax.php', new URLSearchParams({ action: 'listnest_add_to_list', security: fresh.data.nonce, list_key: 'zapytanie', product_id: product }))).text);
assert.equal(add.success, true);
let html = (await request('/zapytanie/')).text;
const id = html.match(/name="_wpcf7" value="(\d+)"/)[1];
function fields() {
  const data = new FormData();
  for (const input of html.matchAll(/<input\b[^>]*type="hidden"[^>]*>/g)) {
    const name = input[0].match(/name="([^"]+)"/);
    const value = input[0].match(/value="([^"]*)"/);
    if (name?.[1].startsWith('_wpcf7') || name?.[1] === '_wpnonce' || name?.[1] === 'listnest_cf7_list') data.set(name[1], value?.[1] || '');
  }
  data.set('your-name', 'CF7 Test');
  data.set('your-email', 'cf7-test@example.com');
  return data;
}
let result = JSON.parse((await request(`/wp-json/contact-form-7/v1/contact-forms/${id}/feedback`, fields())).text);
assert.equal(result.status, 'mail_sent', JSON.stringify(result));
console.log('PASS: CF7 REST sends guest list');
html = (await request('/zapytanie/')).text;
assert.ok(html.includes('No products on the list.'));
console.log('PASS: CF7 clears list after successful mail');
result = JSON.parse((await request(`/wp-json/contact-form-7/v1/contact-forms/${id}/feedback`, fields())).text);
assert.equal(result.status, 'validation_failed', JSON.stringify(result));
console.log('PASS: CF7 rejects empty list');
await request('/wp-login.php');
const login = await request('/wp-login.php', new URLSearchParams({ log: 'admin', pwd: 'password', testcookie: '1', 'wp-submit': 'Log In', redirect_to: base + '/' }));
assert.equal(login.response.status, 302, 'Test admin login');
const authNonce = JSON.parse((await request('/wp-admin/admin-ajax.php', new URLSearchParams({ action: 'listnest_refresh_nonce' }))).text).data.nonce;
assert.equal(JSON.parse((await request('/wp-admin/admin-ajax.php', new URLSearchParams({ action: 'listnest_add_to_list', security: authNonce, list_key: 'zapytanie', product_id: product }))).text).success, true);
html = (await request('/zapytanie/')).text;
result = JSON.parse((await request(`/wp-json/contact-form-7/v1/contact-forms/${id}/feedback`, fields())).text);
assert.equal(result.status, 'mail_sent', JSON.stringify(result));
console.log('PASS: CF7 REST sends logged-in customer list');
html = (await request('/zapytanie/')).text;
assert.ok(html.includes('No products on the list.'));
console.log('PASS: Logged-in CF7 list cleared');
