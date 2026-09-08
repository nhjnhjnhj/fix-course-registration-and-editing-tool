<?php
session_start();
require_once __DIR__ . '/new_semester_functions.php';

// 画面表示と時間割データで使用する設定（共通化はデザイン統合後に実施）
$filterGradeNames = array('B3', 'B4', 'M1', 'M2', 'Prof');
$quarterNames = array('1Q', '2Q', '3Q', '4Q');
$indexDayNames = array('Mon.', 'Tue.', 'Wed.', 'Thu.', 'Fri.', 'Sat.');
$jsonDayNames = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$gradeJsonFiles = array(
    'B3' => 'B3.json',
    'B4' => 'B4.json',
    'M1' => 'M1.json',
    'M2' => 'M2.json',
    'Prof' => 'Prof.json',
);
$gradeTagClasses = array(
    'B3' => 'grade-b3',
    'B4' => 'grade-b4',
    'M1' => 'grade-m1',
    'M2' => 'grade-m2',
    'Prof' => 'grade-prof'
);

// 登録・削除・管理者ログイン後のメッセージ
$message = '';
$messageClass = 'notice';
if (!empty($_SESSION['resist_success'])) {
    $message = '登録が完了しました';
    unset($_SESSION['resist_success']);
} elseif (!empty($_SESSION['delete_success'])) {
    $message = '削除が完了しました';
    unset($_SESSION['delete_success']);
} elseif (!empty($_SESSION['delete_all_success'])) {
    $message = '全ての学生・先生のデータを削除しました';
    unset($_SESSION['delete_all_success']);
} elseif (!empty($_SESSION['delete_prof_class_success'])) {
    $message = '先生の時間割をクリアしました';
    unset($_SESSION['delete_prof_class_success']);
} elseif (!empty($_SESSION['incollect'])) {
    $message = '管理者パスワードが違います';
    $messageClass = 'notice notice-error';
    unset($_SESSION['incollect']);
} elseif (!empty($_SESSION['empty'])) {
    $message = '管理者パスワードが未入力です';
    $messageClass = 'notice notice-error';
    unset($_SESSION['empty']);
}

// URLパラメータから表示条件を取得
$selectedGrades = isset($_GET['grades']) && is_array($_GET['grades']) ? $_GET['grades'] : [];
$allowedFilters = array_merge($filterGradeNames, array('ハイライト'));
$selectedGrades = array_values(array_intersect($selectedGrades, $allowedFilters));
$requestedQuarter = isset($_GET['quarter']) ? $_GET['quarter'] : '';
$selectedQuarter = in_array($requestedQuarter, $quarterNames, true) ? $requestedQuarter : '1Q';
$viewMode = isset($_GET['viewMode']) && $_GET['viewMode'] === 'detail' ? 'detail' : 'summary';
$realSelectedGrades = array_diff($selectedGrades, ['ハイライト']);
$highlightEnabled = in_array('ハイライト', $selectedGrades, true) && count($realSelectedGrades) > 0;
$isProfessor = !empty($_SESSION['Prof_loginSuccess']);
$selectedStudentName = isset($_GET['studentName']) && $_GET['studentName'] !== ''
    ? $_GET['studentName']
    : null;

// 選択された授業を曜日・時限ごとに集計
$cellClasses = [];
foreach ($jsonDayNames as $day) {
    for ($period = 1; $period <= 5; $period++) {
        $cellClasses[$day][$period] = [];
    }
}

$gradesByFile = [];
foreach ($realSelectedGrades as $grade) {
    if (isset($gradeJsonFiles[$grade])) {
        $gradesByFile[$gradeJsonFiles[$grade]][] = $grade;
    }
}

$recordsByFile = [];
foreach ($gradesByFile as $file => $grades) {
    $json = @file_get_contents(__DIR__ . '/json/' . $file);
    $records = $json !== false ? json_decode($json, true) : null;
    $recordsByFile[$file] = is_array($records) ? $records : [];
}

// 選択中の学年から学生名の絞り込み候補を作成する。
$studentNames = [];
foreach ($gradesByFile as $file => $grades) {
    foreach ($recordsByFile[$file] as $record) {
        $recordGrade = isset($record['grade']) ? $record['grade'] : '';
        if (in_array($recordGrade, $grades, true) && !empty($record['name'])) {
            $studentNames[] = $record['name'];
        }
    }
}
$studentNames = array_values(array_unique($studentNames));
sort($studentNames);

