<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . "/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: templates.php?msg=error");
    exit();
}

$templateId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($templateId <= 0) {
    header("Location: templates.php?msg=error");
    exit();
}

try {
    $pdo->beginTransaction();

    // 1) Unlink projects using this template
    $stmt = $pdo->prepare("UPDATE projects SET template_id = NULL WHERE template_id = ?");
    $stmt->execute([$templateId]);

    // 2) Delete template items
    $stmt = $pdo->prepare("DELETE FROM template_items WHERE template_id = ?");
    $stmt->execute([$templateId]);

    // 3) Delete template
    $stmt = $pdo->prepare("DELETE FROM templates WHERE id = ?");
    $stmt->execute([$templateId]);

    $pdo->commit();

    header("Location: templates.php?msg=deleted");
    exit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: templates.php?msg=error");
    exit();
}
