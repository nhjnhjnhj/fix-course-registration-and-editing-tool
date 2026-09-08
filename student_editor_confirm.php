<?php
/* 時間割の変更内容を確認し、確定時にJSONへ保存する画面 */
session_start();
require_once __DIR__ . '/student_editor_functions.php';

// 管理者以外からの直接アクセスを防ぐ。
if (!nspIsAdministrator()) {
    header('Location: index.php');
    exit();
}

$csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

// 編集画面または確認画面からの正しいPOST送信か確認する。
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !steValidateCsrfToken($csrfToken)) {
    header('Location: student_editor.php');
    exit();
}

$grade = isset($_POST['grade']) ? $_POST['grade'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$loadResult = steLoadStudent(__DIR__, $grade, $email);
$isFinal = isset($_POST['final']) && $_POST['final'] === '1';
$scheduleInput = $isFinal
    ? json_decode(isset($_POST['schedule_json']) ? $_POST['schedule_json'] : '', true)
    : (isset($_POST['class']) ? $_POST['class'] : array());
$schedule = steNormalizeSchedule(is_array($scheduleInput) ? $scheduleInput : array());
$saveResult = null;

// 保存ボタンが押された場合だけ、対象学生の時間割を更新する。
if ($loadResult['success'] && $isFinal) {
    $saveResult = steUpdateStudentSchedule(__DIR__, $grade, $email, $schedule);
    if ($saveResult['success']) {
        unset($_SESSION['student_editor_csrf']);
    }
}

$student = $loadResult['success'] ? $loadResult['student'] : null;

// 保存前の時間割と比較し、確認画面で変更セルを判定する。
$originalSchedule = $student !== null
    ? steNormalizeSchedule(isset($student['class']) ? $student['class'] : array())
    : steNormalizeSchedule(array());
$quarterNames = array('Quarter1' => '1Q', 'Quarter2' => '2Q', 'Quarter3' => '3Q', 'Quarter4' => '4Q');
$dayNames = array('Mon' => '月', 'Tue' => '火', 'Wed' => '水', 'Thu' => '木', 'Fri' => '金', 'Sat' => '土');
$scheduleJson = json_encode($schedule, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>学生時間割の確認</title>
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/editor.css">
</head>
<body class="student-editor-page">
<main class="student-editor-shell editor-wide-shell">
    <header class="student-editor-header">
        <p class="editor-eyebrow">管理者専用</p>
        <h1><?php echo $isFinal ? '学生時間割の保存結果' : '学生時間割の確認'; ?></h1>
    </header>

    <?php if (!$loadResult['success']): ?>
        <p class="editor-error"><?php echo steEscape($loadResult['message']); ?></p>
        <a class="button" href="student_editor.php">学生一覧へ戻る</a>
    <?php elseif ($isFinal): ?>
        <p class="<?php echo $saveResult['success'] ? 'editor-success' : 'editor-error'; ?>">
            <?php echo $saveResult['success'] ? '時間割を保存しました。' : steEscape($saveResult['message']); ?>
        </p>
        <a class="button" href="student_editor.php">学生一覧へ戻る</a>
        <a class="button secondary-button" href="index.php">トップページへ戻る</a>
    <?php else: ?>
        <dl class="student-summary">
            <div><dt>学年</dt><dd><?php echo steEscape($grade); ?></dd></div>
            <div><dt>氏名</dt><dd><?php echo steEscape($student['name']); ?></dd></div>
            <div><dt>メールアドレス</dt><dd><?php echo steEscape($student['email']); ?></dd></div>
        </dl>

        <?php foreach ($quarterNames as $quarterKey => $quarterLabel): ?>
            <section class="editor-quarter-section">
                <h2><?php echo steEscape($quarterLabel); ?></h2>
                <div class="editor-table-wrap">
                    <table class="editor-schedule-table confirm-schedule-table">
                        <thead>
                            <tr><th>時限</th><?php foreach ($dayNames as $dayLabel): ?><th><?php echo steEscape($dayLabel); ?></th><?php endforeach; ?></tr>
                        </thead>
                        <tbody>
                            <?php for ($period = 1; $period <= 5; $period++): ?>
                                <tr>
                                    <th><?php echo $period; ?></th>
                                    <?php foreach ($dayNames as $dayKey => $dayLabel): ?>
                                        <?php
                                        // 入力内容が保存済みの値と異なるセルだけを強調する。
                                        $periodKey = (string) $period;
                                        $isChanged = $schedule[$quarterKey][$dayKey][$periodKey]
                                            !== $originalSchedule[$quarterKey][$dayKey][$periodKey];
                                        ?>
                                        <td<?php echo $isChanged ? ' class="changed-schedule-cell"' : ''; ?>>
                                            <?php if ($isChanged): ?><span class="change-label">変更</span><?php endif; ?>
                                            <?php echo steEscape($schedule[$quarterKey][$dayKey][$periodKey]); ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="editor-actions editor-confirm-actions">
            <!-- 確認した内容を確定し、JSONへ保存する -->
            <form method="post" action="student_editor_confirm.php">
                <input type="hidden" name="csrf_token" value="<?php echo steEscape($csrfToken); ?>">
                <input type="hidden" name="grade" value="<?php echo steEscape($grade); ?>">
                <input type="hidden" name="email" value="<?php echo steEscape($email); ?>">
                <input type="hidden" name="schedule_json" value="<?php echo steEscape($scheduleJson); ?>">
                <input type="hidden" name="final" value="1">
                <button type="submit">保存</button>
            </form>
            <!-- 入力内容を保持したまま編集画面へ戻る -->
            <form method="post" action="student_editor_edit.php">
                <input type="hidden" name="csrf_token" value="<?php echo steEscape($csrfToken); ?>">
                <input type="hidden" name="grade" value="<?php echo steEscape($grade); ?>">
                <input type="hidden" name="email" value="<?php echo steEscape($email); ?>">
                <input type="hidden" name="schedule_json" value="<?php echo steEscape($scheduleJson); ?>">
                <button class="secondary-button" type="submit">修正へ戻る</button>
            </form>
            <a class="button secondary-button" href="student_editor.php">学生一覧へ戻る</a>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
