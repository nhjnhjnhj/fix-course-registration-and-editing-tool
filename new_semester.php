<?php
session_start();
require_once __DIR__ . '/new_semester_functions.php';

// 管理者以外が確認画面へ入ることを防ぐ。
if (!nspIsAdministrator()) {
    header('Location: index.php');
    exit();
}

// index.phpから送られたPOSTとCSRFトークンを検証する。
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !nspValidateCsrfToken(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    header('Location: index.php');
    exit();
}

$academicYear = (int) date('Y') + 1;
$summaryError = '';
$studentCounts = array('B3' => 0, 'B4' => 0, 'M1' => 0, 'M2' => 0);
// 共有ロック中に各学年のデータを読み込み、確認用の人数を集計する。
$lockHandle = studentDataAcquireLock(__DIR__, false, false);
try {
    if ($lockHandle === false) {
        throw new Exception('学生データを確認できません。');
    }
    $studentData = nspReadAllStudentData(__DIR__);
    foreach ($studentCounts as $grade => $count) {
        $studentCounts[$grade] = count($studentData[$grade]['data']);
    }
} catch (Exception $exception) {
    $summaryError = $exception->getMessage();
}
// 確認用データの読み込みが終わったため共有ロックを解除する。
studentDataReleaseLock($lockHandle);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新学期開始処理の確認</title>
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-process-page">
<main class="admin-process-card">
    <h1>新学期開始処理の確認</h1>
    <p class="admin-warning">この処理はB3・B4・M1・M2の学生データを一括で変更します。</p>

    <?php if ($summaryError !== ''): ?>
        <p class="admin-error"><?php echo htmlspecialchars($summaryError, ENT_QUOTES, 'UTF-8'); ?></p>
        <a class="button" href="index.php">トップページへ戻る</a>
    <?php else: ?>
        <table class="promotion-table">
            <thead><tr><th>現在</th><th>人数</th><th>処理後</th></tr></thead>
            <tbody>
                <tr><td>B3</td><td><?php echo $studentCounts['B3']; ?>名</td><td>B4へ移行</td></tr>
                <tr><td>B4</td><td><?php echo $studentCounts['B4']; ?>名</td><td>M1へ移行</td></tr>
                <tr><td>M1</td><td><?php echo $studentCounts['M1']; ?>名</td><td>M2へ移行</td></tr>
                <tr><td>M2</td><td><?php echo $studentCounts['M2']; ?>名</td><td>移行前バックアップに保存</td></tr>
                <tr><td>新しいB3</td><td>0名</td><td><code>[]</code>で初期化</td></tr>
            </tbody>
        </table>

        <form class="semester-options" method="post" action="process_new_semester.php">
            <!-- 実行画面でも同じ管理者操作であることを確認する。 -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(nspGetCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
            <label>対象年度
                <input type="number" name="academic_year" min="2000" max="2100" value="<?php echo $academicYear; ?>" required>
            </label>
            <fieldset>
                <legend>進級後の時間割</legend>
                <label><input type="radio" name="schedule_policy" value="reset" checked> 空にする（推奨）</label>
                <label><input type="radio" name="schedule_policy" value="keep"> 引き継ぐ</label>
            </fieldset>
            <p>実行前の4ファイルは自動的にバックアップされます。</p>
            <div class="admin-process-actions">
                <button class="new-semester-execute" type="submit">新学期開始処理を実行</button>
                <a class="button secondary-button" href="index.php">キャンセル</a>
            </div>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
