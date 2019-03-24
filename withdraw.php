<?php
//
////共通変数・関数ファイルを読込み
require('function.php');
//
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　退会ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

//ログイン認証
require('auth.php');


$user_id = $_SESSION['user_id'];

if(!empty($_POST)){
    
    try {
        $user_id = $_POST['user_id'];

        //DBへ接続
        $dbh = dbConnect();
        //SQL文作成
        //$sql = 'SELECT password,id FROM users WHERE username = :username AND delete_flg = 0';
        $sql = 'UPDATE users SET delete_flg = 1 where id = :user_id AND NOT (id = :gestUserId )';

        $data = array(':user_id' => $user_id, ':gestUserId' => $gestUserId );

        //クエリ実行
        $stmt = queryPost($dbh, $sql, $data);

        debug('クエリ結果の中身：'.print_r($stmt,true));
        
        if(!empty($stmt)){
            //セッション変数の中身をログに出す
            debug('セッション変数の中身：'.print_r($_SESSION,true));
            // セッションを削除（ログアウトする）
            session_destroy();
            //セッション変数の中身をログに出す
            debug('セッション変数の中身：'.print_r($_SESSION,true));            
            header("Location:login.php");

        }else{
            debug('クエリが失敗しました。');
            $err_msg['common'] = MSG07;
            
        }
        
    } catch (Exception $e) {
        error_log('エラー発生:' . $e->getMessage());
        $err_msg['common'] = MSG07;
    }
    
    
}
debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>


<?php
$siteTitle = '退会';
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
                        <h2 class="title">退会</h2>

                        <div class="btn-container">
                            <input type="hidden" name=user_id value="<?php echo $_SESSION['user_id']?>">
                            <input type="submit" class="btn btn-mid" value="退会する">
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
