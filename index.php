<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>トップページ</title>
    </head>
    <body>
    
    <?php
    $code = http_response_code(); //HTTPレスポンスコードを取得(404 Not Foundなど)
    const HTTP_OK = 200; //レスポンスコード200 = アクセス許可
    const TABLE_ROW_COUNT = 6; //時間割表の行数
    const TABLE_LINE_COUNT = 7; //時間割表の列数
    const QUOTER_COUNT = 4; //クオーター数
    const GRADE_COUNT = 6;  //学年の数
    const GRADE_NAME = array('B3','B4','M1','M2','Prof','ハイライト'); //曜日の配列
    const QUOTER_NAME = array('1Q','2Q','3Q','4Q'); //学年の配列
    const DAY_NAME = array('月','火','水','木','金','土'); //クオーターの配列

    if($code == HTTP_OK){
        session_start();
        
        echo 'トップページ<br>';

        /*login_Profを経由してログインした場合(login_Profでのフラグが設定されている場合)のみボタンを表示する予定*/
        if(isset($_SESSION['prof_login_Success']) && $_SESSION['prof_login_Success'] === true){
            echo '管理者モードでログインしています。<br>';
        } 
        else {
            echo '管理者モードでログインしていません。<br>';
        }

        //チェックボックスの作成
        for($i = 0 ; $i < GRADE_COUNT ; $i++){
            echo '<label><input type="checkbox" name="check'.(1).'" value="'.(GRADE_NAME[$i]).' ">'.(GRADE_NAME[$i]).'</label>';
        }
        echo '<br>';
        for($i = 0 ; $i < QUOTER_COUNT ; $i++){
            echo '<label><input type="radio" name="check'.(1).'" value="'.(QUOTER_NAME[$i]).' ">'.(QUOTER_NAME[$i]).'</label>';
        }
        echo '<br>';
        


        echo '<table border="1" width="800" cellpadding="10">'; //表の枠の太さ、幅、セルの余白を指定
            
        for( $i = 0 ; $i < TABLE_ROW_COUNT ; $i++){ //0を科目の行、1~5を授業の限とする

            echo '<tr>';

            if($i == 0){ //0行目は曜日の行

                for( $j = 0 ; $j < TABLE_LINE_COUNT ; $j++){ //月～土

                    if($j == 0){ //[0,0]は空白。元システムでは鉛筆のアイコンから編集が出来る。
                        echo '<th></th>';
                        continue;
                    }

                    echo '<th>' . DAY_NAME[$j-1] . '</th>'; //[0,$j]で曜日を出力

                }

            }

            else{

                 for( $j = 0 ; $j < TABLE_LINE_COUNT ; $j++){ //月～土

                    if($j == 0){ //[&i,0]は限を出力
                        echo '<td>' .$i . '</td>'; //[&i,0]で限を出力
                        continue;
                    }
                   echo '<td></td>'; //[&i,&j]は空白。 

                }
            }
            echo '</tr>'; //1行分
        }

        //管理者モードに変更するためのフォーム

        echo '</table>'; 
        echo '<button onclick="location.href=\'login.php\'">ログイン・登録</button><br>';

        echo '(管理者)パスワード<br>';
        echo '<form method="post" action="Back_login_Prof.php">';  //Back_login_Profに遷移
        echo '<input type = "password" name = password><br>'; //管理者パスワードの入力フォーム
        echo '<button type="submit">ログイン</button><br>';
        echo '</form>';

    }

    else{
        echo 'アクセス失敗';
    }
    
    ?>
    


</body>
</html>