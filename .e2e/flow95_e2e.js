#!/usr/bin/env node
/**
 * flow95_e2e.js — End-to-end test after applying fixes:
 *   1. FrontAccountingDbAdapter no longer triggers die() on db errors
 *   2. product_variation_combos table now exists on live
 *   3. VariationsTab::handlePostActions catches Throwables
 *
 * Verifies:
 *   - Tab switch still works (baseline)
 *   - Generate Combinations dispatches, completes, shows PA_DEBUG markers + sections
 *   - No "Back" link (hyperlink_back) leaking into the response
 *   - _tabs_div has content
 *   - Notification or success message appears (display_notification)
 */
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';

(async () => {
  const B = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const ctx = await B.newContext({ viewport: { width: 1280, height: 800 } });
  const p = await ctx.newPage();

  // Intercept responses to capture Ajax command list
  const responses = [];
  p.on('response', async (r) => {
    if (r.url().includes('/items.php') && r.headers()['content-type']?.includes('text/javascript')) {
      try {
        const body = await r.text();
        responses.push({ url: r.url(), body, ts: Date.now() });
      } catch {}
    }
  });

  const fail = (name, detail) => { console.error(`FAIL: ${name} — ${detail}`); process.exitCode = 1; };
  const pass = (name) => console.log(`PASS: ${name}`);

  try {
    // ---- Login ----
    console.log('Logging in...');
    await p.goto('http://localhost:8080/', { waitUntil: 'networkidle', timeout: 30000 });
    await p.fill('input[name="user_name_entry_field"]', 'opencode');
    await p.fill('input[name="password"]', 'opencode');
    await Promise.all([
      p.waitForNavigation({ timeout: 30000 }).catch(e => console.log('nav-warn:', e.message)),
      p.click('input[name="SubmitUser"]'),
    ]);
    await p.waitForTimeout(2000);
    console.log('Logged in. URL:', p.url());
    const bodySnippet = await p.evaluate(() => document.body ? document.body.innerText.substring(0, 400) : 'NO BODY');
    console.log('BODY:', bodySnippet.replace(/\n/g, ' | '));

    // ---- Navigate to items page ----
    console.log('Navigating to items.php...');
    await p.goto('http://localhost:8080/inventory/manage/items.php?stock_id=Americans_S1', { waitUntil: 'networkidle', timeout: 30000 });
    const title = await p.locator('title').textContent();
    console.log('Page title:', title);
    console.log('URL after items nav:', p.url());
    await p.waitForTimeout(1500);

    // ---- Test 1: Tab switch (baseline) ----
    responses.length = 0;
    const variationsTab = p.locator('#tabs li', { hasText: 'Variations' }).first();
    if (await variationsTab.count() === 0) {
      fail('tab-switch', '#tabs li with text Variations not found — tab may not render');
      const tabsText = await p.locator('#tabs').textContent().catch(() => 'NO #tabs');
      console.log('#tabs text:', JSON.stringify(tabsText.substring(0, 500)));
      return;
    }
    await variationsTab.click();
    await p.waitForTimeout(3000);

    let foundTabResponse = false;
    for (const r of responses) {
      if (r.body.includes('_tabs_div') && r.body.includes('PA_DEBUG')) {
        foundTabResponse = true;
        break;
      }
    }
    if (foundTabResponse) pass('tab-switch');
    else fail('tab-switch', 'No PA_DEBUG in tab switch response');

    // ---- Test 2: Generate Combinations ----
    console.log('\nClicking Generate Combinations...');
    responses.length = 0;
    const genBtn = p.locator('#generate_combos');
    if (await genBtn.count() === 0) { fail('gen-combos-btn', '#generate_combos not found'); return; }

    await genBtn.click();
    await p.waitForTimeout(5000);

    if (responses.length === 0) {
      fail('gen-combos-ajax', 'No ajax response captured');
      return;
    }

    let genResponse = null;
    let genResponseIdx = -1;
    for (let i = responses.length - 1; i >= 0; i--) {
      if (responses[i].body.includes('_tabs_div')) {
        genResponse = responses[i];
        genResponseIdx = i;
        break;
      }
    }

    if (!genResponse) {
      fail('gen-combos-response', 'No response with _tabs_div');
      console.log('All responses:');
      for (const r of responses) console.log('  url:', r.url, 'len:', r.body.length, 'snip:', r.body.substring(0, 200));
      return;
    }

    console.log('Gen combos response length:', genResponse.body.length);

    // Check for PA_DEBUG markers
    const debugMarkers = genResponse.body.match(/PA_DEBUG/g);
    const markerCount = debugMarkers ? debugMarkers.length : 0;
    console.log('PA_DEBUG markers in gen response:', markerCount);

    if (markerCount >= 10) pass('gen-combos-markers');
    else fail('gen-combos-markers', `Only ${markerCount} markers (expected ≥10)`);

    // Check for "Back" link (would indicate die/exit)
    const hasBackLink = genResponse.body.includes('hyperlink_back') || genResponse.body.includes('> Back <') || genResponse.body.includes('class="back"');
    if (hasBackLink) fail('gen-combos-no-die', 'Back link found — die() may still be active');
    else pass('gen-combos-no-die');

    // Check for sections
    const hasSections = genResponse.body.includes('ParentProductSection') || genResponse.body.includes('ExistingVariationsSection') || genResponse.body.includes('AssignedCategoriesSection');
    if (hasSections) pass('gen-combos-sections');
    else fail('gen-combos-sections', 'No section debug markers in response');

    // Check for notification (display_notification output)
    const hasNotification = genResponse.body.includes('Combination') || genResponse.body.includes('notification') || genResponse.body.includes('display_');
    if (hasNotification) pass('gen-combos-notification');
    else fail('gen-combos-notification', 'No notification message in response');

    // ---- Test 3: Create Child Product ----
    console.log('\nClicking Create Child Product...');
    responses.length = 0;
    const createBtn = p.locator('#create_child_product');
    if (await createBtn.count() === 0) { fail('create-child-btn', '#create_child_product not found'); return; }

    await createBtn.click();
    await p.waitForTimeout(5000);

    let childResponse = null;
    for (const r of responses) {
      if (r.body.includes('_tabs_div')) {
        childResponse = r;
        break;
      }
    }

    if (!childResponse) {
      fail('create-child-response', 'No response with _tabs_div');
      return;
    }

    console.log('Create child response length:', childResponse.body.length);

    const childMarkers = childResponse.body.match(/PA_DEBUG/g);
    const childMarkerCount = childMarkers ? childMarkers.length : 0;
    console.log('PA_DEBUG markers in create child response:', childMarkerCount);

    if (childMarkerCount >= 10) pass('create-child-markers');
    else fail('create-child-markers', `Only ${childMarkerCount} markers`);

    const childHasBackLink = childResponse.body.includes('hyperlink_back') || childResponse.body.includes('> Back <');
    if (childHasBackLink) fail('create-child-no-die', 'Back link found');
    else pass('create-child-no-die');

    const childHasSections = childResponse.body.includes('ParentProductSection') || childResponse.body.includes('ExistingVariationsSection');
    if (childHasSections) pass('create-child-sections');
    else fail('create-child-sections', 'No sections in response');

    const childHasNotification = childResponse.body.includes('Combination') || childResponse.body.includes('child') || childResponse.body.includes('notification') || childResponse.body.includes('error');
    if (childHasNotification) pass('create-child-notification');
    else fail('create-child-notification', 'No notification/error in response');

  } catch (err) {
    console.error('EXCEPTION:', err.message);
    process.exitCode = 1;
  } finally {
    await B.close();
  }
})();
