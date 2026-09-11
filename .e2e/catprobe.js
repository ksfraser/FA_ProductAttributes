const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  await nav('/modules/FA_ProductAttributes/public/index.php?tab=categories'); await page.waitForLoadState('networkidle');
  const body=await page.locator('body').innerText();
  console.log('CATEGORIES BODY:', body.slice(0,600).replace(/\n/g,' | '));
  console.log('input names:', await page.locator('input[name]').evaluateAll(es=>es.map(e=>e.name)));
  console.log('forms:', await page.locator('form[method]').evaluateAll(fs=>fs.map(f=>f.getAttribute('method'))));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});