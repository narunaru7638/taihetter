<?php
//
////共通変数・関数ファイルを読込み
require('function.php');
//
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　パスワード変更ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

//ログイン認証
require('auth.php');

//================================
// 画面処理
//================================

//DBからユーザデータを取得
$user_info = getUser($_SESSION['user_id']);
debug('取得したユーザ情報：'.print_r($user_info, true));

//POST送信されていた場合
if(!empty($_POST)){
    debug('POST送信があります。');
    debug('POST情報：'.print_r($_POST, true));

    //変数にユーザ情報を代入
    $pass = $_POST['pass'];
    $pass_new = $_POST['pass_new'];
    $pass_new_re = $_POST['pass_new_re'];
    
    //未入力チェック
    validRequired($pass, 'pass');
    validRequired($pass_new, 'pass_new');
    validRequired($pass_new_re, 'pass_new_re');
    
    if(empty($err_msg)){
        debug('未入力チェックOK。');
        
        //古いパスワードのチェック
        validPass($pass, 'pass');
        //新しいパスワードのチェック
        validPass($pass_new, 'pass_new');

        //DBのパスワードと入力した前のパスワードが同じかチェック
        if(!password_verify($pass, $user_info['password'])){
            $err_msg['pass'] = MSG11;
        }
        
        //前のパスワードと新しいパスワードが同じかチェック
        if($pass_new === $pass){
            $err_msg['pass_new'] = MSG12;
        }
        
        //新しいパスワードと再入力のパスワードがあっているかチェック
        validMatch($pass_new, $pass_new_re, 'pass_new');

                
        if(empty($err_msg)){
            debug('バリデーションOKです。');

            //例外処理
            try {
                //DBへ接続
                $dbh = dbConnect();
                //SQL文作成
                $sql = 'UPDATE users SET password = :pass_new WHERE id = :id AND delete_flg = 0 AND NOT (id = :gestUserId )';
                $data = array(':pass_new' => password_hash($pass_new, PASSWORD_DEFAULT),':id' => $user_info['id'], ':gestUserId' => $gestUserId );
                //クエリ実行
                $stmt = queryPost($dbh, $sql, $data);
                
                //クエリ成功の場合
                if($stmt){
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    debug('クエリ結果の中身：'.print_r($result,true));
                    

                    ////メール送信前の準備
                    //                1./etc/postfix/main.cfのファイル最後尾に以下を追加
                    //                relayhost = [smtp.gmail.com]:587
                    //                #sasl setting
                    //                smtp_sasl_auth_enable = yes
                    //                smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd
                    //                smtp_sasl_security_options = noanonymous
                    //                smtp_sasl_tls_security_options = noanonymous 
                    //                smtp_sasl_mechanism_filter = plain
                    //                #tls setting
                    //                smtp_use_tls = yes
                    //                2./etc/postfix/内に「sasl_passwd」というファイルを作成し、以下を記入
                    //                [smtp.gmail.com]:587 Gmailアカウント@gmail.com:Gmailのパスワード
                    //                3.コンソールで「sudo postmap /etc/postfix/sasl_passwd」と入力
                    //                4.sudo postfix start
                    //                5.sudo postfix reload
                    //                6.date | mail -s test Gmailアカウント@gmail.com
                    //                7.メールが届いていれば完了

                    //メールを送信
                    $username = ($user_info['username']) ? $user_info['username'] : '名無し';
                    $from = 'testmailnaru8@gmail.com';
                    $to = $user_info['email'];
                    $subject = 'パスワード変更通知 | Taihetter';
                    //EOTでもなんでもよい。先頭の<<<あとの文字列と合わせる。最後のEOTの前後に空白などは何も入れてはいけない。
                    //EOT内の半角空白もすべてそのまま扱われるのでインデントはしないこと。
                    $comment = <<<EOT
{$username}　さん
パスワードが変更されました。

///////////////////////////////////////
「Taihetter」 presented by naru
///////////////////////////////////////
EOT;

                    $result = sendMail($from, $to, $subject, $comment);
                    
                    
                    if($gestUserId  === (int)$user_info['id']){
                        $_SESSION['msg_success'] = SUC06;
                    }else{
                        $_SESSION['msg_success'] = SUC02;
                    }

                    
                    debug('セッション変数の中身：'.print_r($_SESSION,true));
                    header("Location:mypage.php");

                }else{
                debug('クエリが失敗しました。');
                $err_msg['common'] = MSG07;
                }
            } catch (Exception $e) {
                error_log('エラー発生:' . $e->getMessage());
                $err_msg['common'] = MSG07;
            }


        }


    }
    
}

debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<?php
$siteTitle = 'パスワード変更';
require('head.php'); 
?>

<body class="page-login page-2colum">

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
                    <h2 class="title">パスワード変更</h2>
                    
                    <div class="area-msg">
                        <?php if(!empty($err_msg)) echo $err_msg['common'] ?>
                    </div>
                    <div class="area-msg">
                        <?php if(!empty($err_msg)) echo $err_msg['pass'] ?>
                    </div>
                    <label>
                        古いパスワード
                        <input type="password" name="pass" value="<?php if(!empty($_POST)) echo $_POST['pass'] ?>">
                    </label>
                    
                    <div class="area-msg">
                        <?php if(!empty($err_msg)) echo $err_msg['pass_new'] ?>
                    </div>
                    <label>
                        新しいパスワード
                        <input type="password" name="pass_new" value="<?php if(!empty($_POST)) echo $_POST['pass_new'] ?>">
                    </label>
                    
                    <div class="area-msg">
                        <?php if(!empty($err_msg)) echo $err_msg['pass_new_re'] ?>
                    </div>
                    <label>
                        新しいパスワード(再入力)
                        <input type="password" name="pass_new_re" value="<?php if(!empty($_POST)) echo $_POST['pass_new_re'] ?>">
                    </label>
                    <div class="btn-container">
                        <input type="submit" class="btn" value="変更する">
                    </div>
                </form>
            </div>

        </section>

        <!-- サイドバー -->
        <?php
        require('sidebar.php'); 
        ?>

    </div>

<!-- footer -->
<?php
require('footer.php'); 
?>

