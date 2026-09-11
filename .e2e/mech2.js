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
  await page.click('input[type="submit"]');
  await page.waitForLoadState('networkidle');
  console.log('post-login URL:', page.url());
  console.log('password gone:', (await page.locator('input[name="password"]').count()) === 0);
  await nav('/inventory/manage/items.php?NewItem=1');
  await page.waitForLoadState('networkidle');
  console.log('NewItem URL:', page.url());
  const body = await page.locator('body').innerText();
  console.log('BODY SAMPLE:', body.slice(0,300).replace(/\n/g,' | '));
  console.log('NewStockID field:', await page.locator('input[name="NewStockID"]').count());
  console.log('all submit buttons:', await page.locator('input[type="submit"]').evaluateAll(es=>es.map(e=>e.value)));
  // Check for login redirect
  console.log('is login page:', await page.locator('input[name="user_name_entry_field"]').count());
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});