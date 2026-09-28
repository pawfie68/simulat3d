import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import net from 'node:net';
import { spawn, spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../', import.meta.url));
const php = process.env.PHP_BIN || 'php';
test('server-enforced beta authentication, session expiry and private downloads', async t => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'simulat3d-test-'));
  const hash = spawnSync(php, ['-r', 'echo password_hash("test-only-password", PASSWORD_DEFAULT);'], { encoding: 'utf8' });
  assert.equal(hash.status, 0, 'PHP 8.2+ is required to run the security tests.');
  fs.writeFileSync(path.join(dir, 'config.php'), `<?php return ['password_hash'=>'${hash.stdout}', 'rate_secret'=>'${'x'.repeat(64)}', 'version'=>'2.24'];`);
  fs.mkdirSync(path.join(dir, 'downloads'));
  fs.writeFileSync(path.join(dir, 'downloads', 'SKALARKIe_MSFSInterface_v2.24.zip'), 'private-test-package');
  const port = await new Promise(resolve => { const s = net.createServer(); s.listen(0, '127.0.0.1', () => { const p = s.address().port; s.close(() => resolve(p)); }); });
  const child = spawn(php, ['-S', `127.0.0.1:${port}`, path.join(root, 'server/router.php')], {
    cwd:root, env: { ...process.env, SIMULAT3D_PRIVATE_DIR: dir, SIMULAT3D_LOCAL:'1' }, stdio: 'ignore'
  });
  let spawnError; child.on('error', e => { spawnError=e; });
  t.after(async () => { child.kill(); await new Promise(resolve => { if (child.exitCode !== null) resolve(); else child.once('exit', resolve); }); fs.rmSync(dir,{recursive:true,force:true}); });
  const base = `http://127.0.0.1:${port}`;
  for(let i=0;i<60;i++) { try { await fetch(base+'/api/beta.php'); break; } catch { if(spawnError) throw spawnError; await new Promise(r=>setTimeout(r,50)); } }
  const get = (action, cookie='') => fetch(base+'/api/beta.php?action='+action,{headers: {Cookie:cookie}});
  const post = (action, body, cookie='', origin='http://127.0.0.1:4321') => fetch(base+'/api/beta.php?action='+action, {method:'POST',headers:{Origin:origin,'Content-Type':'application/json','X-Simulat3D':'beta',Cookie:cookie},body:JSON.stringify(body)});
  let r = await get('status');
  assert.deepEqual(await r.json(), {authenticated:false});
  assert.equal(r.headers.get('set-cookie'),null,'Anonymous visits must not create a session cookie.');
  assert.equal((await get('download')).status,401);
  assert.equal((await fetch(base+'/downloads/SKALARKIe_MSFSInterface_v2.24.zip')).status,404);
  assert.equal((await post('login',{password:'test-only-password'},'','https://evil.example')).status,403);
  assert.equal((await post('login',{password:'wrong'})).status,401);
  assert.equal((await post('login',{password:{value:'bad'}})).status,400);
  r=await post('login',{password:'test-only-password'});
  assert.equal(r.status,200);
  const setCookie=r.headers.get('set-cookie');
  assert.match(setCookie,/HttpOnly/i); assert.match(setCookie,/SameSite=Strict/i); assert.match(setCookie,/path=\/api\//i); assert.match(setCookie,/Max-Age=604800/i);
  const cookie=setCookie.split(';')[0];
  r=await get('status',cookie); assert.deepEqual(await r.json(),{authenticated:true});
  r=await get('download',cookie); assert.equal(r.status,200); assert.equal(await r.text(),'private-test-package');
  assert.match(r.headers.get('cache-control'),/no-store/);
  assert.equal((await post('logout',{},cookie)).status,200);
  assert.equal((await get('download',cookie)).status,401,'A copied cookie must fail after logout.');
  r=await post('login',{password:'test-only-password'});
  const cookie2=r.headers.get('set-cookie').split(';')[0];
  const sessionFile=path.join(dir,'sessions','sess_'+cookie2.split('=')[1]);
  const state=fs.readFileSync(sessionFile,'utf8').replace(/expires\|i:\d+;/,'expires|i:1;');
  fs.writeFileSync(sessionFile,state);
  const cleanup=spawnSync(php,[path.join(root,'server/cleanup.php')],{env:{...process.env,SIMULAT3D_PRIVATE_DIR:dir},encoding:'utf8'});
  assert.equal(cleanup.status,0);
  assert.equal(fs.existsSync(sessionFile),false,'Cleanup must use session expiry even when the file was recently accessed.');
  assert.equal((await get('download',cookie2)).status,401,'Expired sessions must fail server-side.');
  r=await post('login',{password:'test-only-password'});
  const cookie3=r.headers.get('set-cookie').split(';')[0];
  const configFile=path.join(dir,'config.php');
  fs.writeFileSync(configFile,fs.readFileSync(configFile,'utf8').replace(hash.stdout, hash.stdout+'changed'));
  assert.equal((await get('download',cookie3)).status,401,'Password rotation must revoke previous sessions.');
  fs.writeFileSync(configFile,`<?php return ['password_hash'=>'${hash.stdout}', 'rate_secret'=>'${'x'.repeat(64)}', 'version'=>'2.24'];`);
  for(let i=0;i<10;i++) r=await post('login',{password:'wrong'});
  assert.equal(r.status,429,'Repeated attempts must be rate limited.');
  assert.ok(Number(r.headers.get('retry-after'))>0);
  fs.unlinkSync(configFile);
  assert.equal((await get('status')).status,503,'Missing configuration must fail closed.');
});

test('public source contains no legacy password or external fonts, and legacy ZIP access is denied',()=>{
  for(const page of ['index.astro','msfs-beta.astro']) {
    const text=fs.readFileSync(path.join(root,'src/pages',page),'utf8');
    assert.doesNotMatch(text,/fonts\.googleapis\.com|fonts\.gstatic\.com|BetaTest2026/);
  }
  assert.match(fs.readFileSync(path.join(root,'public/downloads/.htaccess'),'utf8'),/Require all denied/);
  const endpoint=fs.readFileSync(path.join(root,'public/api/beta.php'),'utf8');
  assert.match(endpoint,/secure' => !\$local/);
});
