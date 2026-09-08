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
        <!-- 登録系画面で共通のデザインを使用 -->
        <link rel="stylesheet" href="assets/css/common.css">
        <link rel="stylesheet" href="assets/css/resist.css">
    </head>
    <body class="resist-page">
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
    const QUARTER_COUNT = 4; //クオーター数
    const GRADE_COUNT = 4; //学年の数
    $dayNames = array('月','火','水','木','金','土'); //曜日の配列
    $gradeNames = array('B3','B4','M1','M2'); //学年の配列
    $quarterNames = array('1Q','2Q','3Q','4Q'); //クオーターの配列
    $jsonDayNames = array('Mon','Tue','Wed','Thu','Fri','Sat'); //jsonファイルの曜日要素の配列

    // JavaScriptを使わずに配置できる授業外予定
    $scheduleEventTypes = array('バイト', 'SA', 'TA');
    $selectedGrade = isset($_POST['grade']) && in_array($_POST['grade'], $gradeNames, true) ? $_POST['grade'] : 'B3';
    $selectedQuarter = isset($_POST['quarter']) && in_array($_POST['quarter'], $quarterNames, true) ? $_POST['quarter'] : '1Q';
    $returnedQuarterData = json_decode(isset($_POST['classAllQuarters']) ? $_POST['classAllQuarters'] : '', true);
    $returnedQuarterData = is_array($returnedQuarterData) ? $returnedQuarterData : array();
    if(isset($_POST['applyScheduleEvent'])){
        $eventType = isset($_POST['scheduleEventType']) ? $_POST['scheduleEventType'] : '';
        if($eventType === 'custom'){
            $eventType = trim(isset($_POST['customScheduleEvent']) ? $_POST['customScheduleEvent'] : '');
            $eventType = mb_substr($eventType, 0, 30, 'UTF-8');
        }
        else if(!in_array($eventType, $scheduleEventTypes, true)){
            $eventType = '';
        }
        $eventDays = isset($_POST['scheduleEventDays']) && is_array($_POST['scheduleEventDays']) ? $_POST['scheduleEventDays'] : array();
        $eventPeriods = isset($_POST['scheduleEventPeriods']) && is_array($_POST['scheduleEventPeriods']) ? $_POST['scheduleEventPeriods'] : array();

        if($eventType !== ''){
            foreach($eventDays as $eventDay){
                if(!in_array($eventDay, $jsonDayNames, true)){
                    continue;
                }
                foreach($eventPeriods as $eventPeriod){
                    $eventPeriod = (int)$eventPeriod;
                    if($eventPeriod >= 1 && $eventPeriod <= 5){
                        $_POST['class'][$eventDay][$eventPeriod] = $eventType;
                    }
                }
            }
        }
    }

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
            $gradeChecked = $gradeNames[$i] == $selectedGrade ? ' checked' : '';
            echo '<label><input type="radio" name="grade" value="'.$gradeNames[$i].'"'.$gradeChecked.'>'.$gradeNames[$i].'</label>';
        }
        echo '<br>';

        echo '学期<br>';
        for($i = 0 ; $i < QUARTER_COUNT ; $i++){

            $quarterChecked = $quarterNames[$i] == $selectedQuarter ? ' checked' : '';
            echo '<label><input type="radio" name="quarter" value="'.$quarterNames[$i].'" onchange="switchQuarter(this.value)"'.$quarterChecked.'>'.$quarterNames[$i].'</label>'; //onchangeで学期切り替え処理を呼ぶ

        }
        echo '<br>';
        echo '<input type="hidden" name="classAllQuarters" id="classAllQuarters">'; //4学期分の時間割データ(JSON)を送信時にJSで詰め込むためのhiddenフィールド

        //予定配置後はPOST値を優先し、それ以外はログイン済みの情報を初期値にする。
        $emailValue = isset($_POST['email'])
            ? $_POST['email']
            : ((isset($_SESSION['Student_login_Success']) && $_SESSION['Student_login_Success'] == true) ? $studentData['email'] : '');
        echo 'メールアドレス';
        echo '<input type="text" name="email" value="'.htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8').'"><br>';

        $nameValue = isset($_POST['name'])
            ? $_POST['name']
            : ((isset($_SESSION['Student_login_Success']) && $_SESSION['Student_login_Success'] == true) ? $studentData['name'] : '');
        echo '氏名';
        echo '<input type="text" name="name" value="'.htmlspecialchars($nameValue, ENT_QUOTES, 'UTF-8').'"><br>';

        $passValue = isset($_POST['password'])
            ? $_POST['password']
            : ((isset($_SESSION['Student_login_Success']) && $_SESSION['Student_login_Success'] == true) ? $studentData['password'] : '');
        echo 'パスワード';
        echo '<input type="password" name="password" value="'.htmlspecialchars($passValue, ENT_QUOTES, 'UTF-8').'"><br>';

        // 予定ピースを選択し、曜日・時限を指定してPHPへ送信する
        echo '<fieldset class="schedule-event-palette">';
        echo '<legend>授業以外の予定</legend>';
        echo '<div class="schedule-event-pieces">';
        foreach($scheduleEventTypes as $eventType){
            echo '<label class="schedule-event-piece"><input type="radio" name="scheduleEventType" value="'.htmlspecialchars($eventType, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($eventType, ENT_QUOTES, 'UTF-8').'</label>';
        }
        echo '<label class="schedule-event-piece custom-event-piece"><input type="radio" name="scheduleEventType" value="custom"><span>自由記述</span><input class="custom-event-input" type="text" name="customScheduleEvent" maxlength="30" placeholder="例：サークル、通院"></label>';
        echo '</div>';
        echo '<div class="schedule-event-target schedule-event-days"><strong>曜日</strong>';
        for($dayIndex = 0; $dayIndex < count($jsonDayNames); $dayIndex++){
            echo '<label><input type="checkbox" name="scheduleEventDays[]" value="'.$jsonDayNames[$dayIndex].'">'.$dayNames[$dayIndex].'</label>';
        }
        echo '</div>';
        echo '<div class="schedule-event-target"><strong>時限</strong>';
        for($period = 1; $period <= 5; $period++){
            echo '<label><input type="checkbox" name="scheduleEventPeriods[]" value="'.$period.'">'.$period.'限</label>';
        }
        echo '</div>';
        echo '<button type="submit" name="applyScheduleEvent" value="1" formaction="resist_new_table.php">予定を配置</button>';
        echo '<p>予定と複数の曜日・時限を選ぶと、すべての組み合わせへ一括配置できます。</p>';
        echo '</fieldset>';

        
        echo '<table border="1" width="800" cellpadding="10">'; //表の枠の太さ、幅、セルの余白を指定
            
        for( $i = 0 ; $i < TABLE_ROW_COUNT ; $i++){ //0を科目の行、1~5を授業の限とする

            echo '<tr>';

            if($i == 0){

                for( $j = 0 ; $j < TABLE_LINE_COUNT ; $j++){ //月～土

                    if($j == 0){ //[0,0]は空白。元システムでは鉛筆のアイコンから編集が出来る。
                        echo '<th></th>';
                        continue;
                    }

                    echo '<th>' . $dayNames[$j-1] . '</th>'; //[0,$j]で曜日を出力

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
                    if(isset($_POST['class'][$jsonDayNames[$j-1]][$i])){
                        $classValue = $_POST['class'][$jsonDayNames[$j-1]][$i];
                        echo '<td><input type="text" id="cell_' . $jsonDayNames[$j-1] . '_' . $i . '" name="class[' . $jsonDayNames[$j-1] . '][' . $i . ']" value="'.htmlspecialchars($classValue, ENT_QUOTES, 'UTF-8').'"></td>';
                    }
                    else if(isset($returnedQuarterData[$selectedQuarter][$jsonDayNames[$j-1]][$i])){
                        $classValue = $returnedQuarterData[$selectedQuarter][$jsonDayNames[$j-1]][$i];
                        echo '<td><input type="text" id="cell_' . $jsonDayNames[$j-1] . '_' . $i . '" name="class[' . $jsonDayNames[$j-1] . '][' . $i . ']" value="'.htmlspecialchars($classValue, ENT_QUOTES, 'UTF-8').'"></td>';
                    }
                    else if(isset($_SESSION['Student_login_Success']) && $_SESSION['Student_login_Success'] == true){
                        $classValue = $studentData['class']['Quarter1'][$jsonDayNames[$j-1]][$i];
                        echo '<td><input type="text" id="cell_' . $jsonDayNames[$j-1] . '_' . $i . '" name="class[' . $jsonDayNames[$j-1] . '][' . $i . ']" value="'.htmlspecialchars($classValue, ENT_QUOTES, 'UTF-8').'"></td>';
                    }

                    else{
                        echo '<td><input type="text" id="cell_' . $jsonDayNames[$j-1] . '_' . $i . '" name="class[' . $jsonDayNames[$j-1] . '][' . $i . ']"></td>';
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
        echo '<a class="button reset-button" href="resist_new_table.php">リセット</a>';
        echo '</form>';

        echo '<a class="button reset-button" href="index.php">トップページへ戻る</a>';

        //クオーターを切り替えても入力内容を保持し、送信時は全クオーター分をまとめて送るためのJS
        //→ JSのquarterDataの初期値として、学期ごとの時間割データをPHP側で組み立てておく
        $quarterLabelMap = array('1Q' => 'Quarter1', '2Q' => 'Quarter2', '3Q' => 'Quarter3', '4Q' => 'Quarter4');
        $initialQuarterData = $returnedQuarterData;
        foreach($quarterLabelMap as $label => $qKey){
            if(isset($initialQuarterData[$label])){
                continue;
            }
            if(isset($_SESSION['Student_login_Success']) && $_SESSION['Student_login_Success'] == true){
                $initialQuarterData[$label] = $studentData['class'][$qKey]; //ログイン済みなら既存の登録内容を初期値にする
            }
            else{
                //未ログインは全学期分を空欄で初期化
                $emptyDay = array();
                foreach($jsonDayNames as $day){
                    $emptyDay[$day] = array('1'=>'', '2'=>'', '3'=>'', '4'=>'', '5'=>'');
                }
                $initialQuarterData[$label] = $emptyDay;
            }
        }
        if(isset($_POST['class']) && is_array($_POST['class'])){
            $initialQuarterData[$selectedQuarter] = $_POST['class'];
        }

        echo '<script>';
        echo 'var quarterData = ' . json_encode($initialQuarterData) . ';'; //学期(1Q~4Q)ごとの時間割データを保持するオブジェクト
        echo 'var currentQuarter = ' . json_encode($selectedQuarter) . ';'; //現在フォーム上に表示している学期
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
