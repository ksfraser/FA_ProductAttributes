const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  let postBodies=[];
  page.on('response', async resp=>{
    if(resp.request().method()==='POST' && resp.url().includes('items.php') && resp.status()===200){
      let t=''; try{ t=await resp.text(); }catch(e){}
      postBodies.push(t);
    }
  });
  page.on('dialog', async d=>await d.dismiss());
  async function openVarTab(stock_id){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await pause(1000);
    await page.locator('button[name="tabs_product_variations"]').first().click();
    await pause(3500); await page.waitForLoadState('networkidle');
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-GEN-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E Gen '+pk);
  await page.click('button[name="addupdate"]'); await pause(2500); await page.waitForLoadState('networkidle');
  // assign categories 2 (Color) and 1 (Size)
  for(const val of ['2','1']){
    await openVarTab(pid);
    await page.locator('select[name="assign_category_id"]').selectOption({value:val}).catch(async()=>{
      console.log('select fail for '+val+' - will try another');
    });
    await page.locator('input[name="assign_category_submit"]').click();
    await pause(3500); await page.waitForLoadState('networkidle');
  }
  await openVarTab(pid);
  // confirm current assignments shown
  const assignedText = await page.evaluate(()=> (document.body.innerText.match(/assigned[^\n]*/g)||[]).slice(0,5));
  console.log('ASSIGNMENT_TEXT', JSON.stringify(assignedText));
  // click generate
  postBodies=[];
  const hasGc = await page.locator('input[name="generate_combos"]').count();
  console.log('HAS_GC_BUTTON', hasGc);
  if(hasGc>0){
    await page.locator('input[name="generate_combos"]').click();
    await pause(5000); await page.waitForLoadState('networkidle').catch(()=>{});
  }
  const lastBody = postBodies[postBodies.length-1]||'';
  // search for indication combos were generated or for a message
  const hasVariationsFieldset = lastBody.includes('Existing Variations') || lastBody.includes('Generate Combinations');
  const notice = (lastBody.match(/(Combination[^<]{0,80}|saved[^<]{0,60}|No categories[^<]{0,60})/gi)||[]);
  console.log('GEN_RESP_POSTBODIES='+postBodies.length+' LASTLEN='+lastBody.length);
  console.log('GEN_RESP notices='+JSON.stringify(notice));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});