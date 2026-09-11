const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  await page.goto(BASE + '/');
  await page.waitForLoadState('networkidle');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]');
  await page.waitForLoadState('networkidle');

  await page.goto(BASE + '/inventory/manage/items.php?NewItem=1');
  await page.waitForLoadState('networkidle');
  // buttons/submits
  const btns = await page.locator('input[type="submit"], button[type="submit"]').allInnerTexts();
  console.log('SUBMIT BUTTONS:');
  btns.forEach((b,i)=>{ const v = b.trim(); if(v) console.log('  ['+i+'] '+v); });
  // labels near NewStockID, description
  const labels = await page.locator('label').allInnerTexts();
  console.log('LABEL SAMPLE:', labels.filter(l=>l.trim()).slice(0,20));
  // Does form include the plugin tab headers?
  const html = await page.content();
  console.log('has product_variations tab header:', html.includes('product_variations'));
  console.log('has tab key literal:', /_tabs_sel/.test(html));
  console.log('tab_sel options present:', (html.match(/options[^>]*product_variations/g)||[]).length);
  // Find _tabs_sel select options
  const tabOpts = await page.locator('select[name="_tabs_sel"] option').allInnerTexts();
  console.log('TABS SEL OPTIONS:', JSON.stringify(tabOpts));
  // default mb_flag
  const mb = await page.locator('input[name="_mb_flag_update"]').count();
  console.log('mb_flag hidden:', mb);
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
