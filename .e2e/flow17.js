const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const strip=(h)=>h.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ');
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-GG-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E GG '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  // assign Color(2)+Size(1) via full POST
  for(const cid of ['2','1']){
    await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
    await page.evaluate((o)=>{
      const f=document.querySelector('form');
      const set=(n,v)=>{ let e=f.querySelector('[name="'+n+'"]'); if(!e){e=document.createElement('input');e.type='hidden';e.name=n;f.appendChild(e);}e.value=v; };
      const s=f.querySelector('select[name="stock_id"]'); for(const op of s.options){ if(op.value===o.pid){ op.selected=true; } }
      set('_tabs_sel','product_variations'); set('assign_category_submit','x'); set('assign_category_id',o.cid);
      // capture the response body after this submit
      let resp='';
      const iv=setInterval(()=>{},1000);
      f.submit();
    }, {pid, cid});
    await page.waitForTimeout(3000);
  }

  // now the generate_combos POST — grab its response body text directly
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  let gcMsg='';
  page.on('response', r=>{
    if(r.request().method()==='POST' && r.url().includes('items.php')){
      r.text().then(t=>{ const s=strip(t); const m=(s.match(/(Combination set[^.]{0,80}|No categories[^.]{0,60}|No values[^.]{0,60}|Invalid stock[^.]{0,40}|Cannot generate[^.]{0,50})/g)||[]); if(m.length) gcMsg=m.join(' | '); }).catch(()=>{});
    }
  });
  await page.evaluate((o)=>{
    const f=document.querySelector('form');
    const set=(n,v)=>{ let e=f.querySelector('[name="'+n+'"]'); if(!e){e=document.createElement('input');e.type='hidden';e.name=n;f.appendChild(e);}e.value=v; };
    const s=f.querySelector('select[name="stock_id"]'); for(const op of s.options){ if(op.value===o.pid){ op.selected=true; } }
    set('_tabs_sel','product_variations'); set('generate_combos','Generate Combinations');
    f.submit();
  }, {pid});
  await page.waitForTimeout(4000);
  console.log('GENERATE_COMBOS message =>', JSON.stringify(gcMsg));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});