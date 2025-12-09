<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
  header('Location: login.php');
  exit;
}

// Ensure service_items table exists to avoid fatal error on first load
try {
  $pdo->query("SELECT 1 FROM service_items LIMIT 1");
} catch (Exception $e) {
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_slug VARCHAR(100) NOT NULL,
        name VARCHAR(150) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (service_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Exception $ignored) { /* no-op */ }
}

// Seed core services if they are missing (keeps admin page in sync with homepage)
$defaultServices = [
  ['name' => 'Elderly Care',   'slug' => 'ElderlyCare',  'base_price' => 0],
  ['name' => 'Patient Care',   'slug' => 'PatientCare',  'base_price' => 0],
  ['name' => 'Babysitting',    'slug' => 'Babysitting',  'base_price' => 0],
  ['name' => 'Lab Testing',    'slug' => 'LabTesting',   'base_price' => 0],
];
try {
  $checkStmt = $pdo->prepare("SELECT id FROM services WHERE slug = ? LIMIT 1");
  $insertStmt = $pdo->prepare("INSERT INTO services (name, slug, base_price, active) VALUES (?,?,?,1)");
  foreach ($defaultServices as $svc) {
    $checkStmt->execute([$svc['slug']]);
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
      $insertStmt->execute([$svc['name'], $svc['slug'], $svc['base_price']]);
    }
  }
} catch (Exception $e) { /* ignore seeding errors */ }

// Helpers
function make_slug($text) {
  $text = strtolower($text);
  $text = preg_replace('/[^a-z0-9]+/', '', $text);
  return $text;
}

