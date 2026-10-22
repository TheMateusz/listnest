import assert from 'node:assert/strict';
const [base, mode] = process.argv.slice(2);
if (base !== 'http://localhost:8893' || !['stored', 'lost'].includes(mode)) throw new Error('Dedicated test environment required');
const cookies = new Map();
async function request(path, fields) {
  const response = await fetch(base + path, {method: fields ? 'POST' : 'GET',redirect:'manual',headers:{cookie:[...cookies].map(([k,v])=>`${k}=${v}`).join('; ')},body:fields ? new URLSearchParams(fields):undefined});
  for(const cookie of response.headers.getSetCookie()){const [k,...v]=cookie.split(';')[0].split('=');cookies.set(k,v.join('='));}
  return {response,text:await response.text()};
}
const product=(await request('/product/kubek-prosty/')).text.match(/data-product-id="(\d+)"/)[1];
const nonce=JSON.parse((await request('/wp-admin/admin-ajax.php',{action:'listnest_refresh_nonce'})).text).data.nonce;
assert.equal(JSON.parse((await request('/wp-admin/admin-ajax.php',{action:'listnest_add_to_list',security:nonce,list_key:'probki',product_id:product})).text).success,true);
const page=await request('/probki/');
const formNonce=page.text.match(/name="listnest_form_nonce" value="([a-f0-9]+)"/)[1];
const result=await request('/probki/',{listnest_form_list:'probki',listnest_form_nonce:formNonce,listnest_name:'Failure test',listnest_email:'failure-test@example.com',listnest_consent:'1',_wp_http_referer:'/probki/'});
if(mode==='stored'){
  assert.equal(result.response.status,302);
  assert.ok(!(await request('/probki/')).text.includes('name="listnest_form_nonce"'));
  console.log('PASS: Failed mail with saved inquiry is acknowledged and list is cleared');
}else{
  assert.equal(result.response.status,200);
  assert.ok(result.text.includes('Your inquiry could not be sent.'));
  assert.ok((await request('/probki/')).text.includes('name="listnest_form_nonce"'));
  console.log('PASS: Mail and storage failure show error and preserve list for retry');
}
