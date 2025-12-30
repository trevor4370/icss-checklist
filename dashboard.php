<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

/* ---------- DB SETTINGS ---------- */
$dbHost = "127.0.0.1";
$dbName = "YOUR_DB_NAME";
$dbUser = "YOUR_DB_USER";
$dbPass = "YOUR_DB_PASS";

/* ---------- SORTING (SAFE) ---------- */
$sort = $_GET['sort'] ?? 'start';
$dir  = $_GET['dir']  ?? 'asc';

$allowedSort = [
    'start' => 'p.start_date',
    'end'   => 'p.end_date',
    'days'  => 'DATEDIFF(p.end_date, CURDATE())'
];

if (!isset($allowedSort[$sort])) {
    $sort = 'start';
}
$dir = ($dir === 'desc') ? 'DESC' : 'ASC';

$orderBy = $allowedSort[$sort] . ' ' . $dir;

/* ---------- SORT LINK HELPER ---------- */
function sortLink(string $label, string $key, string $currentSort, string $currentDir): string {
    $nextDir = ($currentSort === $key && $currentDir === 'ASC') ? 'desc' : 'asc';
    return '<a href="dashboard.php?sort='.$key.'&dir='.$nextDir.'" class="sort-link">'.$label.'</a>';
}

/* ---------- FETCH PROJECTS ---------- */
$projects = [];

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sql = "
        SELECT
            p.id,
            p.name,
            p.start_date,
            p.end_date,
            DATEDIFF(p.end_date, CURDATE()) AS days_left
        FROM projects p
        ORDER BY $orderBy
    ";

    $projects = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $projects = [];
}

/* ---------- MOCK PROGRESS ---------- */
function mockProgress(int $id): array {
    $total = 10;
    $checked = $id % 10;
    $percent = ($checked / $total) * 100;
    return [$checked, $total, (int)$percent];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h2>Welcome to your Dashboard, <?php echo htmlspecialchars($_SESSION['user']); ?></h2>

<nav>
    <a href="templates.php">Templates</a> |
    <a href="projects.php">Projects</a> |
    <a href="logout.php">Log Off</a>
</nav>

<div class="project-table">

    <!-- HEADER -->
    <div class="project-row header">
        <div class="project-cell">Project</div>
        <div class="project-cell"><?php echo sortLink('Start','start',$sort,$dir); ?></div>
        <div class="project-cell"><?php echo sortLink('End','end',$sort,$dir); ?></div>
        <div class="project-cell"><?php echo sortLink('Days Left','days',$sort,$dir); ?></div>
        <div class="project-cell">Progress</div>
        <div class="project-cell"></div>
        <div class="project-cell"></div>
        <div class="project-cell"></div>
    </div>

    <!-- ROWS -->
    <?php foreach ($projects as $p): ?>
        <?php
            [$checked,$total,$percent] = mockProgress((int)$p['id']);
        ?>
        <div class="project-row">
            <div class="project-cell"><?php echo htmlspecialchars($p['name']); ?></div>
            <div class="project-cell"><?php echo $p['start_date']; ?></div>
            <div class="project-cell"><?php echo $p['end_date']; ?></div>
            <div class="project-cell"><?php echo $p['days_left']; ?></div>

            <div class="project-cell">
                <div class="progress-bar">
                    <div class="progress" style="width:<?php echo $percent; ?>%"></div>
                </div>
            </div>

            <div class="project-cell"><?php echo $checked.'/'.$total; ?></div>

            <div class="project-cell">
                <a class="btn-link" href="edit_checklist.php?id=<?php echo $p['id']; ?>">Edit</a>
            </div>

            <div class="project-cell">
                <a class="btn-link danger" href="delete_project.php?id=<?php echo $p['id']; ?>">Delete</a>
            </div>
        </div>
    <?php endforeach; ?>

</div>

</body>
</html>
