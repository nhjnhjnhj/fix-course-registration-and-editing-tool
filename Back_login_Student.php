<?php
session_start();
require_once __DIR__ . '/student_data_lock.php';
header('Content-Type: text/html; charset=UTF-8');

// 新しい認証結果だけを使用するため、以前の学生ログイン情報とエラーを消去する。
unset($_SESSION['Student_login_Success']);
unset($_SESSION['student_json_file']);
unset($_SESSION['student_index']);
unset($_SESSION['NotFound_Student']);
unset($_SESSION['incollect']);
unset($_SESSION['empty']);
unset($_SESSION['login_system_error']);

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';

// 未入力の場合はJSONを検索せずログイン画面へ戻す。
if($email === '' || $password === ''){
    $_SESSION['empty'] = true;
    header('Location: login.php');
    exit();
}

$gradeNames = array('B3', 'B4', 'M1', 'M2');
$matchedFile = '';
$matchedIndex = -1;
$systemError = false;

// 認証中にJSONが更新されないよう、全学年の検索が終わるまで共有ロックを維持する。
$dataLockHandle = studentDataAcquireLock(__DIR__, false, false);
if($dataLockHandle === false){
    $_SESSION['login_system_error'] = true;
    header('Location: login.php');
    exit();
}

foreach($gradeNames as $gradeName){
    $fileName = $gradeName . '.json';
    $json = file_get_contents(__DIR__ . '/json/' . $fileName);
    if($json === false){
        $systemError = true;
        break;
    }

    $students = json_decode($json, true);
    if(!is_array($students)){
        $systemError = true;
        break;
    }

    foreach($students as $index => $student){
        if(!is_array($student) || !isset($student['email']) || !isset($student['password'])){
            continue;
        }

        // メールアドレスとパスワードが同じ学生だけをログイン成功とする。
        if((string) $student['email'] === $email && (string) $student['password'] === $password){
            $matchedFile = $fileName;
            $matchedIndex = $index;
            break 2;
        }
    }
}

studentDataReleaseLock($dataLockHandle);

if($systemError){
    $_SESSION['login_system_error'] = true;
    header('Location: login.php');
    exit();
}

if($matchedFile !== '' && $matchedIndex >= 0){
    // 認証成功時にセッションIDを更新し、以前の管理者ログイン状態を解除する。
    session_regenerate_id(true);
    $_SESSION['Student_login_Success'] = true;
    $_SESSION['student_json_file'] = $matchedFile;
    $_SESSION['student_index'] = $matchedIndex;
    unset($_SESSION['Prof_loginSuccess']);

    header('Location: resist_logined_table.php');
    exit();
}

// 一致する学生がいなければ、入力項目を特定せず共通の認証エラーを表示する。
$_SESSION['incollect'] = true;
header('Location: login.php');
exit();
?>
