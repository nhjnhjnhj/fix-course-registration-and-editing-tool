<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>登録確認ページ</title>
    </head>
    <body>

    <?php
    session_start();
    $code = http_response_code(); //HTTPレスポンスコードを取得(404 Not Foundなど)

    const HTTP_OK = 200; //レスポンスコード200 = アクセス許可
    const TABLE_ROW_COUNT = 6; //時間割表の行数
    const TABLE_LINE_COUNT = 7; //時間割表の列数
    const DAY_NAME = array('月','火','水','木','金','土'); //曜日の配列
    const JSON_DAY_NAME = array('Mon','Tue','Wed','Thu','Fri','Sat'); //jsonファイルの曜日要素の配列
    const PERIOD_COUNT = 5; //1日あたりの限数
    const QUOTER_KEY = array('1Q' => 'Quarter1', '2Q' => 'Quarter2', '3Q' => 'Quarter3', '4Q' => 'Quarter4'); //学期名とjsonファイルのキーの対応
    const GRADE_JSON_FILE = array('B3' => 'B3.json', 'B4' => 'B4.json', 'M1' => 'Master.json', 'M2' => 'Master.json'); //学年とjsonファイル名の対応

    if($code == HTTP_OK){

        if(!isset($_POST['mode'])){ //resist_new_table.php / resist_logined_table.phpを経由せずに直接アクセスした場合
            echo 'アクセス失敗';
        }

        else{
            $mode = $_POST['mode']; //'new' または 'logined'

            //classAllQuarters(JSON文字列、キーは1Q~4Q)を全学期分のクラスデータ(キーはQuarter1~4)に変換
            //resist_new_table.php / resist_logined_table.phpのJSで学期切り替え時に退避された4学期分のデータがここに入っている
            $allQuartersRaw = json_decode($_POST['classAllQuarters'] ?? '', true);
            $classDataAllQuarters = array();
            foreach(QUOTER_KEY as $label => $qKey){
                $classDataAllQuarters[$qKey] = array();
                foreach(JSON_DAY_NAME as $day){
                    $classDataAllQuarters[$qKey][$day] = array();
                    for($p = 1 ; $p <= PERIOD_COUNT ; $p++){
                        $value = $allQuartersRaw[$label][$day][$p] ?? ''; //未入力(存在しないキー)は空文字にする
                        $classDataAllQuarters[$qKey][$day][(string)$p] = trim($value);
                    }
                }
            }

            if(isset($_POST['final']) && $_POST['final'] == '1'){
                //「登録」が押されたのでjsonファイルへ書き込む
                $writeError = '';

                if($mode == 'new'){
                    $grade = trim($_POST['grade'] ?? '');
                    $jsonFile = __DIR__ . '/json/' . (GRADE_JSON_FILE[$grade] ?? 'B3.json');
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
                            //4学期分をまとめて新規レコードとして追加
                            $data[] = array(
                                'grade' => $grade,
                                'name' => trim($_POST['name'] ?? ''),
                                'email' => trim($_POST['email'] ?? ''),
                                'password' => trim($_POST['password'] ?? ''),
                                'class' => $classDataAllQuarters
                            );

                            if(file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false){
                                $writeError = 'jsonファイルへの書き込みに失敗しました: ' . $jsonFile . '(書き込み権限を確認してください)';
                            }
                        }
                    }
                }

                else{ //logined : ログイン済みの学生の登録内容を更新
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

                            $data[$studentIndex]['name'] = trim($_POST['name'] ?? '');
                            $data[$studentIndex]['class'] = $classDataAllQuarters; //4学期分をまるごと上書き更新

                            if(file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false){
                                $writeError = 'jsonファイルへの書き込みに失敗しました: ' . $jsonFile . '(書き込み権限を確認してください)';
                            }
                        }
                    }
                }

                if($writeError !== ''){
                    echo 'エラー: ' . htmlspecialchars($writeError, ENT_QUOTES, 'UTF-8') . '<br>';
                    echo '<button type="button" onclick="history.back()">戻る</button>';
                }

                else{
                    $_SESSION['resist_success'] = true; //index.phpでポップアップを表示するためのフラグ
                    header('Location: index.php');
                    exit();
                }
            }

            else{
                //確認画面：入力欄が固定された(変更不可)状態の時間割表を表示

                echo '登録内容の確認(全学期分を一括登録します)<br>';
                echo '<form method="post" action="resist_check.php">';
                echo '<input type="hidden" name="mode" value="'.htmlspecialchars($mode, ENT_QUOTES, 'UTF-8').'">';
                echo '<input type="hidden" name="grade" value="'.htmlspecialchars(trim($_POST['grade'] ?? ''), ENT_QUOTES, 'UTF-8').'">';
                echo '<input type="hidden" name="classAllQuarters" value="'.htmlspecialchars($_POST['classAllQuarters'] ?? '', ENT_QUOTES, 'UTF-8').'">';
                echo '<input type="hidden" name="final" value="1">';

                echo '学年: ' . htmlspecialchars(trim($_POST['grade'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';

                if($mode == 'new'){
                    echo 'メールアドレス<input type="text" name="email" value="'.htmlspecialchars(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8').'" readonly><br>';
                    echo '氏名<input type="text" name="name" value="'.htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8').'" readonly><br>';
                    echo 'パスワード<input type="password" name="password" value="'.htmlspecialchars(trim($_POST['password'] ?? ''), ENT_QUOTES, 'UTF-8').'" readonly><br>';
                }
                else{
                    echo '氏名<input type="text" name="name" value="'.htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8').'" readonly><br>';
                }

                //確認する学期をラジオボタンで切り替えられるようにする(送信内容には影響しない、表示切替用)
                echo '確認する学期<br>';
                foreach(QUOTER_KEY as $label => $qKey){
                    $checkedAttr = ($label == '1Q') ? ' checked' : '';
                    echo '<label><input type="radio" name="confirmQuarterView" value="'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'" onchange="showConfirmQuarter(this.value)"'.$checkedAttr.'>'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</label>';
                }
                echo '<br>';

                //学期ごとに時間割表を用意し(全て読み取り専用)、ラジオボタンで選ばれた学期のみ表示する。「入力欄が固定された時間割表」の確認画面部分
                foreach(QUOTER_KEY as $label => $qKey){
                    $displayStyle = ($label == '1Q') ? 'block' : 'none';
                    echo '<div id="confirmTable_' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '" style="display:' . $displayStyle . ';">';
                    echo '<table border="1" width="800" cellpadding="10">';

                    for($i = 0 ; $i < TABLE_ROW_COUNT ; $i++){

                        echo '<tr>';

                        if($i == 0){
                            for($j = 0 ; $j < TABLE_LINE_COUNT ; $j++){
                                if($j == 0){
                                    echo '<th></th>';
                                    continue;
                                }
                                echo '<th>' . DAY_NAME[$j-1] . '</th>';
                            }
                        }

                        else{
                            for($j = 0 ; $j < TABLE_LINE_COUNT ; $j++){
                                if($j == 0){
                                    echo '<td>' . $i . '</td>';
                                    continue;
                                }
                                $day = JSON_DAY_NAME[$j-1];
                                $val = $classDataAllQuarters[$qKey][$day][(string)$i];
                                echo '<td><input type="text" value="'.htmlspecialchars($val, ENT_QUOTES, 'UTF-8').'" readonly></td>';
                            }
                        }

                        echo '</tr>';
                    }

                    echo '</table>';
                    echo '</div>';
                }

                echo '<button type="submit">登録</button>';
                echo '</form>';
                echo '<button type="button" onclick="history.back()">キャンセル</button>';

                //ラジオボタンで選択された学期のdivだけを表示し、他は隠す
                echo '<script>';
                echo 'function showConfirmQuarter(q){ var labels = ["1Q","2Q","3Q","4Q"]; labels.forEach(function(l){ var el = document.getElementById("confirmTable_" + l); if (el) el.style.display = (l === q) ? "block" : "none"; }); }';
                echo '</script>';
            }
        }

    }

    else{
        echo 'アクセス失敗';
    }

    ?>


</body>
</html>
