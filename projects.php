<?php
/**
 * ------------------------------------------------------------
 * FILE: projects.php
 * VERSION: v1.03
 * CHANGE SUMMARY:
 * - New Project section: Notes field changed to TEXTAREA (10 lines)
 * - NOTHING else changed
 * ------------------------------------------------------------
 */

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . "/db.php";

$action = $_POST['action'] ?? '';
$msg = $_GET['msg'] ?? '';

/* -------------------------------
   CREATE PROJECT (DB)
   ------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create_project') {
    $name = trim($_POST['project_name'] ?? '');
    $start = trim($_POST['start_date'] ?? '');
    $end = trim($_POST['end_date'] ?? '');
    $templateIds = $_POST['template_ids'] ?? [];
    $notes = trim($_POST['notes'] ?? '');

    if ($name === '') {
        header("Location: projects.php?open=1&msg=name_required");
        exit();
    }
    if (!is_array($templateIds) || count($templateIds) === 0) {
        header("Location: projects.php?open=1&msg=template_required");
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO projects (name, start_date, end_date, notes)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $name,
            ($start !== '' ? $start : null),
            ($end !== '' ? $end : null),
            ($notes !== '' ? $notes : null)
        ]);

        $projectId = (int)$pdo->lastInsertId();

        $tplStmt = $pdo->prepare("
            SELECT description
            FROM template_items
            WHERE template_id = ?
            ORDER BY id ASC
        ");

        $insertItem = $pdo->prepare("
            INSERT INTO project_items (project_id, description, is_done)
            VALUES (?, ?, 0)
        ");

        $added = [];

        foreach ($templateIds as $tplId) {
            $tplId = (int)$tplId;
            if ($tplId <= 0) continue;

            $tplStmt->execute([$tplId]);
            $items = $tplStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $it) {
                $desc = trim($it['description']);
                if ($desc === '') continue;
                if (isset($added[$desc])) continue;

                $insertItem->execute([$projectId, $desc]);
                $added[$desc] = true;
            }
        }

        header("Location: edit_project.php?id=" . $projectId);
        exit();

    } catch (Throwable $e) {
        header("Location: projects.php?open=1&msg=error");
        exit();
    }
}

/* ---------------------------------
   FETCH TEMPLATES
   --------------------------------- */
$stmt = $pdo->query("SELECT id, name FROM templates ORDER BY name ASC");
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------------------------------
   FETCH PROJECTS
   --------------------------------- */
$stmt = $pdo->query("
    SELECT 
        p.id,
        p.name,
        p.start_date,
        p.end_date,
        COUNT(pi.id) AS total_items,
        SUM(CASE WHEN pi.is_done = 1 THEN 1 ELSE 0 END) AS done_items
    FROM projects p
    LEFT JOIN project_items pi ON pi.project_id = p.id
    GROUP BY p.id
    ORDER BY p.id DESC
");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

function ymd_to_dmy($ymd) {
    if (!$ymd) return '';
    $ts = strtotime($ymd);
    return $ts ? date('d/m/Y', $ts) : '';
}

$openAccordion = isset($_GET['open']) && $_GET['open'] === '1';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Projects</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h2>Your Projects</h2>

<nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="templates.php">Templates</a>
    <a href="logout.php">Log Off</a>
</nav>

<!-- PROJECT LIST -->
<div class="project-table projects-table">

    <div class="project-row header">
        <div class="project-cell">Project</div>
        <div class="project-cell">Start</div>
        <div class="project-cell">End</div>
        <div class="project-cell">Remaining</div>
        <div class="project-cell">Progress</div>
        <div class="project-cell">Done</div>
        <div class="project-cell"></div>
        <div class="project-cell"></div>
    </div>

    <?php foreach ($projects as $p): ?>
        <?php
            $daysLeft = '';
            if ($p['end_date']) {
                $today = new DateTime('today');
                $endD  = new DateTime($p['end_date']);
                $daysLeft = (int)$today->diff($endD)->format('%r%a');
            }

            $done  = (int)$p['done_items'];
            $total = (int)$p['total_items'];
            $pct   = ($total > 0) ? (int)round(($done / $total) * 100) : 0;
        ?>
        <div class="project-row">
            <div class="project-cell"><?= htmlspecialchars($p['name']) ?></div>
            <div class="project-cell"><?= ymd_to_dmy($p['start_date']) ?></div>
            <div class="project-cell"><?= ymd_to_dmy($p['end_date']) ?></div>
            <div class="project-cell"><?= $daysLeft ?></div>
            <div class="project-cell">
                <div class="progress-bar">
                    <div class="progress" style="width: <?= $pct ?>%;"></div>
                </div>
            </div>
            <div class="project-cell"><?= $done ?> / <?= $total ?></div>
            <div class="project-cell cell-actions">
                <a class="edit-link" href="edit_project.php?id=<?= (int)$p['id'] ?>">Edit</a>
            </div>
            <div class="project-cell cell-actions">
                <form method="post" action="delete_project.php">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="delete-x" onclick="return confirm('Delete project?')">✖</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<!-- NEW PROJECT (accordion like templates) -->
<div class="accordion" style="margin-top:20px;">

    <button class="accordion-toggle" type="button" onclick="toggleAccordion()">
        + New Project
    </button>

    <div id="accordion-content" class="accordion-content<?= $openAccordion ? ' open' : ''; ?>">

        <form method="post" class="form-block">
            <input type="hidden" name="action" value="create_project">

            <!-- SAME LINE: NAME + START + END -->
            <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:12px;">
                <div>
                    <label>Project Name</label>
                    <input type="text" name="project_name" required>
                </div>
                <div>
                    <label>Start Date</label>
                    <input type="date" name="start_date">
                </div>
                <div>
                    <label>End Date</label>
                    <input type="date" name="end_date">
                </div>
            </div>

            <label style="margin-top:10px;">Checklist Templates (select one or more)</label>

            <div style="background:#262626; border:1px solid #333; border-radius:4px; padding:10px; max-height:180px; overflow:auto;">
                <?php foreach ($templates as $t): ?>
                    <label style="display:flex; align-items:center; gap:10px; margin:6px 0;">
                        <input type="checkbox" name="template_ids[]" value="<?= (int)$t['id'] ?>" required>
                        <span><?= htmlspecialchars($t['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- NOTES: 10 LINES -->
            <label style="margin-top:10px;">Notes (optional)</label>
            <textarea name="notes" rows="10"
                style="width:100%; padding:10px; border-radius:4px; border:1px solid #333; background-color:#262626; color:#fff; box-sizing:border-box;"></textarea>

            <button type="submit" style="margin-top:12px;">Create Project</button>
        </form>

    </div>
</div>

<script>
function toggleAccordion() {
    document.getElementById('accordion-content').classList.toggle('open');
}
</script>

</body>
</html>
