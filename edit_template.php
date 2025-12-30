<?php
/**
 * ------------------------------------------------------------
 * FILE: edit_template.php
 * PURPOSE: Edit a checklist template (name/description + items)
 *
 * RULES:
 * - No automatic database writes
 * - Changes are saved ONLY when Save button is clicked
 * - Layout and styling are controlled by css/style.css
 * - Change the minimum possible to meet the request
 * ------------------------------------------------------------
 */

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . "/db.php";

$templateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($templateId <= 0) {
    header("Location: templates.php");
    exit();
}

$self = "edit_template.php?id=" . $templateId;
$errorMsg = "";
$action = $_POST['action'] ?? '';

/* ===============================
   UPDATE TEMPLATE
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_template') {
    $name = trim($_POST['template_name'] ?? '');
    $desc = trim($_POST['template_description'] ?? '');

    // Keep limits consistent (max 80)
    if (strlen($name) > 80) {
        $name = substr($name, 0, 80);
    }
    if (strlen($desc) > 80) {
        $desc = substr($desc, 0, 80);
    }

    if ($name === '') {
        $errorMsg = "Template name cannot be empty.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE templates SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $desc, $templateId]);
            header("Location: $self");
            exit();
        } catch (Throwable $e) {
            $errorMsg = "Save failed.";
        }
    }
}

/* ===============================
   ADD ITEM
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_item') {
    $text = trim($_POST['new_description'] ?? '');
    if ($text === '') {
        $errorMsg = "Checklist item cannot be empty.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO template_items (template_id, description) VALUES (?, ?)");
            $stmt->execute([$templateId, $text]);
            header("Location: $self");
            exit();
        } catch (Throwable $e) {
            $errorMsg = "Add item failed.";
        }
    }
}

/* ===============================
   DELETE ITEM
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_item') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    if ($itemId > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM template_items WHERE id = ? AND template_id = ?");
            $stmt->execute([$itemId, $templateId]);
            header("Location: $self");
            exit();
        } catch (Throwable $e) {
            $errorMsg = "Delete item failed.";
        }
    }
}

/* ===============================
   FETCH TEMPLATE
   =============================== */
$stmt = $pdo->prepare("SELECT id, name, description FROM templates WHERE id = ?");
$stmt->execute([$templateId]);
$template = $stmt->fetch();

if (!$template) {
    header("Location: templates.php");
    exit();
}

/* ===============================
   FETCH ITEMS
   =============================== */
$stmt = $pdo->prepare("
    SELECT id, description
    FROM template_items
    WHERE template_id = ?
    ORDER BY id ASC
");
$stmt->execute([$templateId]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Template</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h2>Edit Template</h2>

<nav>
    <a href="templates.php">Back</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="logout.php">Log Off</a>
</nav>

<div class="card">

<?php if ($errorMsg): ?>
    <div class="error"><?php echo htmlspecialchars($errorMsg); ?></div>
<?php endif; ?>

<!-- TEMPLATE DETAILS -->
<form id="updateTemplateForm" method="post" action="<?php echo htmlspecialchars($self); ?>" class="form-block">
    <input type="hidden" name="action" value="update_template">

    <!-- ONLY CHANGE: headings + fields in one row -->
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">

        <div>
            <label>Template Name</label>
            <input type="text"
                   name="template_name"
                   maxlength="80"
                   value="<?php echo htmlspecialchars($template['name']); ?>"
                   required>
        </div>

        <div>
            <label>Description (max 80)</label>
            <input type="text"
                   name="template_description"
                   maxlength="80"
                   value="<?php echo htmlspecialchars($template['description'] ?? ''); ?>">
        </div>

    </div>
</form>

<!-- ITEMS LIST -->
<div class="project-table" style="margin-top:12px;">
    <div class="project-row header">
        <div class="project-cell">Checklist Item</div>
        <div class="project-cell"></div>
    </div>

    <?php if (!$items): ?>
        <div class="project-row">
            <div class="project-cell" style="opacity:.85;">No items yet.</div>
        </div>
    <?php else: ?>
        <?php foreach ($items as $it): ?>
            <div class="project-row">
                <div class="project-cell"><?php echo htmlspecialchars($it['description']); ?></div>
                <div class="project-cell cell-actions">
                    <form method="post" action="<?php echo htmlspecialchars($self); ?>">
                        <input type="hidden" name="action" value="delete_item">
                        <input type="hidden" name="item_id" value="<?php echo (int)$it['id']; ?>">
                        <button type="submit" class="delete-x"
                                onclick="return confirm('Delete this item?');">✖</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ADD ITEM (STACKED) -->
<form method="post" action="<?php echo htmlspecialchars($self); ?>" class="form-block" style="margin-top:14px;">
    <input type="hidden" name="action" value="add_item">

    <label>Add Checklist Item</label>
    <input type="text" name="new_description" placeholder="Checklist item description..." required>

    <button type="submit" style="margin-top:8px;">Add Item</button>
</form>

<!-- SAVE BUTTON (LAST) -->
<button type="submit" form="updateTemplateForm" style="margin-top:12px;">
    Save Template
</button>

</div>

</body>
</html>
