<?php
//
//共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　ユーザー登録ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

$weight = 50.0;
//POST送信されていた場合
if(!empty($_POST)){
    
    //変数にユーザ情報を代入
    $email = $_POST['email'];
    $username = $_POST['username'];
    $start_weight = $_POST['start_weight'];
    $pass = $_POST['pass'];
    $pass_re = $_POST['pass_re'];
    $weight = $_POST['start_weight'];

    
    //未入力チェック
    validRequired($email, 'email');
    validRequired($username, 'username');
    validRequired($start_weight, 'start_weight');
    validRequired($pass, 'pass');
    validRequired($pass_re, 'pass_re');

    //未入力がなければ各種バリデーションを実施
    if(empty($err_msg)){
        
        //メールアドレスの長さチェック
        validMaxLen($email, 'email');
        validMinLen($email, 'email');
        //メールアドレスの形式チェック
        validEmail($email, 'email');
        //メールアドレスの重複チェック
        validEmailDup($email);
        
        //ニックネームの長さチェック
        validMaxLen($username,'username');
        //ユーザ名がすでに登録されていないか確認
        validUsernameDup($username);
        
        //パスワードの長さチェック
        validMaxLen($pass, 'pass');
        validMinLen($pass, 'pass');
        //パスワード(再入力)の長さチェック
        validMaxLen($pass_re, 'pass_re');
        validMinLen($pass_re, 'pass_re');
        //パスワードが半角英数記号か確認
        validHalf($pass, 'pass');
        validHalf($pass_re, 'pass_re');
        
        //初期体重が最大最小を超えていないか確認
        validWeight($start_weight, 'start_weight');
        
      
        
        //バリデーションが問題なければパスワードが一致しているか確認
        if(empty($err_msg)){
            //パスワードと再入力のパスワードが一致しているか確認
            validMatch($pass, $pass_re, 'pass_re');
        
            //入力内容に問題がなくエラーが特に無ければDB登録を実施
            if(empty($err_msg)){
                //例外処理
                try {
                    //DBへ接続
                    $dbh = dbConnect();
                    //SQL文作成
                    $sql = 'INSERT INTO users (email, username, password, start_date, start_weight, login_time, create_date) VALUES(:email, :username, :pass, :start_date, :start_weight, :login_time, :create_date)';
                    $data = array(':email' => $email, ':username' => $username, ':pass' => password_hash($pass, PASSWORD_DEFAULT), ':start_date' => date('Y-m-d'), ':start_weight' => $start_weight, ':login_time' => date('Y-m-d H:i:s'), ':create_date' => date('Y-m-d H:i:s'));
                    //クエリ実行
                    $stmt = queryPost($dbh, $sql, $data);

                    //クエリ成功の場合
                    if($stmt){
                        //ログイン有効期限（デフォルトを１時間とする）
                        $sesLimit = 60*60;
                        // 最終ログイン日時を現在日時に
                        $_SESSION['login_date'] = time();
                        $_SESSION['login_limit'] = $sesLimit;
                        // ユーザーIDを格納
                        $_SESSION['user_id'] = $dbh->lastInsertId();
                        //セッション変数の中身をログに出す
                        debug('セッション変数の中身：'.print_r($_SESSION,true));
                        //マイページへ遷移
                        header("Location:mypage.php");
                    }
                } catch (Exception $e) {
                    error_log('エラー発生:' . $e->getMessage());
                    $err_msg['common'] = MSG07;
                }
            }
        }
    }
}
?>

<?php
    $siteTitle = 'ユーザー登録';
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
                        <h2 class="title">ユーザ登録</h2>

                       
                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['email'] ?>
                        </div>
                        <label>
                            メールアドレス(他人には公開されません)
                            <input type="text" name="email" value="<?php if(!empty($_POST['email'])) echo $_POST['email'] ?>">
                        </label>
                        
                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['username'] ?>
                        </div>
                        <label>
                            ニックネーム(他人に公開されます)
                            <input type="text" name="username" value="<?php if(!empty($_POST['username'])) echo $_POST['username'] ?>">
                        </label>
                        
                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['start_weight'] ?>
                        </div>

                        <label>
                            現在の体重(単位：kg)
                            <input class="input-follow-unit" type="number" step="0.1" name="start_weight" placeholder="50.0" value = "<?php echo $weight ?>">
                        </label>

                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['pass'] ?>
                        </div>
                        <label>
                            パスワード
                            <input type="password" name="pass" value="<?php if(!empty($_POST['pass'])) echo $_POST['pass'] ?>">
                        </label>

                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['pass_re'] ?>
                        </div>
                        <label>
                            パスワード(再入力)
                            <input type="password" name="pass_re" value="<?php if(!empty($_POST['pass_re'])) echo $_POST['pass_re'] ?>">
                        </label>

                        <div class="btn-container">
                            <input type="submit" class="btn" value="登録">
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <!-- footer -->
        <?php
            require('footer.php'); 
        ?>