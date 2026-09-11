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

  // Click Variations tab first
  await p.click('button[name="tabs_product_variations"]');
  await pause(3000);

  // Verify the variations content loaded correctly
  var afterTab = await p.evaluate(function(){
    var tabsDiv = document.getElementById('_tabs_div');
    return {
      content: tabsDiv ? tabsDiv.innerHTML.slice(0,300) : 'NO DIV',
      hasButtons: !!document.querySelector('button[name="generate_combos"]')
    };
  });
  console.log('After Variations tab:', afterTab.hasButtons ? 'Buttons found' : 'NO BUTTONS');
  console.log('Content preview:', afterTab.content.slice(0,200));

  // Now intercept the raw HTTP response for the generate_combos click
  var rawBody = null;
  p.on('response', async function(response) {
    if (response.url().indexOf('items.php') >= 0 && response.request().method() === 'POST') {
      try {
        var body = await response.text();
        if (body.indexOf('dataReady') >= 0) {
          rawBody = body;
        }
      } catch(e) {}
    }
  });

  await p.click('button[name="generate_combos"]');
  await pause(4000);

  if (rawBody) {
    // Extract JSON from: ...dataReady({...}) or ...dataReady({...})...
    // The format could be: JsHttpRequest.dataReady({...})\n or top && top.JsHttpRequestGlobal && top.JsHttpRequestGlobal.dataReady({...})
    var jsonStart = rawBody.indexOf('dataReady(');
    if (jsonStart >= 0) {
      var jsonStr = rawBody.slice(jsonStart + 'dataReady('.length);
      // Find the matching closing paren
      var depth = 1;
      var end = 0;
      for (var i = 0; i < jsonStr.length && depth > 0; i++) {
        if (jsonStr[i] === '(') depth++;
        if (jsonStr[i] === ')') depth--;
        if (depth === 0) end = i;
      }
      jsonStr = jsonStr.slice(0, end);

      try {
        var data = JSON.parse(jsonStr);
        console.log('\n=== PARSED RESPONSE ===');
        console.log('id:', data.id);
        console.log('text:', JSON.stringify(data.text||'').slice(0,200));
        console.log('js type:', typeof data.js, Array.isArray(data.js) ? 'isArray' : 'NOT-array');

        if (data.js) {
          var keys = Object.keys(data.js);
          console.log('js keys:', keys);
          for (var ki = 0; ki < keys.length; ki++) {
            var k = keys[ki];
            var cmd = data.js[k];
            console.log('\n--- cmd key=' + k + ' n=' + cmd.n + ' t=' + cmd.t + ' ---');
            console.log('  data length:', (cmd.data||'').length);

            if (cmd.t === 'tabs') {
              // Extract _tabs_div content
              var tabsData = cmd.data;
              var divStart = tabsData.indexOf("id='_tabs_div'");
              if (divStart < 0) divStart = tabsData.indexOf('id="_tabs_div"');
              if (divStart >= 0) {
                var divContent = tabsData.slice(divStart + 14); // skip past id='_tabs_div' and >
                // Remove the closing </div> at the end
                var lastDivClose = divContent.lastIndexOf('</div>');
                if (lastDivClose >= 0) divContent = divContent.slice(0, lastDivClose);
                console.log('  _tabs_div content length:', divContent.length);
                console.log('  _tabs_div CONTENT:');
                console.log(divContent.slice(0,3000));
              } else {
                console.log('  NO _tabs_div found in tabs data');
                console.log('  tabs data length:', tabsData.length);
                console.log('  tabs data first 500:', tabsData.slice(0,500));
                console.log('  tabs data last 500:', tabsData.slice(-500));
              }
            }
          }
        }
      } catch(e) {
        console.log('Parse error:', e.message);
        console.log('JSON string (first 500):', jsonStr.slice(0,500));
      }
    }
  } else {
    console.log('NO RAW RESPONSE CAPTURED');
  }

  await b.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
