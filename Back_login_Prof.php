<?php
    //Back_login_Porf 処理を行うためだけのファイル。即時遷移するため、ページは不要。
    session_start(); //_SESSIONを使うための処理
    header('Content-Type: text/html; charset=UTF-8'); //jsonファイル読み込みようにUTF-8に設定

    $json = file_get_contents(__DIR__ . '/json/Prof.json'); //参照するjsonファイルを指定
    $data_Prof = json_decode($json, true); //jsonファイルを配列に変換

    $_SESSION['Prof_loginSuccess'] = false; //Prof_loginSuccessのフラグをfalseに設定。管理者モードになっていないことを示す。
    //パスワードが一致するかの確認
    if($data_Prof[0]['password'] == $_POST['password']){
        $_SESSION['Prof_loginSuccess'] = true; //Prof_loginSuccessのフラグをtrueに設定。管理者モードになっていることを示す。
    }

    header('Location: index.php'); //トップページへ遷移
    exit();
?>