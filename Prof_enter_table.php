<?php
    //管理者モード(Prof_loginSuccess)でログイン済みのユーザーが、Profとして再ログインせずに
    //resist_logined_table.phpへ入れるようにするための中継ファイル。即時遷移するため、ページは不要。
    session_start();

    if(isset($_SESSION['Prof_loginSuccess']) && $_SESSION['Prof_loginSuccess'] == true){
        //Back_login_Student.phpと同じ形でセッションを設定し、resist_logined_table.phpからProf.jsonの内容を編集できるようにする
        $_SESSION['Student_login_Success'] = true;
        $_SESSION['student_json_file'] = 'Prof.json';
        $_SESSION['student_index'] = 0;

        header('Location: resist_logined_table.php');
        exit();
    }

    else{
        header('Location: index.php'); //管理者モードでなければトップページへ戻す
        exit();
    }
?>
