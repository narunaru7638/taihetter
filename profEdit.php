<?php
//
////共通変数・関数ファイルを読込み
require('function.php');
//
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　プロフィール編集ページ　');
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
    debug('FILE情報：'.print_r($_FILES, true));
    
    //変数にユーザ情報を代入
    $email = $_POST['email'];
    $username = $_POST['username'];
    $start_weight = (!empty ($_POST['start_weight'])) ? $_POST['start_weight'] : '';
    $start_weight = $_POST['start_weight'];
    $your_goal = $_POST['your_goal'];
    $your_action = $_POST['your_action'];
    $mypage_comment = $_POST['mypage_comment'];

    //$_FILESといいう変数の中身(pic)のファイル名(name)があれば、uploadImg関数を実施(nameがあるということ)
    //なければ空を入れる
    //uploadImgは第一引数に実際の$_FILE変数、第二引数にエラーメッセージ用のキーを入れる
    //戻り値はファイルのパス($path)
    $pic = ( !empty($_FILES['pic']['name']) ) ? uploadImg($_FILES['pic'],'pic') : '';
    $pic = ( empty($pic) && !empty($user_info['pic']) ) ? $user_info['pic'] : $pic;

    //emailアドレスに関してDB情報と異なる場合にバリデーションを行う
    if($user_info['email'] !== $email){
        //未入力チェック
        validRequired($email, 'usere');
        //メールアドレスの長さチェック
        validMaxLen($email, 'email');
        validMinLen($email, 'email');
        //メールアドレスの形式チェック
        validEmail($email, 'email');
        if(empty($err_msg['email'])){
            //重複チェック
            validEmailDup($email);
        }
    }
    
    //ニックネームに関してDB情報と異なる場合にバリデーションを行う
    if($user_info['username'] !== $username){
        //未入力チェック
        validRequired($username, 'username');
        //最大文字数チェック
        validMaxLen($username, 'username');
        if(empty($err_msg['username'])){
            //重複チェック
            validUsernameDup($username);
        }
    }

    //初期体重に関してDB情報と異なる場合にバリデーションを行う
    if($user_info['start_weight'] !== $start_weight){
        //未入力チェック
        validWeight($start_weight, 'start_weight');
        validRequired($start_weight, 'start_weight');

    }

    if($user_info['your_goal'] !== $your_goal){
        //長さチェック
        validMaxLen($your_goal, 'your_goal');
    }

    if($user_info['your_action'] !== $your_action){
        //長さチェック
        validMaxLen($your_action, 'your_action');
    }
    
    if($user_info['mypage_comment'] !== $mypage_comment){
        //長さチェック
        validMaxLen($mypage_comment, 'mypage_comment');
    }
    
    if(empty($err_msg)){
        debug('バリデーションOKです。');
        try {
            //DBへ接続
            $dbh = dbConnect();
            //SQL文作成
            //$sql = 'SELECT password,id FROM users WHERE username = :username AND delete_flg = 0';
            $sql = 'UPDATE users SET username = :username, pic = :pic, start_weight = :start_weight, your_goal = :your_goal, your_action = :your_action, mypage_comment = :mypage_comment WHERE id = :user_id AND NOT (id = :gestUserId)';
            $data = array(':username' => $username, ':pic' => $pic, ':start_weight' => $start_weight, ':your_goal' => $your_goal, ':your_action' => $your_action, ':mypage_comment' => $mypage_comment, ':user_id' => $user_info['id'], ':gestUserId' => $gestUserId);
            //クエリ実行
            $stmt = queryPost($dbh, $sql, $data);

            if(!empty($stmt)){
                debug('クエリ成功');
                //セッション変数の中身をログに出す
                debug('クエリ結果の中身：'.print_r($stmt,true));

                if($gestUserId  === (int)$user_info['id']){
                    $_SESSION['msg_success'] = SUC06;
                }else{
                    $_SESSION['msg_success'] = SUC01;
                }
                
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

debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<?php
$siteTitle = 'プロフィール編集';
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

                    <form action="" class="form form-more-wide" method="post" enctype="multipart/form-data">
                        <h2 class="title">プロフィール変更</h2>
                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['common'] ?>
                        </div>
                        
                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['email'] ?>
                        </div>
                        <label>
                            emailアドレス(他人には公開されません)
                            <input type="text" name="email" value="<?php echo sanitize(getFormData('email')) ?>">
                        </label>

                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['username'] ?>
                        </div>
                        <label>
                            ニックネーム(他人に公開されます)
                            <input type="text" name="username" value="<?php echo sanitize(getFormData('username')) ?>">
                        </label>
                        
                        <div style="overflow:hidden;">
                            <div class="imgDrop-container">
                                プロフィール画像(他人に公開されます)
                                <div class="area-msg">
                                    <?php if(!empty($err_msg)) echo $err_msg['pic'] ?>
                                </div>
                                <label class="js-area-drop">
                                    <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
                                    <input type="file" name="pic" class="js-input-file">
                                    <img src="<?php echo sanitize(getFormData('pic')); ?>" alt="" class="prev-img" style="<?php if(empty(getFormData('pic'))) echo 'display:none;' ?>">
                                    <span class="char-in-area-drop">ドラッグ＆ドロップ</span>
                                </label>
                            </div>
                        </div>
                       
                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['start_weight'] ?>
                        </div>
                        <label>
                            ダイエット開始時の体重(単位：kg)
                            <input class="input-follow-unit" type="number" step="0.1" name="start_weight" value="<?php echo sanitize(getFormData('start_weight')) ?>">
                        </label>
                        
                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['your_goal'] ?>
                        </div>
                        <label>
                            ダイエットをはじめた理由・目標(他人に公開されます)
                            <textarea name="your_goal" class="js-count" cols="30" rows="10" style="height:150px;"><?php echo sanitize(getFormData('your_goal')) ?></textarea>
                            <span class="js-count-view">0</span>/255
                        </label>

                        
                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['your_action'] ?>
                        </div>
                        <label>
                            ダイエットのために実施すること(他人に公開されます)
                            <textarea name="your_action" class="js-count" cols="30" rows="10" style="height:150px;"><?php echo sanitize(getFormData('your_action')) ?></textarea>     <span class="js-count-view">0</span>/255                      
                        </label>
                    

                        
                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['mypage_comment'] ?>
                        </div>
                        <label>
                            コメント・ブログ情報など(他人に公開されます)
                            <textarea name="mypage_comment" class="js-count" cols="30" rows="10" style="height:150px;"><?php echo sanitize(getFormData('mypage_comment')) ?></textarea>
                            <span class="js-count-view">0</span>/255
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

