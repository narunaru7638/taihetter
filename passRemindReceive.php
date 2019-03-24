<?php
//
////共通変数・関数ファイルを読込み
require('function.php');

//
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　 パスワード再発行認証キー入力ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

//セッションに認証キーが入っているか確認。なければリダイレクト。
if(empty($_SESSION['authKey'])){
    header("Location:passRemindSend.php");
}


if(!empty($_POST)){
    debug('POST送信があります。');
    debug('POST情報：'.print_r($_POST,true));
    
    //変数に認証キーを代入
    $authKey = $_POST['token'];
    
    //未入力チェック
    validRequired($authKey, 'token');
    
    if(empty($err_msg)){
        debug('未入力チェックOKです。');
        validHalf($authKey,'token');
        
        if(empty($err_msg)){
            debug('バリデーションOK。');

        
            if($_SESSION['authKey'] !== $authKey){
                $err_msg['token'] = MSG15;
            }
            if( time() > $_SESSION['authKey_limit']){
                $err_msg['token'] = MSG16;
            }
            
            if(empty($err_msg)){
                debug('認証OK。');
                
                $pass = makeRandKey();
                
                try{
                    //DBへ接続
                    $dbh = dbConnect();
                    //SQL文作成
                    $sql = 'UPDATE users SET password = :pass WHERE email = :email AND delete_flg = 0';
                    $data = array(':pass' => password_hash($pass, PASSWORD_DEFAULT), ':email' => $_SESSION['authEmail']);
                    //クエリ実行
                    $stmt = queryPost($dbh, $sql, $data);

                    //クエリ成功の場合
                    if($stmt){
                        debug('クエリ成功しました。');
                        

                        
                        
                        
                        //メール送信
                        $from = 'testmailnaru8@gmail.com';
                        $to = $_SESSION['authEmail'];
                        $subject = 'パスワード再発行完了通知 | Taihetter';
                        $comment = <<<EOT
本メールアドレス宛へのパスワード再発行が完了致しました。
（身に覚えのない方は、お手数ですが本メールの破棄をお願い致します。）

下記ログインページにて再発行したパスワードでログインが可能です。
ログインページ：http://localhost:8888/taihetter/login.php
再発行パスワード：{$pass}
（ログイン後、すぐにパスワードを変更して下さい。）

///////////////////////////////////////
「Taihetter」 presented by naru
///////////////////////////////////////
EOT;
                        $result = sendMail($from, $to, $subject, $comment);
                        
                        //メール送信ができているか確認
                        if( $result ){
                            session_unset();
                            $_SESSION['msg_success'] = SUC03;
                            debug('セッション変数の中身：'.print_r($_SESSION,true));
                            header("Location:login.php");
                        }else{
                            $err_msg['common'] = MSG18;
                        }
                        
                    }else{
                        debug('クエリに失敗しました。');
                        $err_msg['common'] = MSG07;
                    }
                    
                }catch (Exception $e) {
                    error_log('エラー発生:' . $e->getMessage());
                    $err_msg['common'] = MSG07;
                }
            }
        }
    }
}



debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<?php
$siteTitle = 'パスワード再発行';
require('head.php'); 
?>

    <body class="page-login page-1colum">

        <!-- ヘッダー -->
        <?php
        require('header.php');
        ?>

        <!-- メインコンテンツ -->
        <div id="contents" class="site-width">

            <!-- Main -->
            <section id="main" >

                <div class="form-container">

                    <form action="" class="form" method="post">
                        <h2 class="title">パスワード再発行</h2>
                        <p>ご指定のメールアドレスお送りした【パスワード再発行認証メール】内にある「認証キー」をご入力ください。</p>
                        
                        
                        
                        <div class="area-msg">
                            <?php ?>
                        </div>
                        
                        <div class="area-msg">
                            <?php 
                            if(!empty($err_msg['common'])) echo $err_msg['common']  ?>
                        </div>
                        <div class="area-msg">
                            <?php 
                            if(!empty($err_msg['token'])) echo $err_msg['token']  ?>
                        </div>
                        <label>
                            認証キー
                            <input type="text" name="token" value="<?php echo getFormData('token'); ?>">
                        </label>
                        <div class="btn-container">
                            <input type="submit" class="btn" value="送信">
                        </div>
                        <a href="passRemindSend.php">&#171;パスワード再発行メール送信ページに戻る</a>
                    </form>
                </div>

            </section>

        </div>

        <!-- footer -->
        <?php
        require('footer.php'); 
        ?>
