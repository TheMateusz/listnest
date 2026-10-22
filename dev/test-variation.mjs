import assert from 'node:assert/strict';
const base = process.argv[2];
if (!/^http:\/\/localhost:889[1234]$/.test(base || '')) throw new Error('Test URL required');
const cookies = new Map();
async function request(path, fields) {
  const response = await fetch(base + path, { method: fields ? 'POST' : 'GET', redirect: 'manual', headers: { cookie: [...cookies].map(([k,v])=>`${k}=${v}`).join('; ') }, body: fields ? new URLSearchParams(fields) : undefined });
  for (const item of response.headers.getSetCookie()) { const [k,...v]=item.split(';')[0].split('='); cookies.set(k,v.join('=')); }
  return response.text();
}
const page = await request('/product/koszulka-wariantowa/');
const parent = page.match(/data-product_id="(\d+)"/)[1];
const encoded = page.match(/data-product_variations="([^"]+)"/)[1];
const variations = JSON.parse(encoded.replace(/&quot;/g,'"').replace(/&#039;/g,"'").replace(/&amp;/g,'&'));
const variation = variations[0];
const nonce = JSON.parse(await request('/wp-admin/admin-ajax.php', { action: 'listnest_refresh_nonce' })).data.nonce;
const fields = { action: 'listnest_add_to_list', security: nonce, list_key: 'probki', product_id: parent, variation_id: String(variation.variation_id), quantity: '2', attributes: JSON.stringify({...variation.attributes, attribute_rozmiar:'L'}) };
let result = JSON.parse(await request('/wp-admin/admin-ajax.php', fields));
assert.equal(result.success, true, JSON.stringify(result));
console.log('PASS: Variation added through actual AJAX');
let list = await request('/probki/');
assert.ok(list.includes('Koszulka wariantowa') && list.includes('Rozmiar') && list.includes('>L<'), 'Variation and selected attributes persist in session');
console.log('PASS: Variation and selected options persist across requests');
result = JSON.parse(await request('/wp-admin/admin-ajax.php', { ...fields, attributes: JSON.stringify({attribute_rozmiar:'L'}) }));
assert.equal(result.data.count, 1);
console.log('PASS: Equivalent variant selections merge into one row');
list = await request('/probki/');
assert.ok(/value="4"/.test(list), 'Duplicate variation quantities must sum');
console.log('PASS: Variation quantities are summed');
