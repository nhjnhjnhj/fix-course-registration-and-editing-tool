<?php
    session_start();

    $_SESSION = array(); // $_SESSION配列の中身を空にする
    session_unset();     // 念のため$_SESSION変数自体もunset

    // ブラウザに残っているセッションCookieも期限切れにする。
    if(ini_get('session.use_cookies')){
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();   // サーバー側のセッションデータを破棄

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    header('Location: index.php'); // トップページへ遷移
    exit();
?>
