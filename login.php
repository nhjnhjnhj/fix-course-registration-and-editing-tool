<?php
session_start();

// 認証処理から返されたエラーを画面内へ一度だけ表示する。
$loginError = '';
if(isset($_SESSION['NotFound_Student']) && $_SESSION['NotFound_Student'] == true){
    $loginError = 'ユーザーが存在しません。';
    unset($_SESSION['NotFound_Student']);
}
else if(isset($_SESSION['incollect']) && $_SESSION['incollect'] == true){
    $loginError = 'メールアドレスまたはパスワードが違います。';
    unset($_SESSION['incollect']);
}
else if(isset($_SESSION['empty']) && $_SESSION['empty'] == true){
    $loginError = 'メールアドレスまたはパスワードが未入力です。';
    unset($_SESSION['empty']);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログインページ</title>
    <!-- 共通テーマとログイン画面専用デザイン -->
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-page">
    <h1 class="login-title">受講科目登録・閲覧システム</h1>
    <p class="login-description">登録済みの時間割を編集するため、ログインしてください。</p>

    <?php if($loginError !== ''): ?>
        <p class="login-error"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <!-- 既存の学生認証処理へメールアドレスとパスワードを送信する -->
    <form method="post" action="Back_login_Student.php">
        <label for="login-email">メールアドレス</label>
        <input id="login-email" type="text" name="email">

        <label for="login-password">パスワード</label>
        <input id="login-password" type="password" name="password">

        <button type="submit">ログイン</button>
    </form>

    <a class="button login-back-button" href="index.php">トップページへ戻る</a>
</body>
</html>
