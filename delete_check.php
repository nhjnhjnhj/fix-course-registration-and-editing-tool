<?php
    session_start();

    //ブラウザの「戻る」操作でキャッシュ(bfcache)から古い画面が復元されるのを防ぐ(HTML出力より前に呼ぶ必要がある)
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>削除確認ページ</title>
        <link rel="stylesheet" href="assets/css/common.css">
        <link rel="stylesheet" href="assets/css/confirm.css">
    </head>
    <body class="delete-page">
    <script>
        //ブラウザキャッシュ(bfcache)から復元された場合は強制的に再読み込みし、PHPを再実行させる
        window.addEventListener('pageshow', function(event){
            if(event.persisted){
                window.location.reload();
            }
        });
    </script>

    <?php
    $code = http_response_code(); //HTTPレスポンスコードを取得(404 Not Foundなど)
    const HTTP_OK = 200; //レスポンスコード200 = アクセス許可
    const PERIOD_COUNT = 5; //1日あたりの限数
    $jsonDayNames = array('Mon','Tue','Wed','Thu','Fri','Sat'); //jsonファイルの曜日要素の配列
    $quarterKeys = array('Quarter1', 'Quarter2', 'Quarter3', 'Quarter4'); //jsonファイルの学期キー

    // PHP 5.4でもJSON解析エラーの内容を表示できるようにする。
    function deleteJsonErrorMessage(){
        return function_exists('json_last_error_msg') ? json_last_error_msg() : (string)json_last_error();
    }

    if($code == HTTP_OK){

        if(!isset($_SESSION['Student_login_Success']) || $_SESSION['Student_login_Success'] != true){ //ログインしていなければ削除不可
            echo 'アクセス失敗(ログインしていません)';
        }

        else{

            if(isset($_POST['confirmDelete']) && $_POST['confirmDelete'] == '1'){
                //「はい」が押されたのでjsonファイルから該当ユーザーを削除する
                $writeError = '';

                $jsonFile = __DIR__ . '/json/' . $_SESSION['student_json_file'];
                $json = file_get_contents($jsonFile);

                if($json === false){
                    $writeError = 'jsonファイルの読み込みに失敗しました: ' . $jsonFile;
                }

                else{
                    $data = json_decode($json, true);

                    if($data === null){
                        $writeError = 'jsonファイルの解析に失敗しました: ' . $jsonFile . '(json_last_error: ' . deleteJsonErrorMessage() . ')';
                    }

                    else{
                        $studentIndex = $_SESSION['student_index'];
                        $isProf = ($_SESSION['student_json_file'] === 'Prof.json');

                        if($isProf){
                            //Profはアカウント自体を削除するとBack_login_Prof.phpでログインできなくなるため、
                            //grade/name/email/passwordは維持し、classの中身(各コマ)だけ空文字にリセットする(delete_all.phpと同じロジック)
                            $emptyClass = array();
                            foreach($quarterKeys as $qKey){
                                $emptyClass[$qKey] = array();
                                foreach($jsonDayNames as $day){
                                    $emptyClass[$qKey][$day] = array();
                                    for($p = 1 ; $p <= PERIOD_COUNT ; $p++){
                                        $emptyClass[$qKey][$day][(string)$p] = '';
                                    }
                                }
                            }
                            $data[$studentIndex]['class'] = $emptyClass;
                        }

                        else{
                            array_splice($data, $studentIndex, 1); //該当ユーザーを削除し、以降のindexを詰める
                        }

                        if(file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false){
                            $writeError = 'jsonファイルへの書き込みに失敗しました: ' . $jsonFile . '(書き込み権限を確認してください)';
                        }
                    }
                }

                if($writeError !== ''){
                    echo 'エラー: ' . htmlspecialchars($writeError, ENT_QUOTES, 'UTF-8') . '<br>';
                    echo '<a class="button" href="resist_logined_table.php?reset=1">編集画面へ戻る</a>';
                }

                else if($isProf){
                    //Profはアカウントごと削除したわけではないので、管理者モードは維持したまま
                    //Prof_enter_table.phpが仕込んだ疑似学生ログイン状態だけを解除する
                    unset($_SESSION['Student_login_Success']);
                    unset($_SESSION['student_json_file']);
                    unset($_SESSION['student_index']);
                    $_SESSION['delete_prof_class_success'] = true;
                    header('Location: index.php');
                    exit();
                }

                else{
                    //削除済みのユーザーなのでログイン状態を破棄し、index.phpでポップアップを表示するためのフラグだけ残す
                    $_SESSION = array();
                    session_unset();
                    $_SESSION['delete_success'] = true;
                    header('Location: index.php');
                    exit();
                }
            }

            else{
                //確認画面
                echo '削除しますか？この操作は取り消せません。<br>';
                echo '<div class="delete-actions">';
                echo '<form method="post" action="delete_check.php">';
                echo '<input type="hidden" name="confirmDelete" value="1">';
                echo '<button type="submit">はい</button>';
                echo '</form>';
                echo '<a class="button" href="resist_logined_table.php?reset=1">いいえ</a>';
                echo '</div>';
            }
        }

    }

    else{
        echo 'アクセス失敗';
    }

    ?>


</body>
</html>
