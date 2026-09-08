<?php
/* 選択された学生の1Q～4Qの時間割を編集する画面 */
session_start();
require_once __DIR__ . '/student_editor_functions.php';

// 管理者以外からの直接アクセスを防ぐ。
if (!nspIsAdministrator()) {
    header('Location: index.php');
    exit();
}

$csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

// 学生一覧画面または確認画面からの正しいPOST送信か確認する。
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !steValidateCsrfToken($csrfToken)) {
    header('Location: student_editor.php');
    exit();
}

$grade = isset($_POST['grade']) ? $_POST['grade'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$loadResult = steLoadStudent(__DIR__, $grade, $email);
$student = $loadResult['success'] ? $loadResult['student'] : null;
$schedule = $student !== null ? steNormalizeSchedule($student['class']) : steNormalizeSchedule(array());

// 確認画面から戻った場合は、未保存の入力内容を復元する。
if (isset($_POST['schedule_json'])) {
    $postedSchedule = json_decode($_POST['schedule_json'], true);
    if (is_array($postedSchedule)) {
        $schedule = steNormalizeSchedule($postedSchedule);
    }
}

$quarterNames = array('Quarter1' => '1Q', 'Quarter2' => '2Q', 'Quarter3' => '3Q', 'Quarter4' => '4Q');
$dayNames = array('Mon' => '月', 'Tue' => '火', 'Wed' => '水', 'Thu' => '木', 'Fri' => '金', 'Sat' => '土');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>学生時間割の編集</title>
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/editor.css">
</head>
<body class="student-editor-page">
<main class="student-editor-shell editor-wide-shell">
    <header class="student-editor-header">
        <p class="editor-eyebrow">管理者専用</p>
        <h1>学生時間割の編集</h1>
    </header>

    <?php if (!$loadResult['success']): ?>
        <p class="editor-error"><?php echo steEscape($loadResult['message']); ?></p>
        <a class="button" href="student_editor.php">学生一覧へ戻る</a>
    <?php else: ?>
        <dl class="student-summary">
            <div><dt>学年</dt><dd><?php echo steEscape($grade); ?></dd></div>
            <div><dt>氏名</dt><dd><?php echo steEscape($student['name']); ?></dd></div>
            <div><dt>メールアドレス</dt><dd><?php echo steEscape($student['email']); ?></dd></div>
        </dl>

        <form method="post" action="student_editor_confirm.php">
            <!-- 編集対象とCSRFトークンを確認画面へ引き継ぐ -->
            <input type="hidden" name="csrf_token" value="<?php echo steEscape($csrfToken); ?>">
            <input type="hidden" name="grade" value="<?php echo steEscape($grade); ?>">
            <input type="hidden" name="email" value="<?php echo steEscape($email); ?>">

            <?php foreach ($quarterNames as $quarterKey => $quarterLabel): ?>
                <!-- 四半期ごとに月～土・1～5限の入力欄を表示する -->
                <section class="editor-quarter-section">
                    <h2><?php echo steEscape($quarterLabel); ?></h2>
                    <div class="editor-table-wrap">
                        <table class="editor-schedule-table">
                            <thead>
                                <tr><th>時限</th><?php foreach ($dayNames as $dayLabel): ?><th><?php echo steEscape($dayLabel); ?></th><?php endforeach; ?></tr>
                            </thead>
                            <tbody>
                                <?php for ($period = 1; $period <= 5; $period++): ?>
                                    <tr>
                                        <th><?php echo $period; ?></th>
                                        <?php foreach ($dayNames as $dayKey => $dayLabel): ?>
                                            <td><input type="text" maxlength="100" name="class[<?php echo steEscape($quarterKey); ?>][<?php echo steEscape($dayKey); ?>][<?php echo $period; ?>]" value="<?php echo steEscape($schedule[$quarterKey][$dayKey][(string) $period]); ?>"></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endforeach; ?>

            <div class="editor-actions">
                <button type="submit">確認へ進む</button>
                <a class="button secondary-button" href="student_editor.php">学生一覧へ戻る</a>
            </div>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
