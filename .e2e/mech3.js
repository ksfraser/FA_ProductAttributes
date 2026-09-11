const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav = (p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  const html = await page.content();
  console.log('has addupdate string:', html.includes('addupdate'));
  console.log('has Insert New Item:', html.includes('Insert New Item'));
  // find context around 'Insert New Item'
  const idx = html.indexOf('Insert New Item');
  console.log('CTX:', idx>=0 ? html.slice(idx-200, idx+80).replace(/\n/g,' ') : 'n/a');
  // find addupdate context
  const j = html.indexOf('addupdate');
  console.log('ADDUPDATECTX:', j>=0 ? html.slice(j-150,j+60).replace(/\n/g,' ') : 'n/a');
  // new item tab anchor/button
  const newLinks = await page.locator('a:has-text("New item"), a:has-text("New Item")').allInnerTexts();
  console.log('New item links:', JSON.stringify(newLinks));
  // count forms
  console.log('FORM count:', await page.locator('form').count());
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});