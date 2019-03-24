<?php

//共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　パスワード再発行メール送信ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();


//================================
// 画面処理
//================================

//POST送信されていた場合
if(!empty($_POST)){
    debug('POST送信があります。');
    
    //変数にPOST情報を代入
    $email = $_POST[email];
    
    //未入力チェック
    validRequired($email, 'email');
    
    //ゲストユーザーチェック（ゲストユーザは利用不可）
    validGestUserEmail($email, 'common');

    
    if(empty($err_msg)){
        debug('未入力チェックOK。');
        validMaxLen($email, 'email');
        validEmail($email, 'email');
    
        if(empty($err_msg['email'])){
            debug('バリデーションOKです。');
            try{
                //DBへ接続
                $dbh = dbConnect();
                //SQL文作成
                $sql = 'SELECT count(*) FROM users WHERE email = :email AND delete_flg = 0';
                $data = array(':email' => $email);
                //クエリ実行
                $stmt = queryPost($dbh, $sql, $data);
                //クエリ結果の値を取得
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                //EmailがDBに登録されている場合
                if($stmt && array_shift($result)){
                    debug('クエリ成功。DB登録あり。');
                    //認証キー作成の関数
                    $_SESSION['msg_success'] = SUC03;
                    $authKey = makeRandKey();

                    //メール送信
                    $from = 'testmailnaru8@gmail.com';
                    $to = $email;
                    $subject = 'パスワード再発行通知 | Taihetter';
                    $comment = <<<EOT
本メールアドレス宛へのパスワード再発行依頼がございました。
（身に覚えのない方は、お手数ですが本メールの破棄をお願い致します。）

下記ページにて認証キーを入力することでパスワードの再発行が可能です。
認証キー入力ページ：http://localhost:8888/taihetter/passRemindReceive.php
認証キー：{$authKey}
（認証キーの使用期限は30分間です。）

認証キーの使用期限後に、パスワードの再発行を実施したい場合は、
再度認証キーを送信先のメールアドレスを入力してください。
パスワード再発行メール送信ページ：http://localhost:8888/taihetter/passRemindSend.php

///////////////////////////////////////
「Taihetter」 presented by naru
///////////////////////////////////////
EOT;

                    $result = sendMail($from, $to, $subject, $comment);
                    
                    //メール送信ができているか確認
                    if( $result ){
                        //認証に必要な情報をセッションへ保存
                        $_SESSION['authKey'] = $authKey;
                        $_SESSION['authEmail'] = $email;
                        $_SESSION['authKey_limit'] = time()+(60 *30);
                        debug('セッション変数の中身:'.print_r($_SESSION,true)); header("Location:passRemindReceive.php");
                    } else {
                        $err_msg['common'] = MSG18;
                    }
                    
                    
                }else{
                    debug('クエリに失敗したか、DBに登録されていないアドレスが入力されました。');
                    $err_msg['common'] = MSG07;
                }
                
            }catch (Exception $e) {
                error_log('エラー発生:' . $e->getMessage());
                $err_msg['common'] = MSG07;
            }
        }
    }    
}
debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<?php
$siteTitle = 'パスワード再発行メール送信';
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
                        <h2 class="title">パスワード再発行メール送信</h2>
                        <p>ご指定のメールアドレス宛にパスワード再発行用のURLと認証キーをお送り致します。</p>
                        <div class="area-msg">
                            <?php if(!empty($err_msg['common'])) echo $err_msg['common'] ?>
                        </div>
                        <div class="area-msg">
                            <?php if(!empty($err_msg['email'])) echo $err_msg['email'] ?>
                        </div>
                        <label>
                            Email
                            <input type="text" name="email" value="<?php echo getFormData('email') ?>">
                        </label>
                        <div class="btn-container">
                            <input type="submit" class="btn" value="送信">
                        </div>
                        <a href="login.php">&#171;ログイン画面に戻る</a>
                    </form>
                </div>

            </section>

        </div>

        <!-- footer -->
        <?php
            require('footer.php');
        ?>
