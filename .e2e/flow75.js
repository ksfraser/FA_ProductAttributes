const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  async function openVarTab(s){
    await nav('/inventory/manage/items.php?stock_id='+s); await page.waitForLoadState('networkidle');
    await pause(1400);
    await page.locator('button[name="tabs_product_variations"]').first().click();
    await pause(3200); await page.waitForLoadState('networkidle');
  }
  const listVar=async()=> page.evaluate(()=>{
    const rows=Array.from(document.querySelectorAll('table')).flatMap(t=>Array.from(t.querySelectorAll('tr')))
      .map(r=>Array.from(r.querySelectorAll('td')).map(c=>c.innerText.trim()))
      .filter(c=>c.length>=2 && /auto-gas-/.test(c[0]));
    return rows.map(r=>r[0]).filter(v=>v);
  });
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  await openVarTab('auto-gas');
  const before = await listVar();
  console.log('BEFORE count:', before.length);

  // click Create Child Product
  await page.locator('button[name="create_child_product"]').click();
  await pause(3000);
  await page.waitForLoadState('networkidle');

  // switch to another tab and back (as the user did)
  await page.locator('button[name="tabs_product_variations"]').first().click();
  await pause(2500); await page.waitForLoadState('networkidle');
  await page.locator('button[name="tabs_product_variations"]').first().click();
  await pause(3500); await page.waitForLoadState('networkidle');

  const after = await listVar();
  console.log('AFTER count:', after.length);
  const added = after.filter(v=>!before.includes(v));
  const removed = before.filter(v=>!after.includes(v));
  console.log('ADDED children:', JSON.stringify(added));
  console.log('REMOVED children:', JSON.stringify(removed));

  // also capture post-click page text for any notification/error regardless of tab switch
  const txt = await page.evaluate(()=>document.body?document.body.innerText:'');
  const notif = txt.split('\n').filter(l=>/Created|Generate Child complete|No combination|error|Error|created/i.test(l) && !/Existing Variations|existed/.test(l));
  console.log('NOTIF lines:', JSON.stringify(notif.slice(0,10)));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});