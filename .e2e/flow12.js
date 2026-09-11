// Capture response bodies of each full POST to read display_notification + panel state.
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
let html='';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  page.on('response', r=>{ if(r.request().method()==='POST' && r.url().includes('items.php')){ r.text().then(t=>{ html=t; }).catch(()=>{});} });
  async function postItem(stock_id, fields){
    html='';
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate((o)=>{
      const f=document.querySelector('form'); if(!f) throw new Error('no form');
      const set=(name,val)=>{ let el=f.querySelector('[name="'+name+'"]'); if(!el){ el=document.createElement('input'); el.type='hidden'; el.name=name; f.appendChild(el);} el.value=val; };
      set('_tabs_sel',o._tabs||'product_variations');
      // force the stock_id form field to our pid so the POST carries it
      const sid=f.querySelector('[name="stock_id"]'); if(sid){ const tag=sid.tagName.toLowerCase(); if(tag==='select'){ for(const op of sid.options){ if(op.value===o.pid) sid.value=o.pid; } } else { sid.value=o.pid; } }
      else set('stock_id',o.pid);
      for(const k of Object.keys(o.fields||{})) set(k,o.fields[k]);
      f.submit();
    }, {stock_id, pid:stock_id, _tabs:fields._tabs||'product_variations', fields});
    await page.waitForTimeout(3500); await page.waitForLoadState('networkidle');
    return html;
  }
  const strip=(h)=>{ h=(h||'').replace(/<[^>]*>/g,' '); return h.replace(/\s+/g,' '); };

  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const pk=Date.now().toString().slice(-5);
  const pid='E2E-Z-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E Z '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  for(const cid of ['2','1']){ await postItem(pid, { assign_category_submit:'Assign Category', assign_category_id:cid }); }

  let resp = await postItem(pid, { generate_combos:'Generate Combinations' });
  let t = strip(resp);
  console.log('GC stock_id present in resp:', /stock_id=/.test(t) || t.includes(pid));
  const msgGC = (t.match(/(Combination set[^.]*|No categories[^.]*|No values[^.]*|Invalid stock[^.]*|Cannot generate[^.]*)/g)||[]).slice(0,4);
  console.log('GC messages:', JSON.stringify(msgGC));
  console.log('GC has Generated Variations wording:', t.includes('Generate Combinations'));
  console.log('GC panel Assigned Categor:', t.includes('Assigned Categories'));
  console.log('GC RESP len', t.length, ' sample around notification:');
  console.log(strip(resp).slice(0,600));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});