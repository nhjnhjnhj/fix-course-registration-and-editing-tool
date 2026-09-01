<?php
    session_start();

    //ブラウザの「戻る」操作でキャッシュ(bfcache)から古いログイン状態の画面が復元されるのを防ぐ(HTML出力より前に呼ぶ必要がある)
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>授業登録ページ</title>
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
    const TABLE_ROW_COUNT = 6; //時間割表の行数
    const TABLE_LINE_COUNT = 7; //時間割表の列数
    const QUOTER_COUNT = 4; //クオーター数
    const GRADE_COUNT = 4;  //学年の数
    const DAY_NAME = array('月','火','水','木','金','土'); //曜日の配列
    const GRADE_NAME = array('B3','B4','M1','M2'); //学年の配列
    const QUOTER_NAME = array('1Q','2Q','3Q','4Q'); //クオーターの配列
    const JSON_DAY_NAME = array('Mon','Tue','Wed','Thu','Fri','Sat'); //jsonファイルの曜日要素の配列
    

    if($code == HTTP_OK){

        if(isset($_SESSION['Student_login_Success']) && $_SESSION['Student_login_Success'] == true){
            //Back_login_Studentで定義したログインした学生の学年を参照
            $jsonFile = __DIR__ . '/json/' . $_SESSION['student_json_file'];
            $json = file_get_contents($jsonFile);
            $data_Student = json_decode($json, true);

            //Back_login_Studentで定義したログインした学生のファイルindexを参照
            $studentIndex = $_SESSION['student_index'];
            $studentData = $data_Student[$studentIndex];
        }


        echo '<form id="resistForm" method="post" action="resist_check.php">';
        echo '<input type="hidden" name="mode" value="new">';

        echo '学年<br>';
        for($i = 0 ; $i < GRADE_COUNT ; $i++){

            if($i == 0){
                echo '<label><input type="radio" name="grade" value="'.(GRADE_NAME[$i]).'" checked>'.(GRADE_NAME[$i]).'</label>'; //デフォルトで最初の値が選択されている状態にする。
            }
            else{
                echo '<label><input type="radio" name="grade" value="'.(GRADE_NAME[$i]).'">'.(GRADE_NAME[$i]).'</label>';
            }

        }
        echo '<br>';

        echo '学期<br>';
        for($i = 0 ; $i < QUOTER_COUNT ; $i++){

            if($i == 0){
                echo '<label><input type="radio" name="quarter" value="'.(QUOTER_NAME[$i]).'" onchange="switchQuarter(this.value)" checked>'.(QUOTER_NAME[$i]).'</label>'; //デフォルトで最初の値が選択されている状態にする。onchangeで学期切り替え処理を呼ぶ
            }
            else{
                echo '<label><input type="radio" name="quarter" value="'.(QUOTER_NAME[$i]).'" onchange="switchQuarter(this.value)">'.(QUOTER_NAME[$i]).'</label>'; //onchangeで学期切り替え処理を呼ぶ
            }

        }
        echo '<br>';
        echo '<input type="hidden" name="classAllQuarters" id="classAllQuarters">'; //4学期分の時間割データ(JSON)を送信時にJSで詰め込むためのhiddenフィールド

        //現状：ログインなら取得したキーを最初から入力。
        //ログイン済みなら表示しない
        echo 'メールアドレス';
        if(isset($_SESSION['Student_login_Success']) && $_SESSION['Student_login_Success'] == true){ //ログイン済み
            $emailValue = $studentData['email'];
            echo '<td><input type = "text" name = "email" value=" '.htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'). '"><br></td>';
        }
        else{
            echo '<input type = "text" name = "email"><br>';
        }
        
        //ログインの有無にかかわらず表示。大きな変更はなし。
        echo '氏名';
        if(isset($_SESSION['Student_login_Success']) && $_SESSION['Student_login_Success'] == true){ //ログイン済み
            $nameValue = $studentData['name'];
            echo '<td><input type = "text" name = "name" value=" '.htmlspecialchars($nameValue, ENT_QUOTES, 'UTF-8'). '"><br></td>';
        }
        else{
            echo '<input type = "text" name = "name"><br>';
        }

        //現状：ログインなら取得したキーを最初から入力。
        //ログイン済みなら表示しない
        echo 'パスワード';
        if(isset($_SESSION['Student_login_Success']) && $_SESSION['Student_login_Success'] == true){ //ログイン済み
            $passValue = $studentData['password'];
            echo '<td><input type = "password" name = "password" value=" '.htmlspecialchars($passValue, ENT_QUOTES, 'UTF-8'). '"><br></td>';
        }
        else{
            echo '<input type = "password" name = "password"><br>';
        }

        
        echo '<table border="1" width="800" cellpadding="10">'; //表の枠の太さ、幅、セルの余白を指定
            
        for( $i = 0 ; $i < TABLE_ROW_COUNT ; $i++){ //0を科目の行、1~5を授業の限とする

            echo '<tr>';

            if($i == 0){

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

                    if($j == 0){
                        echo '<td>' .$i . '</td>'; //[&i,0]で限を出力
                        continue;
                    }

                     //変数名：resist_class(仮置き)
                    //id="cell_曜日_限"はJSのswitchQuarterから各セルを参照するためのキー
                    if(isset($_SESSION['Student_login_Success']) && $_SESSION['Student_login_Success'] == true){
                        $classValue = $studentData['class']['Quarter1'][JSON_DAY_NAME[$j-1]][$i];
                        echo '<td><input type = "text" id="cell_' . JSON_DAY_NAME[$j-1] . '_' . $i . '" name="class[' . JSON_DAY_NAME[$j-1] . '][' . $i . ']" value=" '.htmlspecialchars($classValue, ENT_QUOTES, 'UTF-8'). '"><br></td>';
                    }

                    else{
                        echo '<td><input type = "text" id="cell_' . JSON_DAY_NAME[$j-1] . '_' . $i . '" name="class[' . JSON_DAY_NAME[$j-1] . '][' . $i . ']"><br></td>';
                    }
                }
            }

            echo '</tr>'; //1行分
        }
   
        echo '</table>';
        
        echo '<button type="submit">登録</button>';//resist_check.phpに入力内容をPOSTして確認画面へ
        if(isset($_SESSION['Student_login_Success']) && $_SESSION['Student_login_Success'] == true){ //ログイン済みの場合のみ削除ボタンを表示
            echo '<button type="button" onclick="location.href=\'delete_check.php\'">削除</button>';//リンクあり。ポップアップで処理できそう
        }
        echo '<button type="reset">リセット</button>';
        echo '</form>';

        //クオーターを切り替えても入力内容を保持し、送信時は全クオーター分をまとめて送るためのJS
        //→ JSのquarterDataの初期値として、学期ごとの時間割データをPHP側で組み立てておく
        $quarterLabelMap = array('1Q' => 'Quarter1', '2Q' => 'Quarter2', '3Q' => 'Quarter3', '4Q' => 'Quarter4');
        $initialQuarterData = array();
        foreach($quarterLabelMap as $label => $qKey){
            if(isset($_SESSION['Student_login_Success']) && $_SESSION['Student_login_Success'] == true){
                $initialQuarterData[$label] = $studentData['class'][$qKey]; //ログイン済みなら既存の登録内容を初期値にする
            }
            else{
                //未ログインは全学期分を空欄で初期化
                $emptyDay = array();
                foreach(JSON_DAY_NAME as $day){
                    $emptyDay[$day] = array('1'=>'', '2'=>'', '3'=>'', '4'=>'', '5'=>'');
                }
                $initialQuarterData[$label] = $emptyDay;
            }
        }

        echo '<script>';
        echo 'var quarterData = ' . json_encode($initialQuarterData) . ';'; //学期(1Q~4Q)ごとの時間割データを保持するオブジェクト
        echo 'var currentQuarter = "1Q";'; //現在フォーム上に表示している学期
        echo 'var DAYS = ["Mon","Tue","Wed","Thu","Fri","Sat"];';
        echo 'function readTableIntoData(q){ var data = {}; DAYS.forEach(function(day){ data[day] = {}; for (var p = 1; p <= 5; p++){ var el = document.getElementById("cell_" + day + "_" + p); data[day][p] = el ? el.value.trim() : ""; } }); quarterData[q] = data; }'; //表に今入力されている値をquarterData[q]へ退避
        echo 'function writeDataIntoTable(q){ var data = quarterData[q] || {}; DAYS.forEach(function(day){ for (var p = 1; p <= 5; p++){ var el = document.getElementById("cell_" + day + "_" + p); if (el) el.value = (data[day] && data[day][p]) ? data[day][p] : ""; } }); }'; //quarterData[q]の値を表に復元(未入力なら空欄)
        echo 'function switchQuarter(newQuarter){ readTableIntoData(currentQuarter); currentQuarter = newQuarter; writeDataIntoTable(currentQuarter); }'; //学期切り替え時に現在の入力を退避してから、切替先の学期の値を復元する
        echo 'document.getElementById("resistForm").addEventListener("submit", function(){ readTableIntoData(currentQuarter); document.getElementById("classAllQuarters").value = JSON.stringify(quarterData); });'; //送信直前に表示中の学期分も退避し、4学期分をまとめてhiddenフィールドへセット
        echo '</script>';

    }

    else{
        echo 'アクセス失敗';
    }
    

?>
</body>
</html>