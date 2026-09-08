<?php
/* 管理者状態を維持したまま、Prof時間割の編集状態だけを終了する。 */
session_start();

if(isset($_SESSION['Prof_loginSuccess']) && $_SESSION['Prof_loginSuccess'] === true){
    unset($_SESSION['Student_login_Success']);
    unset($_SESSION['student_json_file']);
    unset($_SESSION['student_index']);
}

header('Location: index.php');
exit();
?>
