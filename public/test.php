<?php
/* ======================
   Minimal CRUD for sub_category_sections
   (only sub_category_id, section_id) + read-only views
   With robust error display + debug panel
   ====================== */

// ---- DEBUG (TEMPORARY — TURN OFF IN PRODUCTION) ----
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// ---- DB CONFIG ----
$DB = [
    'host'    => '127.0.0.1',
    'name'    => 'u664913565_testnielit',
    'user'    => 'u664913565_testnielit',
    'pass'    => 'Nielitbbsr@2025',
    'charset' => 'utf8mb4',
];

// ---- CONNECT ----
try {
    $pdo = new PDO(
        "mysql:host={$DB['host']};dbname={$DB['name']};charset={$DB['charset']}",
        $DB['user'],
        $DB['pass'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

// ---- HELPERS ----
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function table_columns(PDO $pdo, string $table): array {
    $cols = [];
    try {
        $rs = $pdo->query("DESCRIBE `$table`");
        foreach ($rs as $r) $cols[] = $r['Field'];
    } catch (Throwable $e) {}
    return $cols;
}

// ---- CRUD (NO `position`) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO `sub_category_sections` (`sub_category_id`,`section_id`) VALUES (?,?)");
            $stmt->execute([$_POST['sub_category_id'] ?? null, $_POST['section_id'] ?? null]);
        } elseif ($action === 'edit') {
            // If `id` is provided and non-empty, update by id; else use composite key
            if (isset($_POST['id']) && $_POST['id'] !== '') {
                $stmt = $pdo->prepare("UPDATE `sub_category_sections` SET `sub_category_id`=?, `section_id`=? WHERE `id`=?");
                $stmt->execute([$_POST['sub_category_id'] ?? null, $_POST['section_id'] ?? null, $_POST['id']]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE `sub_category_sections`
                       SET `sub_category_id`=?, `section_id`=?
                     WHERE `sub_category_id`=? AND `section_id`=?
                ");
                $stmt->execute([
                    $_POST['sub_category_id'] ?? null,
                    $_POST['section_id'] ?? null,
                    $_POST['orig_sub_category_id'] ?? null,
                    $_POST['orig_section_id'] ?? null,
                ]);
            }
        } elseif ($action === 'delete') {
            if (isset($_POST['id']) && $_POST['id'] !== '') {
                $stmt = $pdo->prepare("DELETE FROM `sub_category_sections` WHERE `id`=?");
                $stmt->execute([$_POST['id']]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM `sub_category_sections` WHERE `sub_category_id`=? AND `section_id`=?");
                $stmt->execute([$_POST['sub_category_id'] ?? null, $_POST['section_id'] ?? null]);
            }
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        // show exact SQL error on the page
        die("CRUD error: " . $e->getMessage());
    }
}

// ---- FETCH ----
try {
    $scs = $pdo->query("SELECT * FROM `sub_category_sections`")->fetchAll();
} catch (PDOException $e) {
    die("Fetch sub_category_sections failed: " . $e->getMessage());
}
try {
    $sections = $pdo->query("SELECT * FROM `sections`")->fetchAll();
} catch (PDOException $e) {
    die("Fetch sections failed: " . $e->getMessage());
}
try {
    $subcats  = $pdo->query("SELECT * FROM `sub_categories`")->fetchAll();
} catch (PDOException $e) {
    die("Fetch sub_categories failed: " . $e->getMessage());
}

// detect columns safely
$hasId = isset($scs[0]) && array_key_exists('id', $scs[0]);

// optional debug panel
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';
$cols_scs = table_columns($pdo, 'sub_category_sections');
$cols_sec = table_columns($pdo, 'sections');
$cols_sub = table_columns($pdo, 'sub_categories');

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Sub Category Sections</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { --card:#fff; --line:#e5e7eb; --muted:#6b7280; --bg:#f8fafc; }
    * { box-sizing:border-box; }
    body { font-family:Arial, sans-serif; margin:20px; background:var(--bg); }
    .container { max-width:1100px; margin:0 auto; }
    h1 { margin:0 0 14px; }
    .card { background:var(--card); border:1px solid var(--line); border-radius:12px; padding:16px; margin-bottom:16px; }
    .card h2 { margin:0 0 12px; font-size:20px; }
    table { width:100%; border-collapse:collapse; background:#fff; }
    th, td { border:1px solid var(--line); padding:8px; vertical-align:top; }
    th { background:#f4f6f8; text-align:left; }
    .row-actions { display:flex; gap:8px; }
    input[type="text"] { width:100%; padding:6px 8px; }
    .btn { padding:6px 10px; border:1px solid #cbd5e1; background:#eef2ff; border-radius:8px; cursor:pointer; }
    .btn.primary { background:#3b82f6; color:#fff; border-color:#3b82f6; }
    .btn.danger  { background:#ef4444; color:#fff; border-color:#ef4444; }
    .muted { color:var(--muted); }
    .grid3 { display:grid; grid-template-columns:repeat(3, minmax(140px,1fr)); gap:8px; }
    @media (max-width:720px){ .grid3 { grid-template-columns:1fr; } }
    .debug { font-family:ui-monospace,Consolas,monospace; font-size:13px; white-space:pre-wrap; background:#fffbe6; border:1px solid #f59e0b; padding:10px; border-radius:8px; }
  </style>
</head>
<body>
<div class="container">

  <h1>Manage Sub Category Sections</h1>

  <?php if ($debug): ?>
    <div class="card debug">
      <strong>DEBUG PANEL</strong>
      <?= "\nsub_category_sections columns: " . h(implode(', ', $cols_scs)) ?>
      <?= "\nsections columns: " . h(implode(', ', $cols_sec)) ?>
      <?= "\nsub_categories columns: " . h(implode(', ', $cols_sub)) . "\n" ?>
      <?= "hasId detected: " . ($hasId ? 'true' : 'false') . "\n" ?>
      <?= "Rows in sub_category_sections: " . count($scs) . "\n" ?>
    </div>
  <?php endif; ?>

  <!-- ADD NEW -->
  <div class="card">
    <h2>Add new row</h2>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="grid3">
        <label>Sub Category ID
          <input type="text" name="sub_category_id" required>
        </label>
        <label>Section ID
          <input type="text" name="section_id" required>
        </label>
        <div style="align-self:end">
          <button class="btn primary" type="submit">Add</button>
        </div>
      </div>
    </form>
  </div>

  <!-- EDITABLE TABLE -->
  <div class="card">
    <h2>Sub Category Sections (Editable)</h2>
    <table>
      <thead>
        <tr>
          <?php if ($hasId): ?><th>id</th><?php endif; ?>
          <th>sub_category_id</th>
          <th>section_id</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$scs): ?>
        <tr><td class="muted" colspan="<?= $hasId ? 4 : 3 ?>">No rows yet.</td></tr>
      <?php else: ?>
        <?php foreach ($scs as $row): ?>
          <tr data-row-id="<?= $hasId ? h($row['id']) : '' ?>">
            <?php if ($hasId): ?>
              <td><?= h($row['id']) ?></td>
            <?php endif; ?>
            <td><input type="text" value="<?= h($row['sub_category_id']) ?>" data-field="sub_category_id"></td>
            <td><input type="text" value="<?= h($row['section_id']) ?>" data-field="section_id"></td>
            <td class="row-actions">
              <button class="btn primary" type="button" onclick="saveRow(this, <?= $hasId ? 'true' : 'false' ?>)">Save</button>
              <form method="post" onsubmit="return confirm('Delete this row?');" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <?php if ($hasId): ?>
                  <input type="hidden" name="id" value="<?= h($row['id']) ?>">
                <?php else: ?>
                  <input type="hidden" name="sub_category_id" value="<?= h($row['sub_category_id']) ?>">
                  <input type="hidden" name="section_id" value="<?= h($row['section_id']) ?>">
                <?php endif; ?>
                <button class="btn danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- READ ONLY: SECTIONS -->
  <div class="card">
    <h2>Sections (Read Only)</h2>
    <table>
      <thead>
        <tr>
          <?php foreach (array_keys($sections[0] ?? ['id'=>1,'name'=>'sample']) as $col): ?>
            <th><?= h($col) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sections as $r): ?>
          <tr><?php foreach ($r as $v): ?><td><?= h($v) ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- READ ONLY: SUB CATEGORIES -->
  <div class="card">
    <h2>Sub Categories (Read Only)</h2>
    <table>
      <thead>
        <tr>
          <?php foreach (array_keys($subcats[0] ?? ['id'=>1,'name'=>'sample']) as $col): ?>
            <th><?= h($col) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($subcats as $r): ?>
          <tr><?php foreach ($r as $v): ?><td><?= h($v) ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Hidden universal form for SAVE -->
  <form id="editForm" method="post" style="display:none;">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="id" value="">
    <input type="hidden" name="orig_sub_category_id" value="">
    <input type="hidden" name="orig_section_id" value="">
    <input type="hidden" name="sub_category_id" value="">
    <input type="hidden" name="section_id" value="">
  </form>

</div>

<script>
function saveRow(btn, hasId){
  const tr = btn.closest('tr');
  const id = tr.dataset.rowId || '';
  const sub_category_id = tr.querySelector('input[data-field="sub_category_id"]').value.trim();
  const section_id      = tr.querySelector('input[data-field="section_id"]').value.trim();

  const f = document.getElementById('editForm');
  f.sub_category_id.value = sub_category_id;
  f.section_id.value      = section_id;

  if (hasId) {
    f.id.value = id;
    f.orig_sub_category_id.value = '';
    f.orig_section_id.value = '';
  } else {
    f.id.value = '';
    // Original values from inputs' defaultValue (what came from DB)
    f.orig_sub_category_id.value = tr.querySelector('input[data-field="sub_category_id"]').defaultValue;
    f.orig_section_id.value      = tr.querySelector('input[data-field="section_id"]').defaultValue;
  }

  f.submit();
}
</script>
</body>
</html>
