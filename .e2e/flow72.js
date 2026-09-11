const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>{let res=page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000}); return res;};
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  await nav('/sales/sales_order_entry.php?NewInvoice=0');
  await pause(2500); await page.waitForLoadState('networkidle');

  // Dump available customers so we can pick one
  const cust = await page.evaluate(()=>{
    const sel=document.querySelector('select[name="customer_id"], select[name="BranchID"], select[name="branch_id"], select[name="customer"]');
    return sel? Array.from(sel.options).slice(0,30).map(o=>({v:o.value,t:o.text})) : {note:'no customer select found'};
  });
  console.log('CUSTOMERS:', JSON.stringify(cust,null,1));

  // Try picking the first real customer via a form submit of the visible branch selector
  // Common: a text input with autocomplete; we'll attempt the first <select> that looks like customer.
  const picked = await page.evaluate(()=>{
    const sel=document.querySelector('select[name="customer_id"], select[name="BranchID"], select[name="branch_id"], select[name="customer"], input[name="customer_id"], input[class*="customer"]');
    if(!sel) return null;
    if(sel.tagName==='SELECT'){
      const real=Array.from(sel.options).map(o=>o.value).filter(v=>v && v!=='0')[0];
      if(real){ sel.value=real; return {type:'select',chosen:real}; }
    }
    return null;
  });
  console.log('PICKED CUSTOMER:', JSON.stringify(picked));
  if(picked && picked.type==='select'){
    await page.waitForLoadState('networkidle'); await pause(1200);
    // submit the form (the branch selection usually autosubmits on change)
  }
  await pause(1500);

  // Now inspect the item picker
  const items = await page.evaluate(()=>{
    const result={stockSelectOptions:[], inputs:[]};
    const sel=document.querySelector('select[name="stock_id"]');
    if(sel){ result.stockSelectOptions=Array.from(sel.options).slice(0,60).map(o=>({v:o.value,t:o.text})); }
    // Also capture any search input for items
    Array.from(document.querySelectorAll('input')).forEach(i=>{ if(/item|stock/i.test(i.name||'')) result.inputs.push({name:i.name,type:i.type}); });
    return result;
  });
  console.log('ITEM PICKER:', JSON.stringify(items,null,1));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});