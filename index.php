<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>トップページ</title>
    </head>
    <body>
    
    <?php
    $code = http_response_code(); //HTTPレスポンスコードを取得(404 Not Foundなど)
    //error_log($code); //コンソールに出力するためのタグ
    //var_dump(http_response_code()); //型も明示する関数


    if(http_response_code() == 200){
        //echo 'アクセス成功<br>';
    
        echo 'トップページ<br>';
        //チェックボックスの作成

        $Grade = array('B3','B4','M1','M2','Prof','ハイライト');
        for($i = 0 ; $i < 6 ; $i++){
            echo '<label><input type="checkbox" name="check'.(1).'" value="'.($Grade[$i]).' ">'.($Grade[$i]).'</label>';
        }

        echo '<br>';

        $Grade = array('1Q','2Q','3Q','4Q');
        for($i = 0 ; $i < 4 ; $i++){
            echo '<label><input type="radio" name="check'.(1).'" value="'.($Grade[$i]).' ">'.($Grade[$i]).'</label>';
        }

        echo '<br>';
        
        echo '<table border="1" width="800" cellpadding="10">'; //表の枠の太さ、幅、セルの余白を指定

        
        $day = ['月', '火', '水', '木', '金', '土']; //曜日の配列
            
        for( $i = 0 ; $i < 6 ; $i++){ //0を科目の行、1~5を授業の限とする

            echo '<tr>';

            if($i == 0){

                for( $j = 0 ; $j < 7 ; $j++){ //月～土

                    if($j == 0){ //[0,0]は空白。元システムでは鉛筆のアイコンから編集が出来る。
                        echo '<th></th>';
                        continue;
                    }

                    echo '<th>' . $day[$j-1] . '</th>'; //[0,$j]で曜日を出力

                }

            }

            else{

                 for( $j = 0 ; $j < 7 ; $j++){ //月～土

                    if($j == 0){
                        echo '<td>' .$i . '</td>'; //[&i,0]で限を出力
                        continue;
                    }
                   echo '<td></td>'; //[&i,&j]は空白。 

                }
            }

            echo '</tr>'; //1行分
        }
   
        echo '</table>';
        
        echo '<button onclick="location.href=\'login.php\'">ログイン・登録</button>';

    }

    else{
        echo 'アクセス失敗';
    }
    
    ?>
    


</body>
</html>