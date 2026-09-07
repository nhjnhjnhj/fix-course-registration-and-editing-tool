<?php
    session_start();
    header('Content-Type: text/html; charset=UTF-8'); //jsonファイル読み込みようにUTF-8に設定

    const JSON_GRADE_NAME = array('B3','B4','Master'); //jsonファイル(学生のみ)の名前の配列
    const JSON_FILE_NUM = 3; //jsonファイルの数(学年の数)
    $StudentSuccess = false;
    
    $_SESSION['NotFound_Student'] = false;

    //jsonファイルの中から全探索。
    for($i = 0 ; $i < JSON_FILE_NUM ; $i++){
        $json = file_get_contents(__DIR__ . '/json/' . JSON_GRADE_NAME[$i] . '.json'); //参照するjsonファイルを指定
        $data_Student = json_decode($json, true); //jsonファイルを配列に変換

        for($j = 0 ; $j < count($data_Student) ; $j++){ //jsonファイルの配列の数だけループ

            //メールアドレス、パスワードが一致するか確認
            if($data_Student[$j]['email'] == $_POST['email'] && $data_Student[$j]['password'] == $_POST['password']){
                $_SESSION['Student_login_Success'] = true; //生徒がログインしている状態
                unset($_SESSION['Prof_loginSuccess']); //同一セッションで過去にProfログインした状態が残らないようクリア

                $_SESSION['student_json_file'] = JSON_GRADE_NAME[$i] . '.json'; //どのJSONファイルかを保存
                $_SESSION['student_index'] = $j; //何人目かを保存

                header('Location: resist_logined_table.php'); //授業登録ページへ遷移
                exit();
                break;
            }

            //メールアドレスが異なる or パスワードが異なる
            if(($data_Student[$j]['email'] != $_POST['email'] && $data_Student[$j]['password'] == $_POST['password']) || ($data_Student[$j]['email'] == $_POST['email'] && $data_Student[$j]['password'] != $_POST['password'])){
                $_SESSION['incollect'] = true; //メールアドレスまたはパスワードが違う場合のフラグを立てる
                header('Location: login.php'); //再度ログインを要求。
                exit();
                break;
            }

            if($_POST['email'] == '' || $_POST['password'] == ''){
                $_SESSION['empty'] = true; //メールアドレスまたはパスワードが未入力の場合のフラグを立てる
                header('Location: login.php'); //再度ログインを要求。
                exit();
                break;
            }
        }


    }

    if($_SESSION['Student_login_Success'] != true && $_SESSION['incollect'] != true){
        $_SESSION['NotFound_Student'] = true; //ユーザーが存在しない場合のフラグを立てる
        header('Location: login.php'); //再度ログインを要求。
        exit();
    }

?>