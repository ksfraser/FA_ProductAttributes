// FA_ProductAttributes live-browser E2E against localhost:8080.
// Full flow: login -> seed categories/values -> create scratch parent item ->
// assign categories on the item's Product Attributes tab (Add Assignment box) ->
// Generate Combinations -> Create Child Product -> verify children + that a
// child is shown read-only as a variation of the parent (issue #52 regression).
//
// Selector notes (kept in sync with the admin/item-tab rendering in
// public/index.php and the Variations UI classes):
//   - admin add forms submit via hidden action=upsert_category / upsert_value.
//   - the item Add Assignment box is emitted by AddAssignmentForm (prefix pa_):
//     select#pa_category_select, checkbox name=add_all, submit name=add_pa_assignment.
//   - variations actions: input[name=generate_combos], input[name=create_child_product].
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');

const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';

(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu'] });
  const context = await browser.newContext();
  const page = await context.newPage();
  const results = [];
  const nav = (p) => page.goto(BASE + p, { waitUntil: 'networkidle', timeout: 45000 });

  async function step(name, fn) {
    try { await fn(); results.push({ name, pass: true }); console.log('PASS  ' + name); }
    catch (e) { results.push({ name, pass: false, error: e.message }); console.log('FAIL  ' + name + ' -> ' + e.message); }
  }

  // Submit the POST form whose hidden action matches, then wait for the 30x/GET
  // redirect (upsert handlers issue header('Location: ...')).
  async function submitFormByAction(action) {
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }),
      page.evaluate((act) => {
        const f = Array.from(document.querySelectorAll('form')).find(fr => {
          const a = fr.querySelector('input[name="action"]');
          return a && a.value === act;
        });
        if (!f) throw new Error('form action=' + act + ' not found');
        f.submit();
      }, action),
    ]);
  }

  // Switch the item page to the given tab by re-posting the items form with a
  // modified hidden _tabs_sel (FA tab mechanism used by tabbed_content_start).
  // The POST is a full-page navigation — race it with waitForNavigation so the
  // next step never sees a half-loaded page.
  async function openItemTab(tabKey) {
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load', timeout: 30000 }),
      page.evaluate((tk) => {
        const f = Array.from(document.querySelectorAll('form')).find(fr =>
          fr.querySelector('input[name="_tabs_sel"]') || fr.querySelector('select[name="_tabs_sel"]'));
        if (!f) throw new Error('no form carrying _tabs_sel');
        const el = f.querySelector('input[name="_tabs_sel"]') || f.querySelector('select[name="_tabs_sel"]');
        el.value = tk;
        f.submit();
      }, tabKey),
    ]);
    await page.waitForTimeout(500);
  }

  // AJAX-submit buttons (class ajaxsubmit — addupdate, generate_combos,
  // create_child_product) do NOT trigger a page navigation. Poll the DOM for
  // the confirmation text instead of waiting for navigation/network idle.
  async function submitButton(selector, confirmRegex, label) {
    await page.click(selector);
    const deadline = Date.now() + 30000;
    let last = '';
    while (Date.now() < deadline) {
      last = await page.content().catch(() => '');
      if (confirmRegex.test(last)) return;
      await page.waitForTimeout(300);
    }
    throw new Error(label + ': confirmation not seen; html=' + last.slice(0, 300));
  }

  // ---------------- Login ----------------
  await step('Login opencode/opencode', async () => {
    await nav('/');
    await page.fill('input[name="user_name_entry_field"]', 'opencode');
    await page.fill('input[name="password"]', 'opencode');
    await page.click('input[type="submit"]');
    await page.waitForLoadState('networkidle');
    if (await page.locator('input[name="password"]').count() > 0) throw new Error('login failed');
  });

  // ---------------- Seed admin categories ----------------
  async function adminAddCategory(code, label) {
    await nav('/modules/FA_ProductAttributes/public/index.php?tab=attributes&sub=categories');
    await page.fill('input[name="code"]', code);
    await page.fill('input[name="label"]', label);
    await page.fill('input[name="sort_order"]', '10');
    await submitFormByAction('upsert_category');
  }
  async function adminAddValue(categoryId, value, slug) {
    await nav('/modules/FA_ProductAttributes/public/index.php?tab=attributes&sub=values&category_id=' + categoryId);
    await page.fill('input[name="value"]', value);
    await page.fill('input[name="slug"]', slug);
    await submitFormByAction('upsert_value');
  }
  async function discoverCatId(code) {
    await nav('/modules/FA_ProductAttributes/public/index.php?tab=attributes&sub=values');
    const opts = await page.locator('select[name="category_id"] option').evaluateAll(os => os.map(o => ({ v: o.value, t: o.text })));
    for (const o of opts) if (o.t === code) return o.v;
    throw new Error('category not found: ' + code);
  }

  await step('Seed categories Color E2E + Size E2E', async () => {
    await adminAddCategory('colore2e', 'Color E2E');
    await adminAddCategory('sizee2e', 'Size E2E');
  });
  const catColor = await discoverCatId('colore2e');
  const catSize = await discoverCatId('sizee2e');
  console.log('   catColor=' + catColor + ' catSize=' + catSize);

  await step('Seed values Red/Blue (Color) + Medium (Size)', async () => {
    await adminAddValue(catColor, 'Red E2E', 'red-e2e');
    await adminAddValue(catColor, 'Blue E2E', 'blue-e2e');
    await adminAddValue(catSize, 'Medium E2E', 'med-e2e');
  });

  // ---------------- Create scratch parent item ----------------
  // Keep the parent id SHORT: FA limits stock_id to 32 chars and child ids are
  // <parent>-<slug chain> (e.g. E2EP690154-blue-e2e-med-e2e = 27 chars).
  const pk = Date.now().toString().slice(-6);
  const parentId = 'E2EP' + pk;

  await step('Create scratch parent item ' + parentId, async () => {
    await nav('/inventory/manage/items.php?NewItem=1');
    await page.fill('input[name="NewStockID"]', parentId);
    await page.fill('input[name="description"]', 'E2E Parent Product ' + pk);
    await page.fill('textarea[name="long_description"]', 'Parent for E2E variations test');
    await submitButton('button[name="addupdate"]', /A new item has been added/i, 'addupdate');
    await nav('/inventory/manage/items.php?stock_id=' + parentId);
    await page.waitForLoadState('networkidle');
    const v = await page.inputValue('input[name="_stock_id_edit"]').catch(() => '');
    if (v !== parentId) throw new Error('parent item not created; got stock_id_edit=' + v);
  });

  // ---------------- Assign categories on the parent (item Product Attributes tab) ----------------
  async function assignCategoryOnItem(catId) {
    await nav('/inventory/manage/items.php?stock_id=' + parentId);
    await page.waitForLoadState('networkidle');
    await openItemTab('product_attributes');
    const sel = page.locator('select#pa_category_select');
    if (await sel.count() === 0) throw new Error('pa_category_select not present on product_attributes tab');
    await sel.selectOption({ value: String(catId) });
    await page.check('input[name="add_all"]');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load', timeout: 30000 }),
      page.click('button[name="add_pa_assignment"]'),
    ]);
    await page.waitForTimeout(250);
    const txt = await page.content();
    if (!/assignment\(s\) added|already assigned/i.test(txt)) throw new Error('no assignment confirmation: ' + txt.slice(0, 200));
  }

  await step('Assign Color category (Add All) on parent Product Attributes tab', async () => assignCategoryOnItem(catColor));
  await step('Assign Size category (Add All) on parent Product Attributes tab', async () => assignCategoryOnItem(catSize));

  // ---------------- Generate Combinations ----------------
  await step('Navigate to Variations tab (re-post form with _tabs_sel=product_variations)', async () => {
    await nav('/inventory/manage/items.php?stock_id=' + parentId);
    await page.waitForLoadState('networkidle');
    await openItemTab('product_variations');
  });

  await step('Verify Generate Combinations + Create Child Product buttons present', async () => {
    const html = await page.content();
    const hasCombos = html.includes('generate_combos') && html.includes('Generate Combinations');
    const hasChild = html.includes('create_child_product') && html.includes('Create Child Product');
    if (!hasCombos || !hasChild) throw new Error('expected buttons missing; html=' + html.slice(0, 200));
  });

  await step('Click Generate Combinations (persists pool)', async () => {
    await submitButton('button[name="generate_combos"]', /Combination set saved|already up to date/i, 'generate_combos');
  });

  await step('Click Create Child Product (instantiate + reconcile)', async () => {
    await submitButton('button[name="create_child_product"]', /Generate Child complete/i, 'create_child_product');
  });

  // ---------------- Verify children + read-only child protection ----------------
  await step('Verify children instantiated and listed', async () => {
    await nav('/inventory/manage/items.php?stock_id=' + parentId);
    await page.waitForLoadState('networkidle');
    await openItemTab('product_variations');
    const body = await page.locator('body').innerText();
    const childIds = body.split('\n').map(l => l.trim()).filter(l => l.startsWith(parentId + '-') && l.length > parentId.length + 1);
    if (childIds.length === 0) throw new Error('no child stock ids found in Existing Variations');
    console.log('   children: ' + childIds.join(', '));
    if (childIds.length < 2) console.log('   WARN: expected 2 combos (2x1), found ' + childIds.length);
  });

  await step('Child item is shown read-only as a variation of parent (issue #52)', async () => {
    // Re-open parent tab, extract the first child id from Existing Variations.
    await nav('/inventory/manage/items.php?stock_id=' + parentId);
    await page.waitForLoadState('networkidle');
    await openItemTab('product_variations');
    const body = await page.locator('body').innerText();
    const childLine = body.split('\n').map(l => l.trim()).find(l => l.startsWith(parentId + '-') && l.length > parentId.length + 1);
    if (!childLine) throw new Error('could not extract a child id');
    const childId = childLine.split(/\s+/)[0]; // strip the trailing " - Description" column

    await nav('/inventory/manage/items.php?stock_id=' + encodeURIComponent(childId));
    await page.waitForLoadState('networkidle');
    await openItemTab('product_variations');

    // Auto-waiting locator (retries past the tab-content AJAX injection) instead
    // of html.includes() — the parent picker's option text also contains
    // "Parent Product", so scope to the fieldset text.
    await page.locator('text=This product is a variation of').first().waitFor({ timeout: 30000 });
    const createButtons = await page.locator('button[name="create_child_product"], button[name="generate_combos"]').count();
    if (createButtons > 0) throw new Error('child variations tab still shows Generate/Create buttons (should be read-only)');
  });

  console.log('\n=== SUMMARY ===');
  const fail = results.filter(r => !r.pass);
  console.log('PASSED ' + (results.length - fail.length) + '/' + results.length);
  fail.forEach(f => console.log('  FAIL: ' + f.name + ': ' + f.error));
  await browser.close();
})().catch(e => { console.error('FATAL', e.message); process.exit(1); });