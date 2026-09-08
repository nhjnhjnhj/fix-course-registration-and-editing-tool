<?php

/* 学生・教員JSONを扱う全処理で共有するロック操作（PHP 5.4.16対応） */

// ロックファイルの保存先を準備し、共有ロックまたは排他ロックを取得する。
function studentDataAcquireLock($projectRoot, $exclusive, $nonBlocking)
{
    $lockDirectory = $projectRoot . '/data/lock';
    if (!is_dir($lockDirectory)) {
        if (!@mkdir($lockDirectory, 0770, true) && !is_dir($lockDirectory)) {
            return false;
        }
    }

    $lockFile = $lockDirectory . '/student_data.lock';
    // 配置済みファイルは内容を書き換えないため、読み取りモードでもロックできる。
    $handle = fopen($lockFile, is_file($lockFile) ? 'r' : 'c');
    if ($handle === false) {
        return false;
    }

    $operation = $exclusive ? LOCK_EX : LOCK_SH;
    if ($nonBlocking) {
        $operation = $operation | LOCK_NB;
    }

    if (!flock($handle, $operation)) {
        fclose($handle);
        return false;
    }

    return $handle;
}

// 取得済みのロックを解除し、ロックファイルを閉じる。
function studentDataReleaseLock($handle)
{
    if (is_resource($handle)) {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
