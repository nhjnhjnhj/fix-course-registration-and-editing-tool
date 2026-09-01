<?php
    session_start();

    $_SESSION = array(); // $_SESSION配列の中身を空にする
    session_unset();     // 念のため$_SESSION変数自体もunset
    session_destroy();   // サーバー側のセッションデータを破棄

    header('Location: index.php'); // トップページへ遷移
    exit();
?>
