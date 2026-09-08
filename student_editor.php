<?php
/* 管理者が時間割を編集する学生を学年別に選択する画面 */
session_start();
require_once __DIR__ . '/student_editor_functions.php';

// 管理者以外からの直接アクセスを防ぐ。
if (!nspIsAdministrator()) {
    header('Location: index.php');
    exit();
}

$loadError = '';
$studentsByGrade = array('B3' => array(), 'B4' => array(), 'M1' => array(), 'M2' => array());

// 読み込み中の更新を避けるため、共有ロックを取得して全学年を読み込む。
$lockHandle = nspAcquireDataLock(__DIR__, false, false);
try {
    if ($lockHandle === false) {
        throw new Exception('学生データを読み込むためのロックを取得できません。');
    }
    $allStudentData = nspReadAllStudentData(__DIR__);
    foreach ($studentsByGrade as $grade => $students) {
        $studentsByGrade[$grade] = $allStudentData[$grade]['data'];
    }
} catch (Exception $exception) {
    $loadError = $exception->getMessage();
}
nspReleaseDataLock($lockHandle);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>学生時間割エディタ</title>
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/editor.css">
</head>
<body class="student-editor-page">
<main class="student-editor-shell">
    <header class="student-editor-header">
        <p class="editor-eyebrow">管理者専用</p>
        <h1>学生時間割エディタ</h1>
        <p>時間割を編集する学生を選択してください。</p>
    </header>

    <?php if ($loadError !== ''): ?>
        <p class="editor-error"><?php echo steEscape($loadError); ?></p>
    <?php else: ?>
        <div class="grade-student-grid">
            <?php foreach ($studentsByGrade as $grade => $students): ?>
                <section class="grade-student-card">
                    <div class="grade-student-heading">
                        <h2><?php echo steEscape($grade); ?></h2>
                        <span><?php echo count($students); ?>名</span>
                    </div>
                    <?php if (count($students) === 0): ?>
                        <p class="empty-students">学生データはありません。</p>
                    <?php else: ?>
                        <div class="student-buttons">
                            <?php foreach ($students as $student): ?>
                                <!-- 学年とメールアドレスを使って編集対象を特定する -->
                                <form method="post" action="student_editor_edit.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo steEscape(steGetCsrfToken()); ?>">
                                    <input type="hidden" name="grade" value="<?php echo steEscape($grade); ?>">
                                    <input type="hidden" name="email" value="<?php echo steEscape($student['email']); ?>">
                                    <button class="student-select-button" type="submit">
                                        <?php echo steEscape($student['name']); ?>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <a class="button editor-back-button" href="index.php">トップページへ戻る</a>
</main>
</body>
</html>
