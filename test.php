<?php
/*******************************
 * Admin – sub_category_sections (CRUD) + read‑only views
 * Drop this file in your admin area and open it in the browser.
 * Adjust the DB credentials and table names below if needed.
 *******************************/

// ————— DB CONFIG —————
$DB = [
    'host' => '127.0.0.1',
    'name' => 'u664913565_testnielit',   // TODO: change to your DB name
    'user' => 'u664913565_testnielit',        // TODO: change if not root
    'pass' => 'Nielitbbsr@2025',            // TODO: set your password
    'charset' => 'utf8mb4',
];

// ———— TABLE NAMES (change if different) ————
const TBL_SCS = 'sub_category_sections';
const TBL_SECTIONS = 'sections';
const TBL_SUBCATS = 'sub_categories';

// ———— ASSUMED EDITABLE COLUMNS for sub_category_sections ————
// Adjust the list if your schema differs. "id" is assumed PK auto‑increment and not editable.
$SCS_EDITABLE_COLS = ['sub_category_id', 'section_id', 'position']; // add/remove columns to fit your table

// ————— INIT —————
session_start();
function pdo(array $DB): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = "mysql:host={$DB['host']};dbname={$DB['name']};charset={$DB['charset']}";
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    return $pdo = new PDO($dsn, $DB['user'], $DB['pass'], $opts);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function csrf_check($token): bool { return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$token); }

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ————— HELPERS —————
function table_exists(PDO $db, string $table): bool {
    $stmt = $db->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}
function get_columns(PDO $db, string $table): array {
    $cols = [];
    $rs = $db->query("DESCRIBE `{$table}`");
    foreach ($rs as $row) { $cols[] = $row['Field']; }
    return $cols;
}

$db = pdo($DB);
$errors = [];
$messages = [];

