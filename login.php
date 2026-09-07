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
        
        //Back_login_Student.phpでログインに失敗した場合、セッション変数NotFound_Studentがtrueに設定されるので、アラートを表示する。
        if(isset($_SESSION['NotFound_Student']) && $_SESSION['NotFound_Student'] == true){

            echo '<script>alert("ユーザーが存在しません");</script>';
            unset($_SESSION['NotFound_Student']); //セッション破棄
        }

        //Back_login_Student.phpでメールアドレスまたはパスワードが異なる場合、セッション変数incollectがtrueに設定されるので、アラートを表示する。
        if(isset($_SESSION['incollect']) && $_SESSION['incollect'] == true){

            echo '<script>alert("メールアドレスまたはパスワードが違います");</script>';
            unset($_SESSION['incollect']); //セッション破棄
        }

        if(isset($_SESSION['empty']) && $_SESSION['empty'] == true){

            echo '<script>alert("メールアドレスまたはパスワードが未入力です");</script>';
            unset($_SESSION['empty']); //セッション破棄
        }


        //メールアドレス、氏名、パスワードを入力するフォームを作成。ログインの場合は、resist_tableでメアド・パスワード欄を表示しない。
        echo '<form method="post" action="Back_login_Student.php">';
        echo 'メールアドレス';
        echo '<input type = "text" name = "email"><br>';
        echo 'パスワード';
        echo '<input type = "password" name = "password"><br>';
        echo '<button type="submit">ログイン</button><br>';
        echo '</form>';

        echo '<button onclick="location.href=\'index.php\'">トップページへ戻る</button>'; //トップページへ遷移

    }
    
    ?>
    


</body>
</html>