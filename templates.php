<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . "/db.php";

$action = $_POST['action'] ?? '';
$msg = $_GET['msg'] ?? '';

/* -------------------------------
   CREATE TEMPLATE (DB)
   Creates template only, then redirects to edit_template.php
   ------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create_template') {
    $name = trim($_POST['template_name'] ?? '');
    $desc = trim($_POST['template_description'] ?? '');

    if (strlen($desc) > 80) {
        $desc = substr($desc, 0, 80);
    }

    if ($name === '') {
        header("Location: templates.php?open=1&msg=name_required");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO templates (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $desc]);

        $newId = (int)$pdo->lastInsertId();
        header("Location: edit_template.php?id=" . $newId);
        exit();

    } catch (Throwable $e) {
        header("Location: templates.php?open=1&msg=error");
        exit();
    }
}

/* ---------------------------------
   FETCH TEMPLATES
   --------------------------------- */
$stmt = $pdo->query("SELECT id, name, description FROM templates ORDER BY id DESC");
$templates = $stmt->fetchAll();

$openAccordion = isset($_GET['open']) && $_GET['open'] === '1';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Templates</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h2>Your Templates</h2>

<nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="projects.php">Projects</a>
    <a href="logout.php">Log Off</a>
</nav>

<!-- =========================
     TEMPLATE LIST
     ========================= -->
<div class="project-table templates-table">

    <div class="project-row header">
        <div class="project-cell">Checklist Name</div>
        <div class="project-cell">Description</div>
        <div class="project-cell"></div>
        <div class="project-cell"></div>
    </div>

    <?php if (!$templates): ?>
        <div class="project-row">
            <div class="project-cell" style="opacity:.85;">No templates found.</div>
        </div>
    <?php else: ?>
        <?php foreach ($templates as $t): ?>
            <?php
                $desc = (string)($t['description'] ?? '');
                if (strlen($desc) > 80) $desc = substr($desc, 0, 80);
            ?>
            <div class="project-row">
                <div class="project-cell"><?php echo htmlspecialchars($t['name']); ?></div>
                <div class="project-cell"><?php echo htmlspecialchars($desc); ?></div>

                <div class="project-cell cell-actions">
                    <a class="edit-link" href="edit_template.php?id=<?php echo (int)$t['id']; ?>">Edit</a>
                </div>

                <div class="project-cell cell-actions">
                    <form method="post" action="delete_template.php">
                        <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                        <button type="submit" class="delete-x"
                                onclick="return confirm('Delete this template? Projects using it will be unlinked.');">✖</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<!-- =========================
     NEW TEMPLATE (UNDER LIST)
     ========================= -->
<div class="accordion" style="margin-top:20px;">

    <button class="accordion-toggle" type="button" onclick="toggleAccordion()">
        + New Template
    </button>

    <div id="accordion-content" class="accordion-content<?php echo $openAccordion ? ' open' : ''; ?>">

        <?php if ($msg === 'name_required'): ?>
            <div class="error">Template name is required.</div>
        <?php elseif ($msg === 'error'): ?>
            <div class="error">Could not create template. Please try again.</div>
        <?php endif; ?>

        <form method="post" class="form-block">
            <input type="hidden" name="action" value="create_template">

            <label>Template Name</label>
            <input type="text" name="template_name" required>

            <label>Description (max 80)</label>
            <input type="text" name="template_description" maxlength="80">

            <button type="submit" style="margin-top:12px;">Create Template</button>
        </form>

        <div style="opacity:.85; margin-top:10px;">
            After creating, you’ll be taken to Edit Template to add checklist items.
        </div>

    </div>
</div>

<script>
function toggleAccordion() {
    document.getElementById('accordion-content').classList.toggle('open');
}
</script>

</body>
</html>