// ————— ACTIONS (CRUD for sub_category_sections) —————
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token. Please reload the page.';
    } else try {
        if ($action === 'create') {
            // Build insert only from allowed columns
            global $SCS_EDITABLE_COLS;
            $fields = [];$place = [];$values = [];
            foreach ($SCS_EDITABLE_COLS as $c) {
                if (array_key_exists($c, $_POST)) { $fields[] = "`$c`"; $place[] = ':' . $c; $values[":".$c] = $_POST[$c] === '' ? null : $_POST[$c]; }
            }
            if ($fields) {
                $sql = 'INSERT INTO `'.TBL_SCS.'` ('.implode(',', $fields).') VALUES ('.implode(',', $place).')';
                $stmt = $db->prepare($sql);
                $stmt->execute($values);
                $messages[] = 'Row created successfully.';
            } else {
                $errors[] = 'No fields to insert.';
            }
        }
        elseif ($action === 'update') {
            global $SCS_EDITABLE_COLS;
            $id = $_POST['id'] ?? null;
            if (!$id) throw new Exception('Missing ID for update.');
            $sets = [];$values = [':id' => $id];
            foreach ($SCS_EDITABLE_COLS as $c) {
                if (array_key_exists($c, $_POST)) { $sets[] = "`$c` = :$c"; $values[":".$c] = $_POST[$c] === '' ? null : $_POST[$c]; }
            }
            if ($sets) {
                $sql = 'UPDATE `'.TBL_SCS.'` SET '.implode(',', $sets).' WHERE `id` = :id';
                $stmt = $db->prepare($sql);
                $stmt->execute($values);
                $messages[] = 'Row updated successfully.';
            } else {
                $errors[] = 'No changes to update.';
            }
        }
        elseif ($action === 'delete') {
            $id = $_POST['id'] ?? null;
            if (!$id) throw new Exception('Missing ID for delete.');
            $stmt = $db->prepare('DELETE FROM `'.TBL_SCS.'` WHERE `id` = :id');
            $stmt->execute([':id' => $id]);
            $messages[] = 'Row deleted successfully.';
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

// ————— FETCH DATA —————
$scs_cols = table_exists($db, TBL_SCS) ? get_columns($db, TBL_SCS) : [];
$sections_cols = table_exists($db, TBL_SECTIONS) ? get_columns($db, TBL_SECTIONS) : [];
$subcats_cols = table_exists($db, TBL_SUBCATS) ? get_columns($db, TBL_SUBCATS) : [];

$scs = $scs_cols ? $db->query('SELECT * FROM `'.TBL_SCS.'` ORDER BY id DESC')->fetchAll() : [];
$sections = $sections_cols ? $db->query('SELECT * FROM `'.TBL_SECTIONS.'` ORDER BY id DESC LIMIT 500')->fetchAll() : [];
$subcats = $subcats_cols ? $db->query('SELECT * FROM `'.TBL_SUBCATS.'` ORDER BY id DESC LIMIT 500')->fetchAll() : [];

// Try to preload reference options for nicer selects
function kv_options(PDO $db, string $table, string $labelCol = 'name'): array {
    try {
        $cols = get_columns($db, $table);
        $label = in_array($labelCol, $cols, true) ? $labelCol : (in_array('title',$cols,true) ? 'title' : (in_array('label',$cols,true) ? 'label' : $cols[0] ?? 'id'));
        $sql = "SELECT id, `$label` AS label FROM `$table` ORDER BY `$label` ASC LIMIT 1000";
        $rows = $db->query($sql)->fetchAll();
        $out = [];
        foreach ($rows as $r) { $out[$r['id']] = $r['label']; }
        return $out;
    } catch (Throwable $e) { return []; }
}
$sectionOptions = $sections_cols ? kv_options($db, TBL_SECTIONS) : [];
$subcatOptions = $subcats_cols ? kv_options($db, TBL_SUBCATS) : [];

$csrf = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage sub_category_sections</title>
  <style>
    body {font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; margin: 24px; background:#f7f7fb;}
    h1,h2 {margin: 0 0 12px}
    .wrap {max-width: 1200px; margin: 0 auto}
    .card {background:#fff;border:1px solid #e6e6ef;border-radius:14px; padding:16px; margin-bottom:18px; box-shadow:0 1px 2px rgba(0,0,0,0.04)}
    table {width:100%; border-collapse: collapse}
    th, td {border-bottom:1px solid #eee; padding:8px 10px; text-align:left; vertical-align: top}
    th {background:#fafafc; font-weight:600}
    tr:hover td {background:#fcfcff}
    .row-actions {display:flex; gap:8px}
    input[type=text], input[type=number], select {padding:6px 8px; border:1px solid #d9d9e8; border-radius:8px; width: 100%; box-sizing: border-box}
    .grid {display:grid; grid-template-columns: repeat(3, minmax(160px, 1fr)); gap:8px}
    .btn {display:inline-block; padding:8px 12px; border:1px solid #d0d0e6; background:#f3f4ff; border-radius:10px; cursor:pointer}
    .btn.primary {background:#3b82f6; color:#fff; border-color:#3b82f6}
    .btn.danger {background:#ef4444; color:#fff; border-color:#ef4444}
    .notice {padding:10px 12px; border-radius:10px; margin-bottom:10px}
    .notice.ok {background:#ecfdf5; border:1px solid #a7f3d0}
    .notice.err {background:#fef2f2; border:1px solid #fecaca}
    .muted {color:#666}
    .pill {display:inline-block; padding:2px 8px; border-radius:999px; background:#eef2ff; border:1px solid #e0e7ff; font-size:12px}
    .tight {margin-top:6px}
    .help {font-size:12px; color:#6b7280}
  </style>
</head>
<body>
<div class="wrap">
  <h1>Manage <span class="pill"><?php echo h(TBL_SCS); ?></span></h1>

  <?php foreach ($messages as $m): ?>
    <div class="notice ok">✅ <?php echo h($m); ?></div>
  <?php endforeach; ?>
  <?php foreach ($errors as $e): ?>
    <div class="notice err">⚠️ <?php echo h($e); ?></div>
  <?php endforeach; ?>

  <div class="card">
    <h2>Create new row</h2>
    <?php if (!$scs_cols): ?>
      <p class="muted">Table <code><?php echo h(TBL_SCS); ?></code> not found. Check the table name or create it.</p>
    <?php else: ?>
      <form method="post" class="grid">
        <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
        <input type="hidden" name="action" value="create">
        <?php foreach ($SCS_EDITABLE_COLS as $col): ?>
          <label>
            <div class="help"><?php echo h($col); ?></div>
            <?php if ($col === 'section_id' && $sectionOptions): ?>
              <select name="section_id">
                <option value="">— select section —</option>
                <?php foreach ($sectionOptions as $id => $label): ?>
                  <option value="<?php echo h($id); ?>"><?php echo h("$id — $label"); ?></option>
                <?php endforeach; ?>
              </select>
            <?php elseif ($col === 'sub_category_id' && $subcatOptions): ?>
              <select name="sub_category_id">
                <option value="">— select sub‑category —</option>
                <?php foreach ($subcatOptions as $id => $label): ?>
                  <option value="<?php echo h($id); ?>"><?php echo h("$id — $label"); ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="text" name="<?php echo h($col); ?>" placeholder="Enter <?php echo h($col); ?>">
            <?php endif; ?>
          </label>
        <?php endforeach; ?>
        <div style="align-self:end">
          <button class="btn primary" type="submit">➕ Add row</button>
        </div>
      </form>
      <p class="help tight">Editable columns: <?php echo h(implode(', ', $SCS_EDITABLE_COLS)); ?>. Change in the PHP file if your schema differs.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Existing rows</h2>
    <?php if (!$scs): ?>
      <p class="muted">No rows to show.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <?php foreach ($scs_cols as $c): ?><th><?php echo h($c); ?></th><?php endforeach; ?>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($scs as $row): ?>
          <tr>
            <form method="post">
              <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
              <input type="hidden" name="action" value="update">
              <?php foreach ($scs_cols as $c): ?>
                <?php if ($c === 'id'): ?>
                  <td><input type="hidden" name="id" value="<?php echo h($row['id']); ?>"><?php echo h($row['id']); ?></td>
                <?php elseif (in_array($c, $SCS_EDITABLE_COLS, true)): ?>
                  <td>
                    <?php if ($c === 'section_id' && $sectionOptions): ?>
                      <select name="section_id">
                        <option value="">— none —</option>
                        <?php foreach ($sectionOptions as $id => $label): ?>
                          <option value="<?php echo h($id); ?>" <?php if ((string)$row[$c]===(string)$id) echo 'selected'; ?>><?php echo h("$id — $label"); ?></option>
                        <?php endforeach; ?>
                      </select>
                    <?php elseif ($c === 'sub_category_id' && $subcatOptions): ?>
                      <select name="sub_category_id">
                        <option value="">— none —</option>
                        <?php foreach ($subcatOptions as $id => $label): ?>
                          <option value="<?php echo h($id); ?>" <?php if ((string)$row[$c]===(string)$id) echo 'selected'; ?>><?php echo h("$id — $label"); ?></option>
                        <?php endforeach; ?>
                      </select>
                    <?php else: ?>
                      <input type="text" name="<?php echo h($c); ?>" value="<?php echo h($row[$c]); ?>">
                    <?php endif; ?>
                  </td>
                <?php else: ?>
                  <td class="muted"><?php echo h($row[$c]); ?></td>
                <?php endif; ?>
              <?php endforeach; ?>
              <td class="row-actions">
                <button class="btn primary" type="submit">💾 Save</button>
            </form>
                <form method="post" onsubmit="return confirm('Delete this row?');">
                  <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo h($row['id']); ?>">
                  <button class="btn danger" type="submit">🗑️ Delete</button>
                </form>
              </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Read‑only: <?php echo h(TBL_SECTIONS); ?></h2>
    <?php if (!$sections_cols): ?>
      <p class="muted">Table <code><?php echo h(TBL_SECTIONS); ?></code> not found.</p>
    <?php else: ?>
      <table>
        <thead><tr><?php foreach ($sections_cols as $c): ?><th><?php echo h($c); ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($sections as $r): ?>
            <tr>
              <?php foreach ($sections_cols as $c): ?><td><?php echo h($r[$c]); ?></td><?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="help tight">Showing latest 500 rows.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Read‑only: <?php echo h(TBL_SUBCATS); ?></h2>
    <?php if (!$subcats_cols): ?>
      <p class="muted">Table <code><?php echo h(TBL_SUBCATS); ?></code> not found.</p>
    <?php else: ?>
      <table>
        <thead><tr><?php foreach ($subcats_cols as $c): ?><th><?php echo h($c); ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($subcats as $r): ?>
            <tr>
              <?php foreach ($subcats_cols as $c): ?><td><?php echo h($r[$c]); ?></td><?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="help tight">Showing latest 500 rows.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Notes</h2>
    <ul>
      <li>This page uses prepared statements and a CSRF token for basic security. Keep it in your admin area.</li>
      <li>If your <code>sub_category_sections</code> table has different columns, edit <code>$SCS_EDITABLE_COLS</code> near the top.</li>
      <li>The dropdowns try to show labels from <code>sections</code> and <code>sub_categories</code> (preferring <code>name</code>/<code>title</code>/<code>label</code> columns).</li>
    </ul>
  </div>
</div>
</body>
</html>
