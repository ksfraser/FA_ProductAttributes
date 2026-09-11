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

  // Go to Settings tab first (to force a tab switch later)
  await p.goto(BASE+'/inventory/manage/items.php?stock_id=BattlestarGalactica',{waitUntil:'networkidle',timeout:45000});
  await pause(2000);

  // Capture all AJAX responses via dataReady interception
  await p.evaluate(function(){
    window.__ajaxResponses = [];
    var origDR = JsHttpRequest.dataReady;
    JsHttpRequest.dataReady = function(data) {
      window.__ajaxResponses.push({id:data.id, js:data.js, text:data.text});
      return origDR.apply(this, arguments);
    };
  });

  // Step 1: Switch to Variations tab
  console.log('=== STEP 1: Switch to Variations tab ===');
  await p.click('button[name="tabs_product_variations"]');
  await pause(3000);

  var r1Count = await p.evaluate(function(){ return window.__ajaxResponses.length; });
  if (r1Count > 0) {
    var tabSwitchData = await p.evaluate(function(){
      var r = window.__ajaxResponses[window.__ajaxResponses.length - 1];
      if (!r || !r.js) return null;
      var keys = Object.keys(r.js);
      for (var i = 0; i < keys.length; i++) {
        var cmd = r.js[keys[i]];
        if (cmd.t === 'tabs') return cmd.data;
      }
      return null;
    });
    if (tabSwitchData) {
      console.log('Tab switch tabs data length:', tabSwitchData.length);
      // Extract _tabs_div content  
      var m = tabSwitchData.match(/id='_tabs_div'[^>]*>([\s\S]*?)(?=<\/div>\s*$)/);
      console.log('_tabs_div content length:', m ? m[1].length : 'NO MATCH');
      if (m) console.log('_tabs_div preview:', m[1].slice(0,300));
    }
  }

  // Step 2: Click Generate Combinations
  console.log('\n=== STEP 2: Generate Combinations ===');
  var r1Total = await p.evaluate(function(){ return window.__ajaxResponses.length; });
  await p.click('button[name="generate_combos"]');
  await pause(4000);
  var r2Total = await p.evaluate(function(){ return window.__ajaxResponses.length; });
  console.log('Responses before:', r1Total, 'after:', r2Total);

  if (r2Total > r1Total) {
    var genData = await p.evaluate(function(){
      var r = window.__ajaxResponses[window.__ajaxResponses.length - 1];
      if (!r || !r.js) return null;
      var result = {text: r.text, commands: []};
      var keys = Object.keys(r.js);
      for (var i = 0; i < keys.length; i++) {
        var cmd = r.js[keys[i]];
        result.commands.push({n:cmd.n, t:cmd.t, why:cmd.why, dataLen:(cmd.data||'').length});
        if (cmd.t === 'tabs') {
          result.tabsData = cmd.data;
        }
      }
      return result;
    });
    if (genData) {
      console.log('Generate response text:', JSON.stringify(genData.text).slice(0,100));
      console.log('Commands:', JSON.stringify(genData.commands));
      if (genData.tabsData) {
        console.log('tabs data length:', genData.tabsData.length);
        var m2 = genData.tabsData.match(/id='_tabs_div'[^>]*>([\s\S]*?)(<\/div>\s*$)/);
        if (m2) {
          console.log('_tabs_div content length:', m2[1].length);
          console.log('_tabs_div content:');
          console.log(m2[1]);
        } else {
          console.log('No _tabs_div match. Last 500 chars of tabs data:');
          console.log(genData.tabsData.slice(-500));
        }
      }
    }
  }

  await b.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
