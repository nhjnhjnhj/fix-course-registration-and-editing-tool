<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>授業登録ページ</title>
    </head>
    <body>
    
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

        session_start();

        if($_SESSION[$Student_login_Success] == true){

            //Back_login_Studentで定義したログインした学生の学年を参照
            $jsonFile = __DIR__ . '/json/' . $_SESSION['student_json_file'];
            $json = file_get_contents($jsonFile);
            $data_Student = json_decode($json, true);

            //Back_login_Studentで定義したログインした学生のファイルindexを参照
            $studentIndex = $_SESSION['student_index'];
            $studentData = $data_Student[$studentIndex];
        }

        echo '学年<br>';
        for($i = 0 ; $i < GRADE_COUNT ; $i++){
            echo '<label><input type="radio" name="check'.(1).'" value="'.(GRADE_NAME[$i]).' ">'.(GRADE_NAME[$i]).'</label>';
        }
        echo '<br>';

        echo '学期<br>';
        for($i = 0 ; $i < QUOTER_COUNT ; $i++){
            echo '<label><input type="radio" name="check'.(1).'" value="'.(QUOTER_NAME[$i]).' ">'.(QUOTER_NAME[$i]).'</label>';
        }
        echo '<br>';
        
        echo 'メールアドレス';
        echo '<input type = "text" name = email><br>';
        echo '氏名';
        echo '<input type = "text" name = name><br>';
        echo 'パスワード';
        echo '<input type = "password" name = password><br>';

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
                    if($_SESSION[$Student_login_Success] == true){
                        $classValue = $studentData['class']['Quarter1'][JSON_DAY_NAME[$j-1]][$i];
                        echo '<td><input type = "text" name="class[' . JSON_DAY_NAME[$j-1] . '][' . $i . ']" value=" '.htmlspecialchars($classValue, ENT_QUOTES, 'UTF-8'). '"><br></td>';
                    }

                    else{
                        echo '<td><input type = "text" name = class[class[JSON_DAY_NAME[$j-1]][$i]]><br></td>';
                    }
                }
            }

            echo '</tr>'; //1行分
        }
   
        echo '</table>';
        
        echo '<button onclick="location.href=\'resist_check.php\'">登録</button>';//リンクあり。ポップアップで処理できそう
        echo '<button onclick="location.href=\'delete_check.php\'">削除</button>';//リンクあり。ポップアップで処理できそう
        echo '<button type="reset">リセット</button>';

    }

    else{
        echo 'アクセス失敗';
    }
    

?>
</body>
</html>