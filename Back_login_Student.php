<?php
    session_start();
    header('Content-Type: text/html; charset=UTF-8'); //jsonファイル読み込みようにUTF-8に設定

    const JSON_GRADE_NAME = array('B3','B4','Master'); //jsonファイル(学生のみ)の名前の配列
    const JSON_FILE_NUM = 3; //jsonファイルの数(学年の数)
    $StudentSuccess = false;
    
    //jsonファイルの中から全探索。
    for($i = 0 ; $i < JSON_FILE_NUM ; $i++){
        $json = file_get_contents(__DIR__ . '/json/' . JSON_GRADE_NAME[$i] . '.json'); //参照するjsonファイルを指定
        $data_Student = json_decode($json, true); //jsonファイルを配列に変換

        for($j = 0 ; $j < count($data_Student) ; $j++){ //jsonファイルの配列の数だけループ

            //学籍番号、氏名、パスワードが一致するか確認
            if($data_Student[$j]['email'] == $_POST['email'] && $data_Student[$j]['password'] == $_POST['password']){
                $_SESSION['Student_login_Success'] = true; //生徒がログインしている状態

                $_SESSION['student_json_file'] = JSON_GRADE_NAME[$i] . '.json'; //どのJSONファイルかを保存
                $_SESSION['student_index'] = $j; //何人目かを保存

                header('Location: resist_logined_table.php'); //授業登録ページへ遷移
                exit();
                break;
            }
        }


    }

    header('Location: login.php'); //再度ログインを要求。不足している要素または、入力された要素の不一致を警告する処理を作成予定。
    exit();

?>