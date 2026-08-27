<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>登録確認ページ</title>
    </head>
    <body>
    
    <?php
    $code = http_response_code(); //HTTPレスポンスコードを取得(404 Not Foundなど)
    const HTTP_OK = 200; //レスポンスコード200 = アクセス許可

    if($code == HTTP_OK){
        //echo 'アクセス成功<br>';
        
        //学籍番号、氏名、パスワードを入力するフォームを作成
        echo '登録しますか？<br>';
        echo '<button onclick="location.href=\'index.php\'">はい</button>'; //トップページへ遷移。jsonファイルの要素書き込み処理を入れる予定。
        echo '<button onclick="location.href=\'resist_table.php\'">いいえ</button>'; //授業登録ページに遷移。

    }
    
    ?>
    


</body>
</html>