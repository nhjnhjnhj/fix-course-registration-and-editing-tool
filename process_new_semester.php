<?php
session_start();
require_once __DIR__ . '/new_semester_functions.php';

// 管理者・POST・CSRFトークンを確認し、不正な実行要求を拒否する。
if (!nspIsAdministrator()
    || $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !nspValidateCsrfToken(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    header('Location: index.php');
    exit();
}

$academicYear = isset($_POST['academic_year']) ? (int) $_POST['academic_year'] : 0;
$schedulePolicy = isset($_POST['schedule_policy']) ? $_POST['schedule_policy'] : 'reset';
$resetSchedule = $schedulePolicy !== 'keep';
// 選択された年度と時間割方針で、バックアップと学年移行を実行する。
$result = nspRunMigration(__DIR__, $academicYear, $resetSchedule);

// 同じ確認画面を再送信できないよう、処理後にトークンを更新する。
unset($_SESSION['new_semester_csrf']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新学期開始処理の結果</title>
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-process-page">
<main class="admin-process-card">
    <h1>新学期開始処理の結果</h1>
    <p class="<?php echo $result['success'] ? 'admin-success' : 'admin-error'; ?>">
        <?php echo htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8'); ?>
    </p>

    <?php if ($result['success']): ?>
        <ul class="result-counts">
            <li>B3: <?php echo $result['counts']['B3']; ?>名</li>
            <li>B4: <?php echo $result['counts']['B4']; ?>名</li>
            <li>M1: <?php echo $result['counts']['M1']; ?>名</li>
            <li>M2: <?php echo $result['counts']['M2']; ?>名</li>
            <li>移行前のM2: <?php echo $result['old_m2_count']; ?>名（バックアップ済み）</li>
        </ul>
    <?php endif; ?>

    <?php if (!empty($result['backup_directory'])): ?>
        <p>バックアップ: <code><?php echo htmlspecialchars($result['backup_directory'], ENT_QUOTES, 'UTF-8'); ?></code></p>
    <?php endif; ?>

    <a class="button" href="index.php">トップページへ戻る</a>
</main>
</body>
</html>
