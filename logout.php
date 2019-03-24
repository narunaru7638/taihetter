<?php
//共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　ログアウトページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

debug('セッション変数の中身：'.print_r($_SESSION,true));

debug('ログアウトします。');
// セッションを削除（ログアウトする）
session_destroy();

debug('ログインページへ遷移します。');
// ログインページへ
header("Location:login.php");
?>