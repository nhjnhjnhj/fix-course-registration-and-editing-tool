<?php

/*
 * 新学期開始処理の共通関数。
 * 友人側のプログラムへ結合しやすいよう、画面表示とファイル操作を分離する。
 * PHP 5.4.16で使用できる構文だけを使用する。
 */
require_once __DIR__ . '/student_data_lock.php';

// 現在のセッションが管理者モードか確認する。
function nspIsAdministrator()
{
    return isset($_SESSION['Prof_loginSuccess'])
        && $_SESSION['Prof_loginSuccess'] === true;
}

// 新学期処理フォームで使用するCSRFトークンを取得する。
function nspGetCsrfToken()
{
    if (!isset($_SESSION['new_semester_csrf'])) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $randomBytes = openssl_random_pseudo_bytes(24);
        } else {
            $randomBytes = false;
        }
        if ($randomBytes !== false) {
            $_SESSION['new_semester_csrf'] = bin2hex($randomBytes);
        } else {
            $_SESSION['new_semester_csrf'] = sha1(uniqid((string) mt_rand(), true));
        }
    }

    return $_SESSION['new_semester_csrf'];
}

// 送信されたCSRFトークンがセッションの値と一致するか確認する。
function nspValidateCsrfToken($token)
{
    return isset($_SESSION['new_semester_csrf'])
        && is_string($token)
        && $_SESSION['new_semester_csrf'] === $token;
}

// 必要なディレクトリがなければ作成する。
function nspEnsureDirectory($directory)
{
    return is_dir($directory) || mkdir($directory, 0770, true);
}

// 学年と学生JSONファイル名の対応を返す。
function nspStudentFiles()
{
    return array(
        'B3' => 'B3.json',
        'B4' => 'B4.json',
        'M1' => 'M1.json',
        'M2' => 'M2.json'
    );
}

// 学生JSONを読み込み、形式と必須項目を検証する。
function nspReadStudentData($path)
{
    if (!is_file($path) || !is_readable($path)) {
        throw new Exception('JSONファイルを読み込めません: ' . basename($path));
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new Exception('JSONファイルの読み込みに失敗しました: ' . basename($path));
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new Exception('JSONの形式が正しくありません: ' . basename($path));
    }

    foreach ($data as $index => $record) {
        if (!is_array($record)
            || !isset($record['grade'])
            || !isset($record['name'])
            || !isset($record['email'])
            || !isset($record['password'])
            || !isset($record['class'])) {
            throw new Exception('必須項目が不足しています: ' . basename($path) . ' #' . $index);
        }
    }

    return array('raw' => $raw, 'data' => $data);
}

// B3・B4・M1・M2の学生JSONをまとめて読み込む。
function nspReadAllStudentData($projectRoot)
{
    $result = array();
    foreach (nspStudentFiles() as $grade => $fileName) {
        $result[$grade] = nspReadStudentData($projectRoot . '/json/' . $fileName);
    }

    return $result;
}

// 全クォーター・曜日・時限が空欄の時間割を作成する。
function nspEmptySchedule()
{
    $schedule = array();
    $days = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
    for ($quarter = 1; $quarter <= 4; $quarter++) {
        $quarterData = array();
        foreach ($days as $day) {
            $quarterData[$day] = array(
                '1' => '', '2' => '', '3' => '', '4' => '', '5' => ''
            );
        }
        $schedule['Quarter' . $quarter] = $quarterData;
    }

    return $schedule;
}

// 学生の学年を更新し、指定された場合は時間割を初期化する。
function nspPromoteRecords($records, $newGrade, $resetSchedule)
{
    $promoted = array();
    foreach ($records as $record) {
        $record['grade'] = $newGrade;
        if ($resetSchedule) {
            $record['class'] = nspEmptySchedule();
        }
        $promoted[] = $record;
    }

    return $promoted;
}

// 配列を日本語が読める整形済みJSONへ変換する。
function nspEncodeJson($data)
{
    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if ($json === false) {
        throw new Exception('JSONデータの作成に失敗しました。');
    }

    return $json . "\n";
}

// 一時ファイルへ書いた後に置き換え、書き込み途中の破損を防ぐ。
function nspAtomicWrite($path, $contents)
{
    $temporaryPath = tempnam(dirname($path), '.new-semester-');
    if ($temporaryPath === false) {
        return false;
    }

    $handle = fopen($temporaryPath, 'wb');
    if ($handle === false) {
        @unlink($temporaryPath);
        return false;
    }

    $written = fwrite($handle, $contents);
    $flushed = fflush($handle);
    fclose($handle);

    if ($written === false || $written !== strlen($contents) || !$flushed) {
        @unlink($temporaryPath);
        return false;
    }

    if (!rename($temporaryPath, $path)) {
        @unlink($temporaryPath);
        return false;
    }

    return true;
}

// 二重実行防止に使用する履歴ファイルのパスを返す。
function nspHistoryPath($projectRoot)
{
    return $projectRoot . '/data/new_semester_history.json';
}

