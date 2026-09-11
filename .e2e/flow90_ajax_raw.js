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

  // Step 1: Click Variations tab to load the tab content
  console.log('=== Step 1: Click Variations tab ===');
  var capturedResponses = [];
  await p.evaluate(function(){
    window.__capturedResponses = [];
    window.__rawResponses = [];
    var origDataReady = JsHttpRequest.dataReady;
    JsHttpRequest.dataReady = function(data) {
      window.__capturedResponses.push(data);
      return origDataReady.apply(this, arguments);
    };
  });

  await p.click('button[name="tabs_product_variations"]');
  await pause(3000);

  // Check what's now on the page after Variations tab loaded
  var afterTab = await p.evaluate(function(){
    var btns = [];
    document.querySelectorAll('button[name="generate_combos"],button[name="create_child_product"]').forEach(function(b){
      btns.push({name:b.name,text:b.textContent.trim()});
    });
    var sel = document.querySelector('input[name="_tabs_sel"]');
    var tabsDiv = document.getElementById('_tabs_div');
    return {
      selectedTab: sel ? sel.value : 'NONE',
      variationButtons: btns,
      tabsDivExists: !!tabsDiv,
      tabsDivHTML: tabsDiv ? tabsDiv.innerHTML.slice(0,500) : 'NO DIV'
    };
  });
  console.log('After Variations tab click:', JSON.stringify(afterTab, null, 2));
  var capCount = await p.evaluate(function(){ return window.__capturedResponses ? window.__capturedResponses.length : 0; });
  console.log('Captured responses count:', capCount);

  // Check the captured response for the Variations tab switch
  if (capCount > 0) {
    var lastResp = await p.evaluate(function(){ return window.__capturedResponses[window.__capturedResponses.length - 1]; });
    console.log('\n=== LAST CAPTURED RESPONSE ===');
    console.log('id:', lastResp.id);
    if (lastResp.js && Array.isArray(lastResp.js)) {
      for (var i = 0; i < lastResp.js.length; i++) {
        var cmd = lastResp.js[i];
        console.log('cmd ' + i + ': n=' + cmd.n + ' t=' + cmd.t + ' data_len=' + (cmd.data||'').length);
        if (cmd.t === 'tabs' || cmd.t === '_tabs_div') {
          console.log('  CONTENT (first 3000):');
          console.log(cmd.data ? cmd.data.slice(0,3000) : 'NULL');
        }
      }
    }
  }

  // Step 2: Now click Generate Combinations (should be visible now)
  console.log('\n=== Step 2: Click Generate Combinations ===');
  await p.evaluate(function(){ window.__capturedResponses = []; });
  var genBtnExists = await p.evaluate(function(){ return !!document.querySelector('button[name="generate_combos"]'); });
  console.log('generate_combos button exists:', genBtnExists);

  if (genBtnExists) {
    await p.click('button[name="generate_combos"]');
    await pause(3000);

    var genCapCount = await p.evaluate(function(){ return window.__capturedResponses ? window.__capturedResponses.length : 0; });
    console.log('Captured responses count after generate:', genCapCount);
    if (genCapCount > 0) {
      var genResp = await p.evaluate(function(){ return window.__capturedResponses[window.__capturedResponses.length - 1]; });
      console.log('\n=== GENERATE COMBOS RESPONSE ===');
      console.log('id:', genResp.id);
      console.log('text:', (genResp.text||'').slice(0,300));
      if (genResp.js && Array.isArray(genResp.js)) {
        for (var i = 0; i < genResp.js.length; i++) {
          var cmd = genResp.js[i];
          console.log('--- cmd ' + i + ' ---');
          console.log('  n:', cmd.n, 't:', cmd.t);
          console.log('  data length:', (cmd.data||'').length);
          if (cmd.t === 'tabs') {
            console.log('  *** TABS CONTENT (first 3000): ***');
            console.log(cmd.data ? cmd.data.slice(0,3000) : 'NULL');
            console.log('  *** END TABS CONTENT ***');
          } else {
            console.log('  data preview:', (cmd.data||'').slice(0,300));
          }
        }
      } else {
        console.log('No js array! Full response:');
        console.log(JSON.stringify(genResp).slice(0,2000));
      }
    } else {
      console.log('NO RESPONSES CAPTURED!');
    }

    // Also check the page state after generate
    var afterGen = await p.evaluate(function(){
      var notif = document.getElementById('msgbox');
      var tabsDiv = document.getElementById('_tabs_div');
      return {
        msgbox: notif ? notif.innerHTML.slice(0,300) : 'NO MSGBOX',
        tabsDivExists: !!tabsDiv,
        tabsDivContent: tabsDiv ? tabsDiv.innerHTML.slice(0,500) : 'NO DIV'
      };
    });
    console.log('\nPage state after generate:', JSON.stringify(afterGen, null, 2));
  } else {
    console.log('generate_combos button NOT FOUND - tab may not have loaded');
  }

  await b.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
