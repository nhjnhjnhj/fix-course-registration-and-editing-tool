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
    const GRADE_COUNT = 5;  //学年の数
    const HIGHLIGHT = 'ハイライト'; //ハイライト表示のための特殊なチェックボックス
    const GRADE_NAME = array('B3','B4','M1','M2','Prof'); //曜日の配列
    const QUOTER_NAME = array('1Q','2Q','3Q','4Q'); //学年の配列
    const DAY_NAME = array('月','火','水','木','金','土'); //クオーターの配列
    const JSON_DAY_NAME = array('Mon','Tue','Wed','Thu','Fri','Sat'); //jsonファイルの曜日要素の配列
    const GRADE_JSON_FILE = array('B3' => 'B3.json', 'B4' => 'B4.json', 'M1' => 'M1.json', 'M2' => 'M2.json', 'Prof' => 'Prof.json'); //学年とjsonファイル名の対応

    if($code == HTTP_OK){
        session_start();

        //登録、削除についてのポップアップ
        if(isset($_SESSION['resist_success']) && $_SESSION['resist_success'] == true){ //登録完了ポップアップ(仮実装。後ほど作成予定)
            echo '<script>alert("登録が完了しました");</script>';
            unset($_SESSION['resist_success']);
        }
        if(isset($_SESSION['delete_success']) && $_SESSION['delete_success'] == true){ //削除完了ポップアップ(仮実装。後ほど作成予定)
            echo '<script>alert("削除が完了しました");</script>';
            unset($_SESSION['delete_success']);
        }
        if(isset($_SESSION['delete_all_success']) && $_SESSION['delete_all_success'] == true){ //全学生・先生データ削除完了ポップアップ
            echo '<script>alert("全ての学生・先生のデータを削除しました");</script>';
            unset($_SESSION['delete_all_success']);
        }
        if(isset($_SESSION['delete_prof_class_success']) && $_SESSION['delete_prof_class_success'] == true){ //Prof時間割クリア完了ポップアップ
            echo '<script>alert("先生の時間割をクリアしました");</script>';
            unset($_SESSION['delete_prof_class_success']);
        }

        //管理者ログインについてのポップアップ
        if(isset($_SESSION['incollect']) && $_SESSION['incollect'] == true){ //管理者モードでのログインに失敗した場合、セッション変数incollectがtrueに設定されるので、アラートを表示する。
            echo '<script>alert("パスワードが違います");</script>';
            unset($_SESSION['incollect']); //セッション破棄
        }
        if(isset($_SESSION['empty']) && $_SESSION['empty'] == true){ //管理者モードでのログインに失敗した場合、セッション変数incollectがtrueに設定されるので、アラートを表示する。
            echo '<script>alert("パスワードが未入力です");</script>';
            unset($_SESSION['empty']); //セッション破棄
        }

        echo 'トップページ<br>';

        /*login_Profを経由してログインした場合(login_Profでのフラグが設定されている場合)のみボタンを表示する予定*/
        if(isset($_SESSION['Prof_loginSuccess']) && $_SESSION['Prof_loginSuccess'] == true){
            echo '管理者モードでログインしています。<br>';

        }
        else {
            echo '管理者モードでログインしていません。<br>';
        }

        //選択されている学年(チェックボックス)・学期(ラジオボタン)・表示モードをURLパラメータから取得(未選択時のデフォルトは学期1Q、summaryモード)
        $selectedGrades = (isset($_GET['grades']) && is_array($_GET['grades'])) ? $_GET['grades'] : array();
        $selectedQuarter = isset($_GET['quarter']) ? $_GET['quarter'] : '1Q';
        $viewMode = (isset($_GET['viewMode']) && $_GET['viewMode'] === 'detail') ? 'detail' : 'summary';

        //「ハイライト」は学年データを持たない特殊なチェックボックスなので、他の学年選択から分離して扱う
        $realSelectedGrades = array_diff($selectedGrades, array('ハイライト'));
        $highlightEnabled = in_array('ハイライト', $selectedGrades, true) && count($realSelectedGrades) > 0;

        //特定の学生のみを表示するプルダウン用に、選択中の学生名をURLパラメータから取得(未選択なら絞り込みなし)
        $selectedStudentName = (isset($_GET['studentName']) && $_GET['studentName'] !== '') ? $_GET['studentName'] : null;

        //チェックが付いている学年に対応するJSONファイル・学年の一覧を作成(プルダウン・表本体の集計の両方で使う)
        $gradesByFile = array();
        foreach($selectedGrades as $grade){
            if(isset(GRADE_JSON_FILE[$grade])){
                $gradesByFile[GRADE_JSON_FILE[$grade]][] = $grade;
            }
        }

        //各ファイルの内容を先読みしておく(プルダウンの氏名収集と表本体の集計で使い回す)
        $recordsByFile = array();
        foreach($gradesByFile as $file => $grades){
            $json = file_get_contents(__DIR__ . '/json/' . $file);
            $records = ($json !== false) ? json_decode($json, true) : null;
            $recordsByFile[$file] = is_array($records) ? $records : array();
        }

        echo '<form method="get" action="index.php">';

        //チェックボックスの作成(変更時にフォームを自動送信して表示を更新する)
        for($i = 0 ; $i < GRADE_COUNT ; $i++){
            $checkedAttr = in_array(GRADE_NAME[$i], $selectedGrades, true) ? ' checked' : '';
            echo '<label><input type="checkbox" name="grades[]" value="'.(GRADE_NAME[$i]).'" onchange="this.form.submit()"'.$checkedAttr.'>'.(GRADE_NAME[$i]).'</label>';
        }

        //「ハイライト」チェックボックス(学年データを持たない特殊なチェックボックス)
        $highlightCheckedAttr = in_array(HIGHLIGHT, $selectedGrades, true) ? ' checked' : '';
        echo '<label><input type="checkbox" name="grades[]" value="'.HIGHLIGHT.'" onchange="this.form.submit()"'.$highlightCheckedAttr.'>'.HIGHLIGHT.'</label>';
        echo '<br>';

        //特定の学生のみを表示するプルダウン(チェックが付いている学年に対応するファイル・学年のレコードからのみ氏名を集める)
        $studentNames = array();
        foreach($gradesByFile as $file => $grades){
            foreach($recordsByFile[$file] as $record){
                if(!in_array($record['grade'] ?? '', $grades, true)){
                    continue; //チェックされていない学年のレコードは無視
                }
                if(isset($record['name']) && $record['name'] !== ''){
                    $studentNames[] = $record['name'];
                }
            }
        }
        $studentNames = array_values(array_unique($studentNames));
        sort($studentNames);

        echo '<label>表示する学生<select name="studentName" onchange="this.form.submit()">';
        echo '<option value=""' . ($selectedStudentName === null ? ' selected' : '') . '>(指定なし)</option>';
        foreach($studentNames as $name){
            $selectedAttr = ($selectedStudentName === $name) ? ' selected' : '';
            echo '<option value="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'"'.$selectedAttr.'>'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'</option>';
        }
        echo '</select></label>';
        echo '<br>';

        for($i = 0 ; $i < QUOTER_COUNT ; $i++){
            $checkedAttr = (QUOTER_NAME[$i] == $selectedQuarter) ? ' checked' : '';
            echo '<label><input type="radio" name="quarter" value="'.(QUOTER_NAME[$i]).'" onchange="this.form.submit()"'.$checkedAttr.'>'.(QUOTER_NAME[$i]).'</label>';
        }
        echo '<br>';

        //表示モードの切り替え(summary: 学年のみ+ツールチップ / detail: 学年・名前・授業名を常時表示)
        $summaryCheckedAttr = ($viewMode == 'summary') ? ' checked' : '';
        $detailCheckedAttr = ($viewMode == 'detail') ? ' checked' : '';
        echo '<label><input type="radio" name="viewMode" value="summary" onchange="this.form.submit()"'.$summaryCheckedAttr.'>summaryモード</label>';
        echo '<label><input type="radio" name="viewMode" value="detail" onchange="this.form.submit()"'.$detailCheckedAttr.'>詳細表示モード</label>';
        echo '<br>';
        echo '</form>';

        //選択された学年・学期に登録されている授業を曜日・限ごとに集計する
        $quarterKey = 'Quarter' . mb_substr($selectedQuarter, 0, 1);
        $cellClasses = array();
        foreach(JSON_DAY_NAME as $day){
            $cellClasses[$day] = array();
            for($p = 1 ; $p <= 5 ; $p++){
                $cellClasses[$day][$p] = array();
            }
        }

        foreach($gradesByFile as $file => $grades){
            $records = $recordsByFile[$file];

            if(is_array($records)){
                foreach($records as $record){
                    if(!in_array($record['grade'] ?? '', $grades, true)){
                        continue; //選択された学年と一致しないレコードは無視
                    }

                    //プルダウンで学生が指定されている場合、その学生以外のレコードは無視(学年フィルタとは併用可)
                    if($selectedStudentName !== null && ($record['name'] ?? '') !== $selectedStudentName){
                        continue;
                    }

                    foreach(JSON_DAY_NAME as $day){
                        for($p = 1 ; $p <= 5 ; $p++){
                            $value = trim($record['class'][$quarterKey][$day][(string)$p] ?? '');
                            if($value !== ''){
                                //セルには学年の文字列だけを表示し、氏名・授業名はカーソルを合わせた時のツールチップ(title属性)で見せる
                                $cellClasses[$day][$p][] = array(
                                    'grade' => $record['grade'] ?? '',
                                    'name' => $record['name'] ?? '',
                                    'class' => $value
                                );
                            }
                        }
                    }
                }
            }
        }


        echo '<table border="1" width="1600" cellpadding="10">'; //表の枠の太さ、幅、セルの余白を指定
            
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

                    //[&i,&j]には該当する曜日・限に登録されている授業を一覧表示(複数あれば区切って並べる)
                    //summaryモード: 学年のみ表示し、カーソルを合わせると氏名・授業名をツールチップ表示
                    //詳細表示モード: 学年・名前・授業名を常時表示(ツールチップなし)
                    $day = JSON_DAY_NAME[$j-1];

                    //ハイライト: 選択中の学年の学生が誰もこの曜日・限に授業を登録していない(空)場合、bgcolor属性で背景を黄色にする(CSSは使わない)
                    $isEmptySlot = empty($cellClasses[$day][$i]);
                    $tdAttr = ($highlightEnabled && $isEmptySlot) ? ' bgcolor="yellow"' : '';

                    if($viewMode == 'detail'){
                        $entries = array_map(function($e){
                            return '学年: '.htmlspecialchars($e['grade'], ENT_QUOTES, 'UTF-8')
                                .' 名前: '.htmlspecialchars($e['name'], ENT_QUOTES, 'UTF-8')
                                .' 授業: '.htmlspecialchars($e['class'], ENT_QUOTES, 'UTF-8');
                        }, $cellClasses[$day][$i]);
                        echo '<td'.$tdAttr.'>' . implode('<hr>', $entries) . '</td>';
                    }
                    else{
                        $entries = array_map(function($e){
                            $title = '氏名: ' . $e['name'] . "\n" . '授業: ' . $e['class'];
                            return '<span title="'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($e['grade'], ENT_QUOTES, 'UTF-8').'</span>';
                        }, $cellClasses[$day][$i]);
                        echo '<td'.$tdAttr.'>' . implode('<br>', $entries) . '</td>';
                    }

                }
            }
            echo '</tr>'; //1行分
        }

        //管理者モードに変更するためのフォーム

        echo '</table>';

        if(!isset($_SESSION['Prof_loginSuccess']) || $_SESSION['Prof_loginSuccess'] != true){ //管理者モードの時は生徒用の「ログイン・登録」ボタンは表示しない
            echo '<button onclick="location.href=\'login.php\'">ログイン</button><br>';
            echo '<button onclick="location.href=\'resist_new_table.php\'">新規登録</button><br>'; //授業登録ページへ遷移。新規登録の場合は、resist_tableでメアド・パスワード欄を表示する。
        }

        if(!isset($_SESSION['Prof_loginSuccess']) || $_SESSION['Prof_loginSuccess'] != true){ //ログインに失敗してPof_loginSuccessがfalseで確定した場合でもフォームを再表示できるようにする
            echo '(管理者)パスワード<br>';
            echo '<form method="post" action="Back_login_Prof.php">';  //Back_login_Profに遷移
            echo '<input type = "password" name = password><br>'; //管理者パスワードの入力フォーム
            echo '<button type="submit">ログイン</button><br>';
            echo '</form>';
        }

        if(isset($_SESSION['Prof_loginSuccess']) && $_SESSION['Prof_loginSuccess'] == true){
            echo '<button onclick="location.href=\'Prof_enter_table.php\'">Profの時間割を編集</button><br>'; //管理者モードならProfとして再ログインなしで授業登録ページへ
            echo '<button onclick="location.href=\'delete_all.php\'">全ての学生・先生の授業データを削除</button><br>'; //管理者モード限定。delete_all.phpで再度パスワード確認の上、全データを削除する。
             echo '<button onclick="location.href=\'logout.php\'">ログアウト</button><br>';
        }

    }

    else{
        echo 'アクセス失敗';
    }
    
    ?>
    


</body>
</html>