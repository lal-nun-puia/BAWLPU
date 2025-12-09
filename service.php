<?php
session_start();
require_once 'db.php'; // $pdo

// require logged-in client
$logged_in = isset($_SESSION['user']);
if (!$logged_in) {
    header('Location: login.php');
    exit();
}
$user = $_SESSION['user'];
if ($user['role'] !== 'Client') {
    // Only clients can create requests here
    header('Location: index.php');
    exit();
}

$service = isset($_GET['type']) ? $_GET['type'] : '';

// Pull active services from DB; fall back to defaults if none found
$activeServices = [];
try {
    $svcStmt = $pdo->query("SELECT slug, name FROM services WHERE active = 1 ORDER BY id DESC");
    foreach ($svcStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $slug = trim($row['slug'] ?? '');
        if ($slug !== '') {
            $activeServices[$slug] = $row['name'];
        }
    }
} catch (Exception $e) { /* ignore */ }

$allowed = !empty($activeServices) ? array_keys($activeServices) : ['Babysitting','ElderlyCare','PatientCare','LabTesting'];
$fallbackService = !empty($allowed) ? $allowed[0] : 'Babysitting';
if (!in_array($service, $allowed)) {
    $service = $fallbackService;
}
$serviceTitle = $activeServices[$service] ?? $service;

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Selected items and estimated cost
    $items_json = trim($_POST['items_json'] ?? '');
    $estimated_cost = floatval($_POST['estimated_cost'] ?? 0);

    if ($patient_name === '' || $phone === '') {
        $msg = "Please provide at least patient name and phone.";
    } else {
        // Try to insert with pricing columns if they exist
        $columns = $pdo->query('DESCRIBE client_requests')->fetchAll(PDO::FETCH_COLUMN, 0);
        if (in_array('items_json', $columns) && in_array('estimated_cost', $columns)) {
            $stmt = $pdo->prepare("INSERT INTO client_requests (client_id, service_type, patient_name, age, address, phone, notes, items_json, estimated_cost) VALUES (?,?,?,?,?,?,?,?,?)");
            $ok = $stmt->execute([$user['user_id'], $service, $patient_name, $age, $address, $phone, $notes, $items_json, $estimated_cost]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO client_requests (client_id, service_type, patient_name, age, address, phone, notes) VALUES (?,?,?,?,?,?,?)");
            $ok = $stmt->execute([$user['user_id'], $service, $patient_name, $age, $address, $phone, $notes]);
        }
        if ($ok) {
            $msg = "Request saved. Nurses will see this job.";
        } else {
            $msg = "Failed to save. Try again.";
        }
    }
}

