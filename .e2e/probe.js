// Diagnostic: log into FA at localhost:8080 and probe the item page DOM.
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');

const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';

(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu'] });
  const page = await browser.newPage();
  const log = (m) => console.log(m);

  // Log in
  await page.goto(BASE + '/');
  await page.waitForLoadState('networkidle');
  log('LOGIN PAGE URL: ' + page.url());
  log('Has user field: ' + (await page.locator('input[name="user_name_entry_field"]').count()));
  await page.fill('input[name="user_name_entry_field"]', 'opencode');
  await page.fill('input[name="password"]', 'opencode');
  await page.click('input[type="submit"]');
  await page.waitForLoadState('networkidle');
  log('AFTER LOGIN URL: ' + page.url());

  // Go to item management index
  await page.goto(BASE + '/inventory/manage/index.php');
  await page.waitForLoadState('networkidle');
  log('ITEMS INDEX URL: ' + page.url());
  log('--- index anchor/text sample ---');
  const links = await page.locator('a').allInnerTexts();
  log('total links: ' + links.length);
  log('has New Item / item list nav:');
  for (const t of links) if (/new/i.test(t)) log('   link: ' + t.trim());

  // Try item edit page directly
  await page.goto(BASE + '/inventory/manage/items.php?NewItem=1');
  await page.waitForLoadState('networkidle');
  log('NEW ITEM URL: ' + page.url());
  const html = await page.content();
  log('new item has StockID: ' + html.includes('StockID'));
  log('new item title sample:');
  const inputs = await page.locator('input[name]').evaluateAll(es => es.map(e => ({ name: e.name, type: e.type, val: e.value })));
  log('input names: ' + JSON.stringify(inputs.map(i => i.name).slice(0, 40)));

  await browser.close();
})().catch(e => { console.error('FATAL', e.message); process.exit(1); });