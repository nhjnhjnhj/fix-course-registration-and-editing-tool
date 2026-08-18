<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>ログイン・新規登録ページ</title>
    </head>
    <body>
    
    <?php
    $code = http_response_code(); //HTTPレスポンスコードを取得(404 Not Foundなど)
    //error_log($code); //コンソールに出力するためのタグ
    //var_dump(http_response_code()); //型も明示する関数


    if(http_response_code() == 200){
        //echo 'アクセス成功<br>';
        
        //学籍番号、氏名、パスワードを入力するフォームを作成
        echo '学籍番号';
        echo '<input type = "text" name = student_id><br>';

        echo '氏名';
        echo '<input type = "text" name = student_name><br>';

        echo 'パスワード';
        echo '<input type = "password" name = password><br>';

        echo '<button onclick="location.href=\'resist_table.php\'">ログイン</button><br>';
        echo '<button onclick="location.href=\'resist_table.php\'">新規登録</button><br>';

        echo '<button onclick="location.href=\'index.php\'">トップページへ戻る</button>';

    }
    
    ?>
    


</body>
</html>