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
                        $writeError = 'jsonファイルの解析に失敗しました: ' . $jsonFile . '(json_last_error: ' . json_last_error_msg() . ')';
                    }

                    else{
                        $studentIndex = $_SESSION['student_index'];
                        array_splice($data, $studentIndex, 1); //該当ユーザーを削除し、以降のindexを詰める

                        if(file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false){
                            $writeError = 'jsonファイルへの書き込みに失敗しました: ' . $jsonFile . '(書き込み権限を確認してください)';
                        }
                    }
                }

                if($writeError !== ''){
                    echo 'エラー: ' . htmlspecialchars($writeError, ENT_QUOTES, 'UTF-8') . '<br>';
                    echo '<button type="button" onclick="history.back()">戻る</button>';
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
                echo '<form method="post" action="delete_check.php">';
                echo '<input type="hidden" name="confirmDelete" value="1">';
                echo '<button type="submit">はい</button>';
                echo '</form>';
                echo '<button type="button" onclick="history.back()">いいえ</button>';
            }
        }

    }

    else{
        echo 'アクセス失敗';
    }

    ?>


</body>
</html>