// Load service items for this service
$items = [];
try {
    $stmt = $pdo->prepare("SELECT id, name, price FROM service_items WHERE LOWER(service_slug) = LOWER(?) AND active = 1 ORDER BY id DESC");
    $stmt->execute([$service]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* ignore */ }

$defaultCatalog = [
    'Babysitting' => [
        ['Emergency Care', 300],
        ['Bedtime Routine', 150],
        ['Diaper Changing and Hygiene', 100],
        ['Playtime and Activities', 100],
        ['Homework Assistance', 200],
        ['Meal Preparation for Child', 150],
        ['Overnight Babysitting', 1200],
        ['Evening Babysitting (2 hours)', 250],
        ['Half Day Babysitting (4 hours)', 400],
        ['Full Day Babysitting (8 hours)', 800],
    ],
    'ElderlyCare' => [
        ['Night Care Visit', 1000],
        ['Bathing & Grooming', 350],
        ['Feeding Support', 250],
        ['Dementia Care Visit', 900],
        ['Physiotherapy Session', 700],
        ['Pressure Sore Care', 500],
        ['Companionship Session', 300],
        ['Mobility Support', 350],
        ['Medication Reminder', 200],
        ['Daily Assistance Visit', 400],
    ],
    'PatientCare' => [
        ['Medication Administration', 300],
        ['Blood Pressure Check', 120],
        ['Blood Sugar Monitoring', 150],
        ['Personal Hygiene Support', 350],
        ['Nebulization', 250],
        ['Catheter Care', 600],
        ['Post-Operative Care Visit', 800],
        ['IV/Injection Administration', 500],
        ['Wound Dressing', 450],
        ['Vital Signs Monitoring', 300],
    ],
    'LabTesting' => [
        ['Complete Blood Count (CBC)', 500],
        ['Blood Sugar (Fasting/PP)', 250],
        ['Lipid Profile', 800],
        ['Liver Function Test', 900],
        ['Kidney Function Test', 750],
        ['Thyroid Profile', 650],
        ['Vitamin D Test', 1200],
        ['Urine Routine Test', 300],
        ['COVID-19 RT-PCR', 1600],
        ['Electrolyte Panel', 700],
    ],
];

if (empty($items) && isset($defaultCatalog[$service])) {
    $items = [];
    foreach ($defaultCatalog[$service] as $idx => $row) {
        $items[] = [
            'id' => -($idx + 1),
            'name' => $row[0],
            'price' => $row[1],
        ];
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Request - <?php echo htmlspecialchars($serviceTitle); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css">
  <script src="assets/theme.js"></script>
  <style>
    body{font-family:Poppins, sans-serif;background:var(--bg);color:var(--text);padding:20px}
    .card{max-width:600px;margin:20px auto;background:var(--card);padding:20px;border-radius:12px;box-shadow:var(--shadow)}
    input, textarea{width:100%;padding:10px;margin:8px 0;border:1px solid var(--input-border, #cfeee8);border-radius:8px;background:var(--card);color:var(--text)}
    button{background:var(--primary);color:#fff;border:none;padding:12px 18px;border-radius:8px;cursor:pointer}
    button:hover{background:var(--primary-700);}
    .msg{padding:10px;margin:10px 0;border-radius:8px;background:rgba(34,197,94,0.15);color:var(--text);border:1px solid rgba(34,197,94,0.35)}
    .items{margin:10px 0;padding:10px;border:1px solid var(--input-border, #cfeee8);border-radius:8px;background:var(--card)}
    .items h4{margin:0 0 12px 0}
    .items-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;max-height:360px;overflow-y:auto;padding-right:6px}
    .items-search{display:flex;gap:10px;align-items:center;margin-bottom:10px}
    .items-search input{flex:1;padding:10px;border:1px solid var(--input-border, #cfeee8);border-radius:8px;background:var(--card);color:var(--text)}
    .items-search .hint{font-size:12px;color:var(--muted)}
    .item-card{border:1px solid var(--input-border, #e0f2f1);border-radius:10px;padding:12px;background:var(--card);display:flex;flex-direction:column;gap:6px}
    .item-title{font-weight:600;color:var(--text)}
    .item-price{color:var(--primary);font-weight:600}
    .item-actions{display:flex;gap:8px;align-items:center}
    .qty-input{width:80px;padding:6px;border:1px solid var(--input-border, #cfeee8);border-radius:6px;background:var(--card);color:var(--text)}
    .add-btn{background:var(--primary);color:#fff;border:none;padding:8px 12px;border-radius:8px;cursor:pointer}
    .add-btn:hover{background:var(--primary-700)}
    .selected-list{margin-top:12px}
    .selected-row{display:flex;gap:12px;align-items:center;justify-content:space-between;border:1px solid var(--input-border, #e0f2f1);padding:8px;border-radius:8px;background:var(--card);margin:6px 0;flex-wrap:wrap}
    .selected-row .name{flex:1;min-width:150px;font-weight:500}
    .selected-row .qty{width:80px}
    .selected-row .price{min-width:120px;text-align:right;font-weight:600}
    .selected-row button{background:var(--danger);color:#fff;border:none;padding:6px 10px;border-radius:6px;cursor:pointer}
    .selected-row button:hover{filter:brightness(0.9)}
    .total{font-weight:700;margin-top:12px}
    .no-results{padding:10px;border:1px dashed var(--input-border, #cfeee8);border-radius:8px;background:var(--card);color:var(--muted);text-align:center;font-size:14px}
    .hidden{display:none}
  </style>
</head>
<body>
  <div class="card">
    <h2>Request for <?php echo htmlspecialchars($serviceTitle); ?></h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:16px;">
      <button onclick="window.history.back()" class="btn btn-outline">Back</button>
      <?php if(count($allowed) > 1): ?>
        <div style="display:flex; gap:6px; flex-wrap:wrap;">
          <?php foreach($allowed as $slug): ?>
            <a class="btn btn-outline" href="service.php?type=<?= urlencode($slug) ?>" style="<?= $slug===$service ? 'background:var(--primary);color:#fff;' : '' ?>">
              <?= htmlspecialchars($activeServices[$slug] ?? $slug) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php if($msg) echo "<div class='msg'>".htmlspecialchars($msg)."</div>"; ?>
    <form method="post" onsubmit="syncItemsBeforeSubmit()">
      <label>Patient / Person Name *</label>
      <input type="text" name="patient_name" required>

      <label>Age</label>
      <input type="text" name="age">

      <label>Address</label>
      <input type="text" name="address">

      <label>Phone *</label>
      <input type="text" name="phone" pattern="[0-9+ -]{6,20}" required>

      <label>Additional notes</label>
      <textarea name="notes"></textarea>

      <?php if(!empty($items)): ?>
      <div class="items">
        <h4>Select one or more services</h4>
        <div class="items-search">
          <input type="search" id="itemFilter" placeholder="Search services (e.g., wound care, feeding)">
          <span class="hint">Type to filter</span>
        </div>
        <div class="items-grid">
          <?php foreach($items as $it): ?>
            <div class="item-card" data-name="<?= htmlspecialchars(strtolower($it['name']), ENT_QUOTES, 'UTF-8') ?>">
              <div class="item-title"><?= htmlspecialchars($it['name']) ?></div>
              <div class="item-price">&#8377; <?= number_format((float)$it['price'], 2) ?></div>
              <div class="item-actions">
                <input type="number" class="qty-input" id="qty-<?= (int)$it['id'] ?>" min="1" step="1" value="1">
                <button type="button" class="add-btn" onclick="addItem(<?= (int)$it['id'] ?>)">Add</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div id="noResults" class="no-results hidden">No services match your search.</div>
        <div id="selectedList" class="selected-list"></div>
        <div class="total">Estimated Total: &#8377; <span id="estTotal">0.00</span></div>
      </div>
      <?php else: ?>
      <div class="msg">No selectable items for this service yet. Please contact admin to add items.</div>
      <?php endif; ?>

      <input type="hidden" name="items_json" id="itemsJson">
      <input type="hidden" name="estimated_cost" id="estimatedCost">

      <input type="hidden" name="create_request" value="1">
      <button type="submit">Save Request</button>
    </form>
    <p style="margin-top:12px"><a href="index.php">Back to Home</a></p>
  </div>
</body>
</html>

<script>
// Catalog of tests for this service
const ITEM_CATALOG = <?php echo json_encode(array_map(function($it){return ['id'=>(int)$it['id'],'name'=>$it['name'],'price'=>(float)$it['price']];}, $items), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
let selectedItems = [];

function filterItems(term){
  const t = (term || '').trim().toLowerCase();
  const cards = document.querySelectorAll('.item-card');
  let visible = 0;
  cards.forEach(card => {
    const name = card.dataset.name || '';
    const match = !t || name.includes(t);
    card.style.display = match ? 'flex' : 'none';
    if (match) visible++;
  });
  const noRes = document.getElementById('noResults');
  if (noRes) noRes.classList.toggle('hidden', visible > 0);
}

function addItem(id){
  const item = ITEM_CATALOG.find(it => it.id === id);
  if(!item) return;
  const qtyInput = document.getElementById(`qty-${id}`);
  let qtyVal = parseInt((qtyInput && qtyInput.value) || '1', 10);
  if (isNaN(qtyVal) || qtyVal < 1) qtyVal = 1;
  if (qtyInput) qtyInput.value = qtyVal;
  const existing = selectedItems.find(x => x.id === id);
  if(existing){ existing.qty += qtyVal; }
  else { selectedItems.push({ id: item.id, name: item.name, price: item.price, qty: qtyVal }); }
  renderSelected();
}

function updateQty(id, qty){
  const q = parseInt(qty||'0',10);
  const row = selectedItems.find(x => x.id===id);
  if(!row) return;
  row.qty = isNaN(q)||q<1 ? 1 : q;
  renderSelected();
}

function removeItem(id){
  selectedItems = selectedItems.filter(x => x.id !== id);
  renderSelected();
}

function renderSelected(){
  const list = document.getElementById('selectedList');
  list.innerHTML = '';
  let total = 0;
  selectedItems.forEach(it => {
    const line = document.createElement('div');
    line.className = 'selected-row';
    const left = document.createElement('div'); left.className='name'; left.textContent = it.name;
    const mid = document.createElement('div');
    const qty = document.createElement('input'); qty.type='number'; qty.min='1'; qty.step='1'; qty.value = it.qty; qty.className='qty'; qty.onchange = (e)=>updateQty(it.id, e.target.value);
    mid.appendChild(qty);
    const right = document.createElement('div'); right.className='price'; const subtotal = it.price * it.qty; right.textContent = "\u20B9 " + subtotal.toFixed(2);
    const rm = document.createElement('button'); rm.type='button'; rm.textContent='Remove'; rm.onclick=()=>removeItem(it.id);
    line.appendChild(left); line.appendChild(mid); line.appendChild(right); line.appendChild(rm);
    list.appendChild(line);
    total += subtotal;
  });
  document.getElementById('estTotal').innerText = total.toFixed(2);
  document.getElementById('itemsJson').value = JSON.stringify(selectedItems);
  document.getElementById('estimatedCost').value = total.toFixed(2);
}

function syncItemsBeforeSubmit(){ renderSelected(); }

document.getElementById('itemFilter')?.addEventListener('input', (e) => filterItems(e.target.value));
// initial render to ensure totals/hidden fields stay in sync
filterItems('');
</script>
