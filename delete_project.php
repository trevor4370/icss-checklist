<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . "/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: projects.php");
    exit();
}

$projectId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($projectId <= 0) {
    header("Location: projects.php");
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM project_items WHERE project_id = ?");
    $stmt->execute([$projectId]);

    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);

    $pdo->commit();

    header("Location: projects.php?msg=deleted");
    exit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header("Location: projects.php");
    exit();
}
