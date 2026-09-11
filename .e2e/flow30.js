const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const notifs=[];
  // capture ALL responses with status + url + first 300 chars (to find notification text & 404)
  page.on('response', r=>{ const ct=r.headers()['content-type']||''; if(!r.url().includes('.js')&&!r.url().includes('.css')){ r.text().then(t=>{ notifs.push({s:r.status(), u:r.url().replace(BASE,'').slice(0,60), c:ct.slice(0,30), t:(t||'').replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').slice(0,300)}); }).catch(()=>{});} });
  async function activateVar(stock_id){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate(()=>{ const tb=document.querySelector('button[name="tabs_product_variations"]'); if(tb) tb.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-TT-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E TT '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  for(const value of [2,1]){
    await activateVar(pid);
    await page.locator('select[name="assign_category_id"]').selectOption({value:String(value)});
    await page.click('input[name="assign_category_submit"]');
    await page.waitForTimeout(3000); await page.waitForLoadState('networkidle');
  }
  await activateVar(pid);
  console.log('assigned:', JSON.stringify(await page.locator('.tablestyle2 tr').allInnerTexts()));

  // click generate_combos
  notifs.length=0;
  await page.click('input[name="generate_combos"]');
  await page.waitForTimeout(3500);
  console.log('--- GC click responses ---');
  for(const n of notifs) console.log(n.s, n.u, n.c, '|', n.t.slice(0,160));

  await activateVar(pid);
  // click generate_child
  notifs.length=0;
  await page.click('input[name="generate_child"]');
  await page.waitForTimeout(3500);
  console.log('--- GCh click responses ---');
  for(const n of notifs) console.log(n.s, n.u, n.c, '|', n.t.slice(0,160));

  // children in dropdown
  await nav('/inventory/manage/items.php'); await page.waitForLoadState('networkidle');
  const opts = await page.evaluate(()=>{
    const sels=Array.from(document.querySelectorAll('select'));
    for(const s of sels){ const tt=s.innerText||''; if(tt.includes('E2E-TT-')) return tt; } return null;
  });
  const kids = opts? opts.split('\n').map(x=>x.trim()).filter(x=>x.includes('E2E-TT-')) : [];
  console.log('DROPDOWN children =', kids.length, JSON.stringify(kids.slice(0,10)));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});