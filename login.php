<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>ログイン・新規登録ページ</title>
    </head>
    <body>
    
    <?php
    session_start();
    $code = http_response_code(); //HTTPレスポンスコードを取得(404 Not Foundなど)
    const HTTP_OK = 200; //レスポンスコード200 = アクセス許可

    if($code == HTTP_OK){
        
        //メールアドレス、氏名、パスワードを入力するフォームを作成。ログインの場合は、resist_tableでメアド・パスワード欄を表示しない。
        echo '<form method="post" action="Back_login_Student.php">';
        echo 'メールアドレス';
        echo '<input type = "text" name = "email"><br>';
        echo 'パスワード';
        echo '<input type = "password" name = "password"><br>';
        echo '<button type="submit">ログイン</button><br>';
        echo '</form>';

        echo '<button onclick="location.href=\'resist_new_table.php\'">新規登録</button><br>'; //授業登録ページへ遷移。新規登録の場合は、resist_tableでメアド・パスワード欄を表示する。

        echo '<button onclick="location.href=\'index.php\'">トップページへ戻る</button>'; //トップページへ遷移

    }
    
    ?>
    


</body>
</html>