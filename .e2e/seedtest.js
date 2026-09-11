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

  const pk=Date.now().toString().slice(-4);
  const code='cc'+pk, code2='ss'+pk;
  // add category
  await nav('/modules/FA_ProductAttributes/public/index.php?tab=categories'); await page.waitForLoadState('networkidle');
  const subBtns = await page.locator('form[method="post"] button[type="submit"], form[method="post"] input[type="submit"]').evaluateAll(es=>es.map(e=>({n:e.name,t:e.tagName})));
  console.log('category submit btns:', JSON.stringify(subBtns));
  await page.fill('input[name="code"]', code);
  await page.fill('input[name="label"]', 'Cat '+pk);
  await page.fill('input[name="sort_order"]', '6');
  // click the Save button in the add form
  await page.locator('form[method="post"] button[type="submit"]').first().click();
  await page.waitForLoadState('networkidle'); await page.waitForTimeout(1500);

  // verify persisted: read categories select values
  await nav('/modules/FA_ProductAttributes/public/index.php?tab=values'); await page.waitForLoadState('networkidle');
  const opts=await page.locator('select[name="category_id"] option').evaluateAll(os=>os.map(o=>o.text));
  console.log('categories after add:', JSON.stringify(opts));
  console.log('has '+code+':', opts.includes(code));

  // discover id
  const o2=await page.locator('select[name="category_id"] option').evaluateAll(os=>os.map(o=>({v:o.value,t:o.text})));
  const cidObj=o2.find(o=>o.t===code); console.log('id of '+code+':', cidObj&&cidObj.v);
  if(cidObj){
    // add two values
    for(const [val,slug] of [['Q'+pk+'V1','q-'+pk+'-v1'],['Q'+pk+'V2','q-'+pk+'-v2']]){
      await nav('/modules/FA_ProductAttributes/public/index.php?tab=values&category_id='+cidObj.v); await page.waitForLoadState('networkidle');
      await page.fill('input[name="value"]', val);
      await page.fill('input[name="slug"]', slug);
      await page.locator('form[method="post"] button[type="submit"]').first().click();
      await page.waitForLoadState('networkidle'); await page.waitForTimeout(1200);
    }
    // verify values persisted
    await nav('/modules/FA_ProductAttributes/public/index.php?tab=values&category_id='+cidObj.v); await page.waitForLoadState('networkidle');
    const body=await page.locator('body').innerText();
    console.log('values persisted Q'+pk+'V1:', body.includes('Q'+pk+'V1'));
  }
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});