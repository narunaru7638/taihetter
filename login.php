<?php
//
////共通変数・関数ファイルを読込み
require('function.php');

//
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　 ログインページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

//ログイン認証
require('auth.php');

//POST送信されていた場合
if(!empty($_POST)){
    debug('POST送信があります。');
    
    //変数にユーザ情報を代入
    $email = $_POST['email'];
    $pass = $_POST['pass'];
    $pass_save = (!empty($_POST['pass_save'])) ? true : false ;

    //未入力チェック
    validRequired($email, 'email');
    validRequired($pass, 'pass');
    
    
    //未入力がなければ各種バリデーションを実施
    if(empty($err_msg)){
        
        //メールアドレスの長さチェック
        validMaxLen($email, 'email');
        validMinLen($email, 'email');
        //メールアドレスの形式チェック
        validEmail($email, 'email');
        //パスワードの長さチェック
        validMaxLen($pass, 'pass');
        validMinLen($pass, 'pass');
        //パスワードが半角英数記号か確認
        validHalf($pass, 'pass');
    
        //入力内容に問題がなくエラーが特に無ければDBの情報を確認
        if(empty($err_msg)){
            debug('バリデーションOKです。');
            //例外処理
            try {
                //DBへ接続
                $dbh = dbConnect();
                //SQL文作成
                $sql = 'SELECT password,id FROM users WHERE email = :email AND delete_flg = 0';
                $data = array(':email' => $email);

                //クエリ実行
                $stmt = queryPost($dbh, $sql, $data);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                debug('クエリ結果の中身：'.print_r($result,true));

                // パスワード照合
                if(!empty($result) && password_verify($pass, array_shift($result))){
                    debug('パスワードがマッチしました。');
                    
                    //
                    $sesLimit = 60 * 60;
                    $_SESSION['login_date'] = time();
                    
                    
                    if($pass_save){
                        debug('ログイン保持にチェックがありました。');
                        $_SESSION['login_limit'] = $sesLimit*24*30;
                    }else{
                        debug('ログイン保持にチェックがありませんでした。');
                        $_SESSION['login_limit'] = $sesLimit;
                    }
                    
                    $_SESSION['user_id'] = $result['id'];
                    debug('セッション変数の中身：'.print_r($_SESSION,true));
                    //マイページへ遷移
                    header("Location:mypage.php");
                }else{
                    debug('パスワードがアンマッチです。');
                    $err_msg['common'] = MSG10;
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
$siteTitle = 'ログイン';
require('head.php'); 
?>

    <body class="page-login page-1colum">

    <!-- ヘッダー -->
    <?php
    require('header.php');
    ?>

        <p id="js-show-msg" style="display:none;" class="msg-slide">
            <?php echo getSesssionFlash('msg_success'); ?>
        </p>
       
        <!-- メインコンテンツ -->
        <div id="contents" class="site-width">

            <!-- Main -->
            <section id="main" >

                <div class="form-container">

                    <form action="" class="form" method="post">
                        <h2 class="title">ログイン</h2>
                        
                        <div class="area-msg">
                            <?php 
                            if(!empty($err_msg['common'])) echo $err_msg['common'];
                            ?>
                        </div>
                        
                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['email'] ?>
                        </div>
                        <label>
                            emailアドレス
                            <input type="text" name="email" value="<?php if(!empty($_POST['email'])) echo $_POST['email'] ?>">
                        </label>
                        
                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['pass'] ?>
                        </div>
                        <label>
                            パスワード
                            <input type="password" name="pass" value="<?php if(!empty($_POST['pass'])) echo $_POST['pass'] ?>">
                        </label>
                        
                        <label>
                            <input type="checkbox" name="pass_save"><span class="sub-message">次回ログインを省略する</span>
                        </label>
                        <div class="btn-container">
                            <input type="submit" class="btn" value="ログイン">
                        </div>
                        <span class="sub-message">パスワードを忘れた方は<a href="passRemindSend.php">コチラ</a></span>
                    </form>
                </div>
                <div style="margin:20px 270px;" >
                    <p>ゲストユーザーとしてログインも可能です。</p>
                    <p>ゲストユーザーのemailアドレスとパスワードは以下です。</p>
                    <p>　【emailアドレス】：gestuser@mail.com</p>
                    <p>　【 パスワード 】　：gestuser</p>
                    <p>　　※ゲストユーザーは以下の機能が利用できません。</p>
                    <p>　　　・プロフィール編集</p>
                    <p>　　　・パスワード変更</p>
                    <p>　　　・退会</p>
                    <p>　　　・パスワード再発行メール送信</p>
                </div>
            </section>

        </div>

        <!-- footer -->
        <?php
        require('footer.php'); 
        ?>