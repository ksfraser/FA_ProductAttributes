const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE='http://localhost:8080';
const EXE='/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async()=>{
  const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});
  const p=await b.newPage();
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));

  await p.goto(BASE+'/',{waitUntil:'networkidle',timeout:45000});
  await p.fill('input[name="user_name_entry_field"]','opencode');
  await p.fill('input[name="password"]','opencode');
  await p.click('input[type="submit"]');
  await p.waitForLoadState('networkidle');

  await p.goto(BASE+'/inventory/manage/items.php?stock_id=BattlestarGalactica',{waitUntil:'networkidle',timeout:45000});
  await pause(2000);

  // Dump forms and buttons on the page
  var info = await p.evaluate(function(){
    var forms = document.querySelectorAll('form');
    var result = [];
    forms.forEach(function(f,i){
      var btns = [];
      f.querySelectorAll('button, input[type=submit]').forEach(function(b){
        btns.push({name:b.name,type:b.type,value:b.value,className:b.className,text:b.textContent.trim().slice(0,40)});
      });
      result.push({id:f.id,action:f.action,method:f.method,buttons:btns,inputCount:f.querySelectorAll('input').length});
    });
    return result;
  });
  console.log('Forms on page:', JSON.stringify(info, null, 2));

  // Also check for the generate_combos button specifically
  var btnInfo = await p.evaluate(function(){
    var btns = document.querySelectorAll('button[name="generate_combos"],button[name="create_child_product"]');
    var result = [];
    btns.forEach(function(b){
      result.push({name:b.name,text:b.textContent.trim(),className:b.className,type:b.type,formnovalidate:b.getAttribute('formnovalidate')});
    });
    var hidden = document.querySelectorAll('input[name="_tabs_sel"],input[name="stock_id"]');
    var hiddens = [];
    hidden.forEach(function(h){hiddens.push({name:h.name,value:h.value,type:h.type});});
    return {buttons:result,hiddens:hiddens};
  });
  console.log('Button info:', JSON.stringify(btnInfo, null, 2));

  await b.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