// 選択中の学年に該当する学生がいなくなった場合は、氏名の絞り込みを解除する。
if ($selectedStudentName !== null && !in_array($selectedStudentName, $studentNames, true)) {
    $selectedStudentName = null;
}

$quarterKey = 'Quarter' . mb_substr($selectedQuarter, 0, 1);
foreach ($gradesByFile as $file => $grades) {
    $records = $recordsByFile[$file];

    foreach ($records as $record) {
        $recordGrade = isset($record['grade']) ? $record['grade'] : '';
        $recordName = isset($record['name']) ? $record['name'] : '';
        if (!in_array($recordGrade, $grades, true)) {
            continue;
        }
        if ($selectedStudentName !== null && $recordName !== $selectedStudentName) {
            continue;
        }
        foreach ($jsonDayNames as $day) {
            for ($period = 1; $period <= 5; $period++) {
                $periodKey = (string) $period;
                $className = isset($record['class'][$quarterKey][$day][$periodKey])
                    ? trim($record['class'][$quarterKey][$day][$periodKey])
                    : '';
                if ($className !== '') {
                    $cellClasses[$day][$period][] = [
                        'grade' => $recordGrade,
                        'name' => $recordName,
                        'class' => $className,
                    ];
                }
            }
        }
    }
}

$quarterNumber = (int) $selectedQuarter[0];
$termLabel = '2026年度' . ($quarterNumber <= 2 ? '前期' : '後期')
    . ($quarterNumber % 2 === 1 ? '前半' : '後半') . "({$selectedQuarter})";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>受講科目登録・閲覧システム</title>
    <!-- デザインは外部CSSで管理し、PHPの処理と分離する -->
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<main class="page">
    <h1 class="title">受講科目登録・閲覧システム</h1>
    <div class="title-rule" aria-hidden="true">◆ ❄ ◆</div>

    <?php if ($message !== ''): ?>
        <p class="<?= $messageClass ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <!-- 学年、ハイライト、表示形式の選択 -->
    <form id="filter-form" method="get" action="index.php">
        <section class="filters" aria-label="表示条件">
            <?php foreach ($filterGradeNames as $grade): ?>
                <label><input type="checkbox" name="grades[]" value="<?= $grade ?>" <?= in_array($grade, $selectedGrades, true) ? 'checked' : '' ?> onchange="this.form.submit()"> <?= $grade === 'Prof' ? '教員' : $grade ?></label>
            <?php endforeach; ?>
            <label><input type="checkbox" name="grades[]" value="ハイライト" <?= $highlightEnabled ? 'checked' : '' ?> onchange="this.form.submit()"> ハイライト</label>
            <label class="student-filter">表示する学生
                <select name="studentName" onchange="this.form.submit()">
                    <option value="" <?= $selectedStudentName === null ? 'selected' : '' ?>>(指定なし)</option>
                    <?php foreach ($studentNames as $studentName): ?>
                        <option value="<?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?>" <?= $studentName === $selectedStudentName ? 'selected' : '' ?>><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <select name="viewMode" aria-label="表示形式" onchange="this.form.submit()">
                <option value="summary" <?= $viewMode === 'summary' ? 'selected' : '' ?>>サマリ表示</option>
                <option value="detail" <?= $viewMode === 'detail' ? 'selected' : '' ?>>詳細表示</option>
            </select>
        </section>

        <section class="summary">
            <div class="term">
                <span class="calendar" aria-hidden="true">🗓</span>
                <span><?= htmlspecialchars($termLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <img class="mascot" src="photo/第<?= $quarterNumber ?>Q.png" alt="第<?= $quarterNumber ?>Qを案内するペンギン">
            </div>
            <?php if ($isProfessor): ?>
                <a class="edit-button" href="Prof_enter_table.php"><span aria-hidden="true">❄</span> 時間割登録・編集</a>
            <?php else: ?>
                <!-- JavaScriptを使わず、同じ画面内にログイン方法を表示する -->
                <details class="edit-menu">
                    <summary class="edit-button"><span aria-hidden="true">❄</span> 時間割登録・編集</summary>
                    <div class="edit-options">
                        <a href="login.php">ログイン</a>
                        <a href="resist_new_table.php">新規登録</a>
                    </div>
                </details>
            <?php endif; ?>
        </section>

        <nav class="quarter-tabs" aria-label="クォーター選択">
            <?php foreach ($quarterNames as $quarter): ?>
                <label class="quarter-tab <?= $quarter === $selectedQuarter ? 'active' : '' ?>">
                    <input type="radio" name="quarter" value="<?= $quarter ?>" <?= $quarter === $selectedQuarter ? 'checked' : '' ?> onchange="this.form.submit()">
                    <?= $quarter ?>
                </label>
            <?php endforeach; ?>
        </nav>
    </form>

    <!-- 曜日・時限別の時間割 -->
    <div class="schedule-wrap">
        <table class="schedule">
            <thead>
                <tr><th class="corner" aria-label="編集">✎</th><?php foreach ($indexDayNames as $day): ?><th scope="col"><?= $day ?></th><?php endforeach; ?></tr>
            </thead>
            <tbody>
                <?php for ($period = 1; $period <= 5; $period++): ?>
                    <tr>
                        <th class="period" scope="row"><?= $period ?></th>
                        <?php foreach ($jsonDayNames as $day): ?>
                            <?php $isHighlighted = $highlightEnabled && empty($cellClasses[$day][$period]); ?>
                            <td class="<?= $isHighlighted ? 'highlighted' : '' ?>">
                                <?php foreach ($cellClasses[$day][$period] as $entry): ?>
                                    <?php if ($viewMode === 'detail'): ?>
                                        <div class="course-detail">
                                            <strong><?= htmlspecialchars($entry['grade'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?= htmlspecialchars($entry['name'], ENT_QUOTES, 'UTF-8') ?><br>
                                            <?= htmlspecialchars($entry['class'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php else: ?>
                                        <?php $tooltip = '氏名: ' . $entry['name'] . "\n授業: " . $entry['class']; ?>
                                        <?php $gradeTagClass = isset($gradeTagClasses[$entry['grade']]) ? $gradeTagClasses[$entry['grade']] : ''; ?>
                                        <span class="course-tag <?= $gradeTagClass ?>" title="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($entry['grade'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <?php if ($isProfessor): ?>
        <!-- 管理者向けの機能を追加しやすいカード形式でまとめる -->
        <section class="admin-tools" aria-labelledby="admin-tools-title">
            <div class="admin-tools-heading">
                <div>
                    <h2 id="admin-tools-title">管理者ツール</h2>
                    <p>学生データや時間割に関する管理操作を行います。</p>
                </div>
                <span class="admin-tools-badge">管理者専用</span>
            </div>

            <div class="admin-tool-grid">
                <article class="admin-tool-card semester-tool-card">
                    <span class="admin-tool-icon" aria-hidden="true">🗓</span>
                    <h3>新学期開始</h3>
                    <p>B3→B4→M1→M2の順に学生データを進級させます。</p>
                    <form method="post" action="new_semester.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(nspGetCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                        <button class="admin-tool-button new-semester-button" type="submit">確認画面へ</button>
                    </form>
                </article>

                <article class="admin-tool-card initialize-tool-card">
                    <span class="admin-tool-icon" aria-hidden="true">🗑</span>
                    <h3>ファイル初期化</h3>
                    <p>対象ファイルを選択し、データを初期状態へ戻します。</p>
                    <a class="button admin-tool-button initialize-button" href="delete_all.php">初期化画面へ</a>
                </article>

                <article class="admin-tool-card editor-tool-card">
                    <span class="admin-tool-icon" aria-hidden="true">✎</span>
                    <h3>エディタ</h3>
                    <p>JSONデータの内容を管理画面から確認・編集します。</p>
                    <a class="button admin-tool-button editor-button" href="student_editor.php">エディタを開く</a>
                </article>
            </div>

            <small class="admin-tools-note">新学期開始、ファイル初期化、学生時間割エディタを利用できます。</small>
        </section>
    <?php endif; ?>

    <!-- 管理者ログインとログアウト -->
    <div class="footer-actions">
        <?php if ($isProfessor): ?>
            <span class="login-status">管理者モード</span>
            <a class="admin-button" href="logout.php">ログアウト</a>
        <?php else: ?>
            <form class="admin-login" method="post" action="Back_login_Prof.php">
                <input class="login-field" type="password" name="password" aria-label="管理者パスワード" placeholder="管理者パスワード">
                <button class="admin-button" type="submit"><span aria-hidden="true">⚙</span> 管理</button>
            </form>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
