<?php
    //Back_login_Porf 処理を行うためだけのファイル。即時遷移するため、ページは不要。
    session_start(); //_SESSIONを使うための処理
    header('Content-Type: text/html; charset=UTF-8'); //jsonファイル読み込みようにUTF-8に設定

    $json = file_get_contents(__DIR__ . '/json/Prof.json'); //参照するjsonファイルを指定
    $data_Prof = json_decode($json, true); //jsonファイルを配列に変換

    //遷移先はオープンリダイレクト対策のため固定の2択のみ(通常ログインはindex.php、全データ削除時はdelete_all.php)
    $purpose = $_POST['purpose'] ?? '';
    $redirectTo = ($purpose === 'deleteAll') ? 'delete_all.php' : 'index.php';

    //全データ削除用のパスワード再確認では、既に管理者モードでログイン済みの状態からの再入力なので、
    //間違えてもProf_loginSuccessをfalseにせず管理者モードを維持したままdelete_all.phpでの再入力を促す。
    if($purpose !== 'deleteAll'){
        $_SESSION['Prof_loginSuccess'] = false; //Prof_loginSuccessのフラグをfalseに設定。管理者モードになっていないことを示す。
    }

    //同一セッションで過去に学生としてログインした状態が残っていると、Profログイン後もresist_logined_table.phpで学年欄が表示されてしまうためクリアする
    unset($_SESSION['Student_login_Success']);
    unset($_SESSION['student_json_file']);
    unset($_SESSION['student_index']);

    //パスワードが一致するかの確認
    if($data_Prof[0]['password'] == $_POST['password']){
        $_SESSION['Prof_loginSuccess'] = true; //Prof_loginSuccessのフラグをtrueに設定。管理者モードになっていることを示す。

        if($purpose === 'deleteAll'){
            $_SESSION['deleteAllAuthorized'] = true; //delete_all.phpで全データ削除を実行してよいことを示すフラグ
        }
    }

    else if($_POST['password'] == ''){
        $_SESSION['empty'] = true;
    }

    else if($data_Prof[0]['password'] != $_POST['password']){
        $_SESSION['incollect'] = true;
    }

    header('Location: ' . $redirectTo); //遷移
    exit();
?>