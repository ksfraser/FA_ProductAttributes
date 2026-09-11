const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  await page.goto(BASE + '/'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  await page.goto(BASE + '/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  // main form fields
  const fields = await page.locator('input[name], select[name], textarea[name]').evaluateAll(es => es.map(e => {
    return { tag:e.tagName, name:e.name||e.id, type:e.type, val:(e.tagName==='SELECT'? Array.from(e.options).filter(o=>o.selected).map(o=>o.value+':'+o.text).join(','): e.value) };
  }));
  console.log('=== NEW ITEM FORM FIELDS ===');
  fields.forEach(f => console.log(JSON.stringify(f)));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
