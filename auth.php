<?php

//================================
// ログイン認証・自動ログアウト
//================================
// ログインしている場合
if( !empty($_SESSION['login_date']) ){
    debug('ログイン済みユーザーです。');
    //現在日時が最終ログイン時間と制限時間を超えていた場合
    if( $_SESSION['login_date'] + $_SESSION['login_limit'] < time()){
        debug('ログイン有効期限オーバーです。');
        //セッションを削除する
        session_destroy();
        //ログインページヘ
        header("Location:login.php");
        
    }else{
        
        debug('ログイン有効期限以内です。');
        //最終ログイン時間を更新
        $_SESSION['login_date'] = time();
        //現在の処理ページがlogin.phpならマイページへ遷移
        //$_SERVER['PHP_SELF']はドメインからのパスを返す
        //さらにbasename関数を使うとファイル名のみ取り出す
        if(basename($_SERVER['PHP_SELF']) === 'login.php'){
            debug('マイページへ遷移します。');
            header("Location:mypage.php");
        }
    }
    
    
}else{
    debug('未ログインユーザーです。');
    if(basename($_SERVER['PHP_SELF']) !== 'login.php'){
        debug('ログインページへ遷移します。');
        header("Location:login.php");
    }

    
}