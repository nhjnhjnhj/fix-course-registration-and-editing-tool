<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>登録確認ページ</title>
    </head>
    <body>
    
    <?php
    $code = http_response_code(); //HTTPレスポンスコードを取得(404 Not Foundなど)
    //error_log($code); //コンソールに出力するためのタグ
    //var_dump(http_response_code()); //型も明示する関数


    if(http_response_code() == 200){
        //echo 'アクセス成功<br>';
        
        //学籍番号、氏名、パスワードを入力するフォームを作成
        echo '登録しますか？<br>
        <button onclick="location.href=\'index.php\'">はい</button>
        <button onclick="location.href=\'resist_table.php\'">いいえ</button>
        ';

    }
    
    ?>
    


</body>
</html>