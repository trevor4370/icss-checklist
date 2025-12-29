<?php
/**
 * ------------------------------------------------------------
 * FILE: edit_project.php
 * PURPOSE: Edit project checklist and save done status
 * ------------------------------------------------------------
 */

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . "/db.php";

$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($projectId <= 0) {
    header("Location: projects.php");
    exit();
}

$self = "edit_project.php?id=" . $projectId;
$errorMsg = "";
$action = $_POST['action'] ?? '';

/* -------------------------------
   Helper: detect column exists
   ------------------------------- */
function table_has_column(PDO $pdo, string $table, string $col): bool {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$col]);
        return (bool)$stmt->fetch();
    } catch (Throwable $e) {
        return false;
    }
}

$hasSourceTemplate = table_has_column($pdo, 'project_items', 'source_template_id');

// Fetch project details
$stmt = $pdo->prepare("
    SELECT p.*, t.name AS template_name
    FROM projects p
    LEFT JOIN templates t ON t.id = p.template_id
    WHERE p.id = ?
");
$stmt->execute([$projectId]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: projects.php");
    exit();
}

// Handle form submission (only at the bottom save)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_all') {

    // Re-fetch item IDs for safety
    $stmt = $pdo->prepare("SELECT id FROM project_items WHERE project_id = ? ORDER BY id ASC");
    $stmt->execute([$projectId]);
    $itemIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($itemIds as $itemId) {
        $itemId = (int)$itemId;
        $isDone = isset($_POST["done_$itemId"]) ? 1 : 0;

        $stmt = $pdo->prepare("
            UPDATE project_items
            SET is_done = :done
            WHERE id = :id AND project_id = :project_id
        ");
        $stmt->execute([
            ':done' => $isDone,
            ':id' => $itemId,
            ':project_id' => $projectId
        ]);
    }

    header("Location: $self");
    exit();
}

// Fetch project items (with template grouping if available)
if ($hasSourceTemplate) {
    $stmt = $pdo->prepare("
        SELECT
            pi.id,
            pi.description,
            pi.is_custom,
            pi.is_done,
            pi.source_template_id,
            t2.name AS source_template_name
        FROM project_items pi
        LEFT JOIN templates t2 ON t2.id = pi.source_template_id
        WHERE pi.project_id = ?
        ORDER BY
            COALESCE(t2.name,'') ASC,
            pi.id ASC
    ");
    $stmt->execute([$projectId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("
        SELECT id, description, is_custom, is_done
        FROM project_items
        WHERE project_id = ?
        ORDER BY id ASC
    ");
    $stmt->execute([$projectId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Project</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h2>Edit Project</h2>

<nav>
    <a href="projects.php">Projects</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="logout.php">Log Off</a>
</nav>

<?php if ($errorMsg): ?>
    <div class="error"><?php echo htmlspecialchars($errorMsg); ?></div>
<?php endif; ?>

<div class="card">

    <!-- Project summary on one line -->
    <div style="margin-bottom:12px;">
        <strong>Project:</strong> <?php echo htmlspecialchars($project['name']); ?>
        <strong style="margin-left:15px;">Template:</strong> <?php echo htmlspecialchars($project['template_name'] ?? ''); ?>
        <strong style="margin-left:15px;">Start:</strong> <?php echo htmlspecialchars($project['start_date'] ?? ''); ?>
        <strong style="margin-left:15px;">End:</strong> <?php echo htmlspecialchars($project['end_date'] ?? ''); ?>
    </div>

    <!-- IMPORTANT: checklist must be inside the same form as Save -->
    <form method="post" action="<?php echo htmlspecialchars($self); ?>">
        <input type="hidden" name="action" value="save_all">

        <div class="project-table">

            <!-- Main header -->
            <div class="project-row header">
                <div class="project-cell">Done</div>
                <div class="project-cell">To Do Item</div>
                <div class="project-cell"></div>
            </div>

            <?php if (!$items): ?>
                <div class="project-row">
                    <div class="project-cell" style="opacity:.85;">No checklist items found.</div>
                </div>
            <?php else: ?>

                <?php
                $lastTemplate = null;

                foreach ($items as $item):
                    $currentTemplate = $hasSourceTemplate
                        ? (string)($item['source_template_name'] ?? '')
                        : '';

                    // Template separation row
                    if ($hasSourceTemplate && $currentTemplate !== $lastTemplate) {
                        $lastTemplate = $currentTemplate;
                        ?>
                        <div class="project-row header" style="margin-top:14px;">
                            <div class="project-cell" style="font-weight:700;">
                                <?php echo htmlspecialchars($currentTemplate !== '' ? $currentTemplate : 'Other Items'); ?>
                            </div>
                            <div class="project-cell"></div>
                            <div class="project-cell"></div>
                        </div>

                        <div style="height:10px;"></div>
                        <?php
                    }
                ?>

                    <div class="project-row">
                        <div class="project-cell">
                            <input type="checkbox"
                                   name="done_<?php echo (int)$item['id']; ?>"
                                   value="1"
                                   <?php echo !empty($item['is_done']) ? 'checked' : ''; ?>>
                        </div>

                        <div class="project-cell">
                            <?php echo htmlspecialchars($item['description']); ?>
                        </div>

                        <div class="project-cell">
                            <?php if (!empty($item['is_custom'])): ?>
                                <button type="submit"
                                        name="delete_<?php echo (int)$item['id']; ?>"
                                        class="delete-x"
                                        onclick="return confirm('Delete this item?');">✖</button>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <button type="submit" style="margin-top:20px;">Save All Changes</button>
    </form>

    <?php if (!$hasSourceTemplate): ?>
        <div style="opacity:.85; margin-top:12px;">
            Note: To separate items by template name, the <strong>project_items</strong> table needs a column called
            <strong>source_template_id</strong>.
        </div>
    <?php endif; ?>

</div>

</body>
</html>
