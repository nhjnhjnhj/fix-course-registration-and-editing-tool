<?php
    session_start();
    require_once __DIR__ . '/student_data_lock.php';

    //ブラウザの「戻る」操作でキャッシュ(bfcache)から古い画面が復元されるのを防ぐ(HTML出力より前に呼ぶ必要がある)
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>全データ削除確認ページ</title>
    </head>
    <body>
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
    const STUDENT_JSON_FILES = array('B3.json', 'B4.json', 'M1.json', 'M2.json'); //全学生分のjsonファイル
    const JSON_DAY_NAME = array('Mon','Tue','Wed','Thu','Fri','Sat'); //jsonファイルの曜日要素の配列
    const QUOTER_KEY = array('Quarter1', 'Quarter2', 'Quarter3', 'Quarter4'); //jsonファイルの学期キー
    const PERIOD_COUNT = 5; //1日あたりの限数

    if($code == HTTP_OK){

        if(!isset($_SESSION['Prof_loginSuccess']) || $_SESSION['Prof_loginSuccess'] != true){ //管理者モードでなければ削除不可
            echo 'アクセス失敗(管理者モードでログインしていません)';
        }

        else{

            //パスワード再確認についてのポップアップ(Back_login_Prof.phpでの照合結果)
            if(isset($_SESSION['incollect']) && $_SESSION['incollect'] == true){
                echo '<script>alert("パスワードが違います");</script>';
                unset($_SESSION['incollect']);
            }
            if(isset($_SESSION['empty']) && $_SESSION['empty'] == true){
                echo '<script>alert("パスワードが未入力です");</script>';
                unset($_SESSION['empty']);
            }

            if(isset($_SESSION['deleteAllAuthorized']) && $_SESSION['deleteAllAuthorized'] == true){
                //パスワード確認が取れているので、全学生・先生の授業データを削除する
                unset($_SESSION['deleteAllAuthorized']); //使い切りのフラグなので即座に破棄(再実行防止)
                $writeError = '';
                $dataLockHandle = studentDataAcquireLock(__DIR__, true, false);
                if($dataLockHandle === false){
                    $writeError = '学生データの更新ロックを取得できませんでした。';
                }

                //学生用jsonファイルはgrade/name/email/password/classをまとめて削除(配列を空にする)
                if($writeError === ''){
                    foreach(STUDENT_JSON_FILES as $file){
                        $jsonFile = __DIR__ . '/json/' . $file;

                        if(file_put_contents($jsonFile, json_encode(array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false){
                            $writeError = 'jsonファイルへの書き込みに失敗しました: ' . $jsonFile . '(書き込み権限を確認してください)';
                            break;
                        }
                    }
                }

                //先生用jsonファイルはgrade/name/email/passwordは残し、classの中身(各コマ)だけ空文字にリセット
                if($writeError === ''){
                    $profJsonFile = __DIR__ . '/json/Prof.json';
                    $json = file_get_contents($profJsonFile);

                    if($json === false){
                        $writeError = 'jsonファイルの読み込みに失敗しました: ' . $profJsonFile;
                    }

                    else{
                        $data_Prof = json_decode($json, true);

                        if($data_Prof === null){
                            $writeError = 'jsonファイルの解析に失敗しました: ' . $profJsonFile . '(json_last_error: ' . json_last_error_msg() . ')';
                        }

                        else{
                            foreach($data_Prof as &$record){
                                $emptyClass = array();
                                foreach(QUOTER_KEY as $qKey){
                                    $emptyClass[$qKey] = array();
                                    foreach(JSON_DAY_NAME as $day){
                                        $emptyClass[$qKey][$day] = array();
                                        for($p = 1 ; $p <= PERIOD_COUNT ; $p++){
                                            $emptyClass[$qKey][$day][(string)$p] = '';
                                        }
                                    }
                                }
                                $record['class'] = $emptyClass;
                            }
                            unset($record);

                            if(file_put_contents($profJsonFile, json_encode($data_Prof, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false){
                                $writeError = 'jsonファイルへの書き込みに失敗しました: ' . $profJsonFile . '(書き込み権限を確認してください)';
                            }
                        }
                    }
                }

                studentDataReleaseLock($dataLockHandle);

                if($writeError !== ''){
                    echo 'エラー: ' . htmlspecialchars($writeError, ENT_QUOTES, 'UTF-8') . '<br>';
                    echo '<button type="button" onclick="location.href=\'index.php\'">トップページへ</button>';
                }

                else{
                    $_SESSION['delete_all_success'] = true; //index.phpでポップアップを表示するためのフラグ
                    header('Location: index.php');
                    exit();
                }
            }

            else{
                //確認画面：警告文・パスワード再入力フォーム・削除ボタン・戻るボタンを1画面にまとめて表示
                echo '本当に削除しますか？(この操作は取り消せません)。<br>';
                echo '全ての学生の情報(学年・氏名・メールアドレス・パスワード・時間割)と、先生の時間割が削除されます。<br>';
                echo '削除する場合には、再度(管理者)パスワードを入力してください。<br>';

                echo '<form method="post" action="Back_login_Prof.php">';
                echo '<input type="hidden" name="purpose" value="deleteAll">'; //Back_login_Prof.phpが全削除フローと判別するための値
                echo '(管理者)パスワード<input type="password" name="password"><br>';
                echo '<button type="submit">削除</button>';
                echo '</form>';

                echo '<button type="button" onclick="location.href=\'index.php\'">戻る</button>';
            }
        }

    }

    else{
        echo 'アクセス失敗';
    }

    ?>


</body>
</html>
