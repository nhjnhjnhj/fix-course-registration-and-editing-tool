<?php

/* 管理者用学生時間割エディタの共通処理（PHP 5.4.16対応） */
require_once __DIR__ . '/new_semester_functions.php';

// エディタ画面で使用するCSRFトークンを取得する。
function steGetCsrfToken()
{
    if (!isset($_SESSION['student_editor_csrf'])) {
        $randomBytes = function_exists('openssl_random_pseudo_bytes')
            ? openssl_random_pseudo_bytes(24)
            : false;
        $_SESSION['student_editor_csrf'] = $randomBytes !== false
            ? bin2hex($randomBytes)
            : sha1(uniqid((string) mt_rand(), true));
    }

    return $_SESSION['student_editor_csrf'];
}

// 送信されたCSRFトークンを検証する。
function steValidateCsrfToken($token)
{
    return isset($_SESSION['student_editor_csrf'])
        && is_string($token)
        && $_SESSION['student_editor_csrf'] === $token;
}

// HTMLへ表示する文字列をエスケープする。
function steEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// 指定された学年が編集対象か確認する。
function steIsAllowedGrade($grade)
{
    $files = nspStudentFiles();
    return isset($files[$grade]);
}

// 学年に対応するJSONファイルのパスを返す。
function steStudentFilePath($projectRoot, $grade)
{
    $files = nspStudentFiles();
    return isset($files[$grade]) ? $projectRoot . '/json/' . $files[$grade] : false;
}

// メールアドレスが一致する学生を検索し、重複も確認する。
function steFindStudent($records, $email)
{
    $matchedIndex = -1;
    $matchedStudent = null;
    $matchedCount = 0;

    foreach ($records as $index => $record) {
        if (isset($record['email']) && (string) $record['email'] === (string) $email) {
            $matchedIndex = $index;
            $matchedStudent = $record;
            $matchedCount++;
        }
    }

    return array(
        'index' => $matchedIndex,
        'student' => $matchedStudent,
        'count' => $matchedCount
    );
}

// 学年とメールアドレスから最新の学生情報を読み込む。
function steLoadStudent($projectRoot, $grade, $email)
{
    if (!steIsAllowedGrade($grade) || trim($email) === '') {
        return array('success' => false, 'message' => '編集対象の指定が正しくありません。');
    }

    // JSONの読み込み中に別処理が更新しないよう共有ロックを取得する。
    $lockHandle = studentDataAcquireLock($projectRoot, false, false);
    if ($lockHandle === false) {
        return array('success' => false, 'message' => '学生データを読み込むためのロックを取得できません。');
    }

    try {
        $studentData = nspReadStudentData(steStudentFilePath($projectRoot, $grade));
        $match = steFindStudent($studentData['data'], $email);
        if ($match['count'] === 0) {
            throw new Exception('対象の学生が見つかりません。');
        }
        if ($match['count'] > 1) {
            throw new Exception('同じメールアドレスの学生が複数存在するため編集できません。');
        }

        studentDataReleaseLock($lockHandle);
        return array('success' => true, 'student' => $match['student']);
    } catch (Exception $exception) {
        studentDataReleaseLock($lockHandle);
        return array('success' => false, 'message' => $exception->getMessage());
    }
}

// 任意の文字列を指定文字数までに切り詰める。
function steLimitText($value, $length)
{
    $value = trim((string) $value);
    return function_exists('mb_substr')
        ? mb_substr($value, 0, $length, 'UTF-8')
        : substr($value, 0, $length);
}

// 受信した時間割を1Q～4Q・月～土・1～5限の固定構造へ整える。
function steNormalizeSchedule($input)
{
    $normalized = array();
    $days = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
    $input = is_array($input) ? $input : array();

    for ($quarter = 1; $quarter <= 4; $quarter++) {
        $quarterKey = 'Quarter' . $quarter;
        $normalized[$quarterKey] = array();
        foreach ($days as $day) {
            $normalized[$quarterKey][$day] = array();
            for ($period = 1; $period <= 5; $period++) {
                $value = isset($input[$quarterKey][$day][$period])
                    ? $input[$quarterKey][$day][$period]
                    : '';
                $normalized[$quarterKey][$day][(string) $period] = steLimitText($value, 100);
            }
        }
    }

    return $normalized;
}

// 対象学生の時間割だけを排他ロック中に更新する。
function steUpdateStudentSchedule($projectRoot, $grade, $email, $schedule)
{
    if (!steIsAllowedGrade($grade) || trim($email) === '') {
        return array('success' => false, 'message' => '編集対象の指定が正しくありません。');
    }

    // 最新データの再読込から保存完了まで排他ロックで保護する。
    $lockHandle = studentDataAcquireLock($projectRoot, true, false);
    if ($lockHandle === false) {
        return array('success' => false, 'message' => '学生データを更新するためのロックを取得できません。');
    }

    try {
        $path = steStudentFilePath($projectRoot, $grade);
        $studentData = nspReadStudentData($path);
        $records = $studentData['data'];
        $match = steFindStudent($records, $email);

        if ($match['count'] === 0) {
            throw new Exception('対象の学生が見つかりません。');
        }
        if ($match['count'] > 1) {
            throw new Exception('同じメールアドレスの学生が複数存在するため保存できません。');
        }

        // 個人情報は変更せず、対象学生のclassだけを差し替える。
        $records[$match['index']]['class'] = steNormalizeSchedule($schedule);
        if (!nspAtomicWrite($path, nspEncodeJson($records))) {
            throw new Exception('時間割をJSONへ保存できませんでした。');
        }
        nspReadStudentData($path);

        studentDataReleaseLock($lockHandle);
        return array('success' => true, 'student' => $records[$match['index']]);
    } catch (Exception $exception) {
        studentDataReleaseLock($lockHandle);
        return array('success' => false, 'message' => $exception->getMessage());
    }
}