// 新学期開始処理の実行済み年度を読み込む。
function nspReadHistory($projectRoot)
{
    $path = nspHistoryPath($projectRoot);
    if (!is_file($path)) {
        return array();
    }

    $raw = file_get_contents($path);
    $history = $raw !== false ? json_decode($raw, true) : null;
    return is_array($history) ? $history : array();
}

// 指定年度の新学期開始処理が完了済みか確認する。
function nspWasAcademicYearProcessed($projectRoot, $academicYear)
{
    foreach (nspReadHistory($projectRoot) as $entry) {
        if (isset($entry['academic_year'])
            && (int) $entry['academic_year'] === (int) $academicYear
            && isset($entry['status'])
            && $entry['status'] === 'completed') {
            return true;
        }
    }

    return false;
}

// 学年移行前の4つの学生JSONを日付付きでバックアップする。
function nspCreateBackup($projectRoot, $academicYear, $sourceData)
{
    $backupRoot = $projectRoot . '/data/new_semester_backups';
    if (!nspEnsureDirectory($backupRoot)) {
        throw new Exception('バックアップ用ディレクトリを作成できません。');
    }

    $backupDirectory = $backupRoot . '/' . (int) $academicYear . '_start_' . date('Ymd_His');
    $suffix = 1;
    while (file_exists($backupDirectory)) {
        $backupDirectory = $backupRoot . '/' . (int) $academicYear . '_start_' . date('Ymd_His') . '_' . $suffix;
        $suffix++;
    }

    if (!mkdir($backupDirectory, 0770, true)) {
        throw new Exception('移行前バックアップを作成できません。');
    }

    foreach (nspStudentFiles() as $grade => $fileName) {
        if (file_put_contents($backupDirectory . '/' . $fileName, $sourceData[$grade]['raw']) === false) {
            throw new Exception('バックアップの保存に失敗しました: ' . $fileName);
        }
        nspReadStudentData($backupDirectory . '/' . $fileName);
    }

    return $backupDirectory;
}

// バックアップ、学年移行、保存、失敗時の復元をまとめて実行する。
function nspRunMigration($projectRoot, $academicYear, $resetSchedule)
{
    $academicYear = (int) $academicYear;
    if ($academicYear < 2000 || $academicYear > 2100) {
        return array('success' => false, 'message' => '対象年度が正しくありません。');
    }

    $lockHandle = studentDataAcquireLock($projectRoot, true, true);
    if ($lockHandle === false) {
        return array('success' => false, 'message' => '別のデータ更新処理が実行中です。時間を置いて再実行してください。');
    }

    $sourceData = null;
    $backupDirectory = '';
    try {
        if (nspWasAcademicYearProcessed($projectRoot, $academicYear)) {
            throw new Exception($academicYear . '年度の処理はすでに完了しています。');
        }

        $sourceData = nspReadAllStudentData($projectRoot);
        $backupDirectory = nspCreateBackup($projectRoot, $academicYear, $sourceData);

        $newData = array(
            'B3' => array(),
            'B4' => nspPromoteRecords($sourceData['B3']['data'], 'B4', $resetSchedule),
            'M1' => nspPromoteRecords($sourceData['B4']['data'], 'M1', $resetSchedule),
            'M2' => nspPromoteRecords($sourceData['M1']['data'], 'M2', $resetSchedule)
        );

        foreach (nspStudentFiles() as $grade => $fileName) {
            $path = $projectRoot . '/json/' . $fileName;
            if (!nspAtomicWrite($path, nspEncodeJson($newData[$grade]))) {
                throw new Exception('移行後データの保存に失敗しました: ' . $fileName);
            }
            nspReadStudentData($path);
        }

        $history = nspReadHistory($projectRoot);
        $history[] = array(
            'academic_year' => $academicYear,
            'processed_at' => date('Y-m-d H:i:s'),
            'status' => 'completed'
        );
        if (!nspEnsureDirectory(dirname(nspHistoryPath($projectRoot)))
            || !nspAtomicWrite(nspHistoryPath($projectRoot), nspEncodeJson($history))) {
            throw new Exception('二重実行防止データを保存できません。');
        }

        $counts = array();
        foreach ($newData as $grade => $records) {
            $counts[$grade] = count($records);
        }

        studentDataReleaseLock($lockHandle);
        return array(
            'success' => true,
            'message' => $academicYear . '年度の新学期開始処理が完了しました。',
            'counts' => $counts,
            'old_m2_count' => count($sourceData['M2']['data']),
            'backup_directory' => $backupDirectory
        );
    } catch (Exception $exception) {
        if (is_array($sourceData)) {
            foreach (nspStudentFiles() as $grade => $fileName) {
                nspAtomicWrite($projectRoot . '/json/' . $fileName, $sourceData[$grade]['raw']);
            }
        }
        studentDataReleaseLock($lockHandle);
        return array(
            'success' => false,
            'message' => $exception->getMessage(),
            'backup_directory' => $backupDirectory
        );
    }
}
