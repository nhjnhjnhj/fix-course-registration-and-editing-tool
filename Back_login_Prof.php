<?php
    //Back_login_Porf 処理を行うためだけのファイル。即時遷移するため、ページは不要。
    session_start(); //_SESSIONを使うための処理
    header('Content-Type: text/html; charset=UTF-8'); //jsonファイル読み込みようにUTF-8に設定

    $json = file_get_contents(__DIR__ . '/json/Prof.json'); //参照するjsonファイルを指定
    $data_Prof = json_decode($json, true); //jsonファイルを配列に変換

    //パスワードが一致するかの確認
    if($data_Prof[0]['password'] == $_POST['password']){
        $_SESSION['prof_login_Success'] = true; //prof_login_Successのフラグをtrueに設定。管理者モードになっていることを示す。
    }
    else{
        $_SESSION['prof_login_Success'] = false; //prof_login_Successのフラグをfalseに設定。管理者モードになっていないことを示す。
    }

    header('Location: index.php'); //トップページへ遷移
    exit();
?>