// Create / Update / Toggle / Delete services
$flash = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && (isset($_POST['create']) || isset($_POST['update']) || isset($_POST['delete']))) {
  if (isset($_POST['create'])) {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $price = (float)($_POST['base_price'] ?? 0);
    $slug = make_slug($slug !== '' ? $slug : $name);
    if ($slug === '') { $slug = 'service'.time(); }
    $stmt = $pdo->prepare("INSERT INTO services (name, slug, base_price, active) VALUES (?,?,?,1)");
    $stmt->execute([$name,$slug,$price]);
    $flash = "Service '{$name}' added.";
  } elseif (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $slug = make_slug(trim($_POST['slug']) !== '' ? trim($_POST['slug']) : $name);
    $price = (float)($_POST['base_price'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE services SET name=?, slug=?, base_price=?, active=? WHERE id=?");
    $stmt->execute([$name,$slug,$price,$active,$id]);
    $flash = "Service '{$name}' updated.";
  } elseif (isset($_POST['delete'])) {
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM services WHERE id=?")->execute([$id]);
    $flash = "Service deleted.";
  }
  $redir = 'admin_services.php';
  if (isset($_GET['tab'])) { $redir .= '?tab='.urlencode($_GET['tab']); }
  header('Location: '.$redir.(strpos($redir,'?')===false ? '?' : '&').'msg='.urlencode($flash));
  exit;
}

$services = $pdo->query("SELECT * FROM services ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$serviceCount = count($services);
$serviceNameMap = [];
foreach ($services as $srv) {
  $serviceNameMap[$srv['slug']] = $srv['name'];
}

// Manage service items (CRUD)
if ($_SERVER['REQUEST_METHOD']==='POST' && (isset($_POST['item_create']) || isset($_POST['item_update']) || isset($_POST['item_delete']) || isset($_POST['item_bulk_import']))) {
  if (isset($_POST['item_create'])) {
    $service_slug = trim($_POST['service_slug']);
    $item_name = trim($_POST['item_name']);
    $item_price = (float)($_POST['item_price'] ?? 0);
    $stmt = $pdo->prepare("INSERT INTO service_items (service_slug, name, price, active) VALUES (?,?,?,1)");
    $stmt->execute([$service_slug,$item_name,$item_price]);
    header('Location: admin_services.php?tab=items&service='.$service_slug);
    exit;
  } elseif (isset($_POST['item_update'])) {
    $id = (int)$_POST['id'];
    $service_slug = trim($_POST['service_slug']);
    $item_name = trim($_POST['item_name']);
    $item_price = (float)($_POST['item_price'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE service_items SET service_slug=?, name=?, price=?, active=? WHERE id=?");
    $stmt->execute([$service_slug,$item_name,$item_price,$active,$id]);
    header('Location: admin_services.php?tab=items&service='.$service_slug);
    exit;
  } elseif (isset($_POST['item_delete'])) {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM service_items WHERE id=?");
    $stmt->execute([$id]);
    header('Location: admin_services.php?tab=items');
    exit;
  } elseif (isset($_POST['item_bulk_import'])) {
    $service_slug = trim($_POST['service_slug']);
    $lines = preg_split("/\r?\n/", trim($_POST['bulk_text'] ?? ''));
    $ins = $pdo->prepare("INSERT INTO service_items (service_slug, name, price, active) VALUES (?,?,?,1)");
    foreach ($lines as $line) {
      if ($line === '') continue;
      // support CSV: name,price or tab-separated
      $parts = preg_split('/[,\t]/', $line);
      $name = trim($parts[0] ?? '');
      $price = (float)trim($parts[1] ?? '0');
      if ($name !== '') { $ins->execute([$service_slug, $name, $price]); }
    }
    header('Location: admin_services.php?tab=items&service='.$service_slug);
    exit;
  }
}

$serviceFilter = $_GET['service'] ?? '';
$coreCategories = [
  'ElderlyCare' => 'Elderly Care',
  'PatientCare' => 'Patient Care',
  'Babysitting' => 'Babysitting',
  'LabTesting' => 'Lab Testing',
];
try {
  if ($serviceFilter) {
    $itemsStmt = $pdo->prepare("SELECT * FROM service_items WHERE LOWER(service_slug)=LOWER(?) ORDER BY id DESC");
    $itemsStmt->execute([$serviceFilter]);
  } else {
    $itemsStmt = $pdo->prepare("SELECT * FROM service_items ORDER BY id DESC");
    $itemsStmt->execute();
  }
  $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $items = [];
}
$itemCount = count($items);
$itemsByService = [];
foreach ($items as $it) {
  $itemsByService[$it['service_slug']][] = $it;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Manage Services</title>
  <link rel="stylesheet" href="assets/styles.css">
  <script src="assets/theme.js"></script>
  <script>
    // Auto-slug for quick add
    document.addEventListener('DOMContentLoaded', function(){
      var nameEl = document.getElementById('newServiceName');
      var slugEl = document.getElementById('newServiceSlug');
      if(nameEl && slugEl){
        nameEl.addEventListener('input', function(){
          var slug = (nameEl.value || '').toLowerCase().replace(/[^a-z0-9]+/g,'');
          slugEl.value = slug;
        });
      }
    });
  </script>
</head>
<body>
<?php include 'partials/nav.php'; ?>
<div class="container" style="max-width:1100px;margin:80px auto 20px;">
  <h2>Manage Services</h2>
  <p style="margin:6px 0 16px 0;">Create services, keep prices up to date, and manage the item catalog for each service.</p>
  <?php if(!empty($_GET['msg'])): ?>
    <p class="message" style="margin:8px 0 14px 0;"><?= htmlspecialchars($_GET['msg']) ?></p>
  <?php endif; ?>

  <div class="card" style="margin-bottom:16px; display:flex; flex-wrap:wrap; gap:8px; align-items:center; justify-content:space-between;">
    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
      <strong>Services:</strong> <?= $serviceCount ?> &nbsp;|&nbsp;
      <strong>Items:</strong> <?= $itemCount ?>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <a class="btn btn-outline" href="#services">Go to Services</a>
      <a class="btn btn-outline" href="#items">Go to Service Items</a>
    </div>
  </div>

  <div class="grid grid-2" style="gap:16px; align-items:flex-start;">
    <!-- Services column -->
    <div id="services">
      <div class="card mb-3">
        <h3>Add New Service</h3>
        <p class="muted" style="margin-top:4px;">Slug auto-generates from the name (letters/numbers only) so links and client forms work.</p>
        <form method="post" class="form-grid mt-2">
          <div><label>Name</label><input name="name" id="newServiceName" required></div>
          <div><label>Slug</label><input name="slug" id="newServiceSlug" placeholder="auto-fill from name"></div>
          <div><label>Base Price</label><input type="number" step="0.01" name="base_price" value="0"></div>
          <div class="right"><button class="btn btn-primary" name="create" type="submit">Create</button></div>
        </form>
      </div>

      <div class="card">
        <h3 style="margin-bottom:10px;">Services</h3>
        <table class="table">
          <thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Base Price</th><th>Active</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if(empty($services)): ?>
              <tr><td colspan="6" style="text-align:center; padding:16px;">No services yet. Add one above to get started.</td></tr>
            <?php else: ?>
            <?php foreach($services as $s): ?>
            <tr>
              <form method="post">
                <td><?= (int)$s['id'] ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"></td>
                <td><input name="name" value="<?= htmlspecialchars($s['name']) ?>" style="width:100%"></td>
                <td><input name="slug" value="<?= htmlspecialchars($s['slug']) ?>" style="width:100%"></td>
                <td><input type="number" step="0.01" name="base_price" value="<?= htmlspecialchars($s['base_price']) ?>" style="max-width:120px"></td>
                <td style="text-align:center;">
                  <label class="flex" style="gap:6px;align-items:center; justify-content:center;">
                    <input type="checkbox" name="active" <?= $s['active']? 'checked':'' ?>> Active
                  </label>
                </td>
                <td style="white-space:nowrap;">
                  <button class="btn btn-primary" name="update" type="submit">Update</button>
                  <button class="btn btn-danger" name="delete" type="submit" onclick="return confirm('Delete service?')">Delete</button>
                </td>
              </form>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Items column -->
    <div id="items">
      <div class="card">
        <h3>Service Items</h3>
        <p class="muted" style="margin-top:4px;">Link individual tasks/tests to a service. Filter first, then add or bulk import.</p>
        <div class="flex" style="gap:8px; flex-wrap:wrap; margin:8px 0;">
          <strong>Quick categories:</strong>
          <?php foreach($coreCategories as $slug => $label): ?>
            <a class="btn btn-outline" href="admin_services.php?tab=items&service=<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($label) ?></a>
          <?php endforeach; ?>
          <a class="btn btn-outline" href="admin_services.php?tab=items">All</a>
        </div>
        <form method="get" class="flex" style="gap:8px;align-items:center;">
          <input type="hidden" name="tab" value="items">
          <label for="service">Filter by Service</label>
          <select id="service" name="service" onchange="this.form.submit()">
            <option value="">All</option>
            <?php foreach($services as $s): ?>
              <option value="<?= htmlspecialchars($s['slug']) ?>" <?= ($serviceFilter===$s['slug'])?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <noscript><button class="btn btn-primary" type="submit">Filter</button></noscript>
        </form>

        <h4 class="mt-2">Add Item</h4>
        <form method="post" class="form-grid mt-2">
          <div>
            <label>Service</label>
            <select name="service_slug" required>
              <?php foreach($services as $s): ?>
                <option value="<?= htmlspecialchars($s['slug']) ?>" <?= ($serviceFilter===$s['slug'])?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div><label>Item Name</label><input name="item_name" required placeholder="CBC / Dressing"></div>
          <div><label>Price</label><input type="number" step="0.01" name="item_price" value="0" required></div>
          <div class="right"><button class="btn btn-primary" name="item_create" type="submit">Add Item</button></div>
        </form>

        <h4 class="mt-3">Bulk Import Items</h4>
        <p class="muted" style="margin-top:-6px;">One item per line: <code>Name,Price</code> or tab separated.</p>
        <form method="post" class="form-grid mt-2">
          <div>
            <label>Service</label>
            <select name="service_slug" required>
              <?php foreach($services as $s): ?>
                <option value="<?= htmlspecialchars($s['slug']) ?>" <?= ($serviceFilter===$s['slug'])?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="grid-column: span 3;">
            <label>Paste items (Name,Price per line)</label>
            <textarea name="bulk_text" rows="6" placeholder="CBC,350&#10;Lipid Profile,800&#10;Blood Sugar,200"></textarea>
          </div>
          <div class="right"><button class="btn btn-primary" name="item_bulk_import" type="submit">Import</button></div>
        </form>
      </div>

      <div class="card mt-3">
        <h3 style="margin-bottom:10px;">Items by Service</h3>
        <?php if(empty($itemsByService)): ?>
          <p class="muted" style="margin:0;">No items found for this filter.</p>
        <?php else: ?>
          <?php foreach($itemsByService as $slug => $list): ?>
            <details open style="margin-bottom:8px;">
              <summary style="cursor:pointer; font-weight:600; padding:8px 0;">
                <?= htmlspecialchars($serviceNameMap[$slug] ?? $slug) ?> (<?= count($list) ?>)
              </summary>
              <table class="table">
                <thead><tr><th>ID</th><th>Name</th><th>Price</th><th>Active</th><th>Actions</th></tr></thead>
                <tbody>
                  <?php foreach($list as $it): ?>
                  <tr>
                    <td><?= (int)$it['id'] ?></td>
                    <td><?= htmlspecialchars($it['name']) ?></td>
                    <td><?= htmlspecialchars($it['price']) ?></td>
                    <td><?= $it['active'] ? 'Yes' : 'No' ?></td>
                    <td>
                      <form method="post" class="flex" style="gap:6px; flex-wrap:wrap;">
                        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                        <input type="hidden" name="service_slug" value="<?= htmlspecialchars($it['service_slug']) ?>">
                        <button class="btn btn-primary" name="item_update" type="submit" onclick="return confirm('Edit this item?');">Edit</button>
                        <button class="btn btn-danger" name="item_delete" type="submit" onclick="return confirm('Delete item?')">Delete</button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </details>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<script src="assets/nav.js"></script>
<script>
  (function(){
    const toggle = document.getElementById('themeToggle');
    if(!toggle) return;
    const root = document.documentElement;
    const saved = localStorage.getItem('theme');
    if (saved === 'dark') { root.classList.add('theme-dark'); }
    const updateLabel = () => {
      toggle.textContent = root.classList.contains('theme-dark') ? 'Light Mode' : 'Dark Mode';
    };
    updateLabel();
    toggle.addEventListener('click', function(){
      const isDark = root.classList.toggle('theme-dark');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
      updateLabel();
    });
  })();
</script>
</body>
</html>
