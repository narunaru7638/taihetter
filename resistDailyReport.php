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
$currentWeightIdNum = (!empty($_GET['p'])) ? $_GET['p'] : '';

if( !is_int((int)$currentWeightIdNum)){
    error_log('エラー発生：指定ページに不正な値が入りました');
    header("Location:mypage.php");
}



//DBから体重の情報を取得
//GET通信(一覧から遷移)の際は該当する情報を取得する
if(!empty($_GET['p'])){    
    $currentWeightIdNum = $_GET['p'];
    $weight_info = getWeightById($currentWeightIdNum);
//GET通信じゃない場合は本日の情報を取得する
}else{
    $currentWeightIdNum = $_SESSION['user_id'];
    $weight_info = getWeight($currentWeightIdNum, date('Y-m-d'));
}

if(!empty($weight_info['user_id'])){
    if($weight_info['user_id'] !== $_SESSION['user_id']){
        error_log('エラー発生：指定ページに自身以外のページを指定されました');
        header("Location:mypage.php");
    }
}




//DBから取得した体重情報にマッチするデイリーレポート情報を取得
$dailyreport_info = getDailyreport($weight_info['id']);

//すでに登録済みか判断するフラグ。未登録なら新規登録、登録済みなら編集へ。
$today_regist_flg = (empty($weight_info)) ? false : true ;


if(!empty($_POST)){
    
    debug('POST送信があります。');
    debug('POST情報:'.print_r($_POST, true));
    
    //POST情報を変数に代入
    $weight = (!empty ($_POST['weight'])) ? $_POST['weight'] : '';
    $comment = $_POST['comment'];

    //画像情報を変数に代入
    //$_FILESに画像の情報があればUPLOADする。
    $pic_breakfast = (!empty ($_FILES['pic_breakfast']['name'])) ? uploadImg($_FILES['pic_breakfast'], 'pic_breakfast') : '';
    //$_FILESに画像がなければDBの画像情報を取得する。
    $pic_breakfast = (empty($pic_breakfast) && !empty($dailyreport_info['pic_breakfast']) ? $dailyreport_info['pic_breakfast'] : $pic_breakfast);

    $pic_lunch = (!empty ($_FILES['pic_lunch']['name'])) ? uploadImg($_FILES['pic_lunch'], 'pic_lunch') : '';
    $pic_lunch = (empty($pic_lunch) && !empty($dailyreport_info['pic_lunch']) ? $dailyreport_info['pic_lunch'] : $pic_lunch);

    $pic_dinner = (!empty ($_FILES['pic_dinner']['name'])) ? uploadImg($_FILES['pic_dinner'], 'pic_dinner') : '';
    $pic_dinner = (empty($pic_dinner) && !empty($dailyreport_info['pic_dinner']) ? $dailyreport_info['pic_dinner'] : $pic_dinner);
    
    $pic_another_food1 = (!empty ($_FILES['pic_another_food1']['name'])) ? uploadImg($_FILES['pic_another_food1'], 'pic_another_food1') : '';
    $pic_another_food1 = (empty($pic_another_food1) && !empty($dailyreport_info['pic_another_food1']) ? $dailyreport_info['pic_another_food1'] : $pic_another_food1);

    $pic_another_food2 = (!empty ($_FILES['pic_another_food2']['name'])) ? uploadImg($_FILES['pic_another_food2'], 'pic_another_food2') : '';
    $pic_another_food2 = (empty($pic_another_food2) && !empty($dailyreport_info['pic_another_food2']) ? $dailyreport_info['pic_another_food2'] : $pic_another_food2);

    $pic_another_food3 = (!empty ($_FILES['pic_another_food3']['name'])) ? uploadImg($_FILES['pic_another_food3'], 'pic_another_food3') : '';
    $pic_another_food3 = (empty($pic_another_food3) && !empty($dailyreport_info['pic_another_food3']) ? $dailyreport_info['pic_another_food3'] : $pic_another_food3);


    
    //バリデーション
    validWeight($weight,'weight');
    validRequired($weight, 'weight');
    validMaxLen($comment, 'comment');
    
    //入力内容に問題がなければDBに情報を登録
    if(empty($err_msg)){
        debug('バリデーションOKです。');
        try{
            //DBへ接続
            $dbh = dbConnect();
            //SQL文作成(登録済みならUPDATE、新規ならINSERT)
            if($today_regist_flg){
                $sql1 = 'UPDATE weights SET weight = :weight WHERE user_id = :user_id AND date_of_mesure = :date_of_mesure AND delete_flg = 0';
//                $data1 = array(':weight' => $weight, ':date_of_mesure' => date('Y-m-d'), ':user_id' => $_SESSION['user_id'] );
                $data1 = array(':weight' => $weight, ':date_of_mesure' => $weight_info['date_of_mesure'], ':user_id' => $_SESSION['user_id'] );

            }else{
                $sql1 = 'INSERT INTO weights (weight, date_of_mesure, user_id, create_date) VALUES(:weight, :date_of_mesure, :user_id, :create_date)';
                $data1 = array(':weight' => $weight, ':date_of_mesure' => date('Y-m-d'), ':user_id' => $_SESSION['user_id'], ':create_date' => date('Y-m-d H:i:s') );
            }
            //クエリー実行
            $stmt1 = queryPost($dbh, $sql1, $data1);
            
            //体重を登録が成功したら、デイリーレポートを登録するために、weightsテーブルのIDを取得する
            if($stmt1){
                debug('体重の登録/編集が完了しました。');
                //SQL文作成(現在のユーザの今日の日付のweight情報を取得する)
                if($today_regist_flg){
                    $sql2 = 'SELECT id FROM weights WHERE user_id = :user_id AND date_of_mesure = :date_of_mesure AND delete_flg = 0';
                    $data2 = array(':user_id' => $_SESSION['user_id'], ':date_of_mesure' => $weight_info['date_of_mesure']);
                }else{
                    $sql2 = 'SELECT id FROM weights WHERE user_id = :user_id AND date_of_mesure = :date_of_mesure AND delete_flg = 0';
                    $data2 = array(':user_id' => $_SESSION['user_id'], ':date_of_mesure' => date('Y-m-d'));
                }
                //クエリ実行
                $stmt2 = queryPost($dbh, $sql2, $data2);
                //クエリ結果の中身
                $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                //クエリ結果の中身はstr型になっているのでint型に変換
                $weight_id = intval($result2['id']);
                
                //weightsテーブルのID取得が成功したら、デイリーレポートを登録する
                if($stmt2 && array_shift($result2)){
                    debug('体重データの取得ができましたのでデイリーレポート登録準備に移ります。');
                    
                    if($today_regist_flg){
                        $sql3 = 'UPDATE dailyreport SET pic_breakfast = :pic_breakfast, pic_lunch = :pic_lunch, pic_dinner = :pic_dinner, pic_another_food1 = :pic_another_food1, pic_another_food2 = :pic_another_food2, pic_another_food3 = :pic_another_food3, comment = :comment WHERE weight_id = :weight_id AND delete_flg = 0';
                        $data3 = array(':pic_breakfast' => $pic_breakfast, ':pic_lunch' => $pic_lunch, ':pic_dinner' => $pic_dinner, ':pic_another_food1' => $pic_another_food1, ':pic_another_food2' => $pic_another_food2, ':pic_another_food3' => $pic_another_food3, ':comment' => $comment, ':weight_id' => $weight_id );
                    }else{
                        $sql3 = 'INSERT INTO dailyreport (weight_id, pic_breakfast, pic_lunch, pic_dinner, pic_another_food1, pic_another_food2, pic_another_food3, comment, create_date) VALUES(:weight_id, :pic_breakfast, :pic_lunch, :pic_dinner, :pic_another_food1, :pic_another_food2, :pic_another_food3, :comment, :create_date)';
                        $data3 = array(':weight_id' => $weight_id, ':pic_breakfast' => $pic_breakfast, ':pic_lunch' => $pic_lunch, ':pic_dinner' => $pic_dinner, ':pic_another_food1' => $pic_another_food1, ':pic_another_food2' => $pic_another_food2, ':pic_another_food3' => $pic_another_food3, ':comment' => $comment, ':create_date' => date('Y-m-d H:i:s') );
                    }
                    $stmt3 = queryPost($dbh, $sql3, $data3);

                    if(!empty($stmt3)){
                        debug('デイリーレポートの登録/編集完了');
                        debug('クエリ結果の中身:'.print_r($stmt3,true));
                        ($today_regist_flg) ? $_SESSION['msg_success'] = SUC05 : $_SESSION['msg_success'] = SUC04 ;
                        header("Location:mypage.php");
                        
                    }else{
                        debug('クエリが失敗しました。');
                        $err_msg['common'] = MSG07;
                    }
                }else{
                    debug('クエリが失敗しました。');
                    $err_msg['common'] = MSG07;
                }
            }else{
                debug('クエリが失敗しました。');
                $err_msg['common'] = MSG07;
            }
                
                
        }catch (Exception $e) {
            error_log('エラー発生：'. $e->getMessage());
            $err_msg['common'] = MSG07;
        }
    }
    
}



debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<?php
$siteTitle = '体重・食事の登録';
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
                        <h2 class="title">体重・食事の<?php echo ($today_regist_flg) ? '修正' : '登録' ?></h2>

                       
                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['weight'] ?>
                        </div>
                        <?php 
                            $weightForm = getFormDataWeight('weight');
                            ($weightForm) ? $weightForm = $weightForm : $weightForm = 50;
                        ?>
                        <label>
                            <?php echo ($today_regist_flg) ? $weight_info['date_of_mesure'] : '本日' ?>の体重(単位：kg)
                            <input class="input-follow-unit" type="number" step="0.1" name="weight" placeholder="50.0" value="<?php echo sanitize($weightForm); ?>">
                           
                        </label>

                        <div class="food-images">

                                <div style="overflow:hidden;">
                                    <div class="imgDrop-container">
                                       
                                        <div class="one-food-space">
                                            <div class="kind-of-food">朝食</div>
                                            <div class="area-msg">
                                                <?php if(!empty($err_msg)) echo $err_msg['pic_breakfast'] ?>
                                            </div>      
                                            <label class="js-area-drop">
                                                <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
                                                <input type="file" name="pic_breakfast" class="js-input-file">
                                                <img src="<?php echo sanitize(getFormDataDailyreport('pic_breakfast')); ?>" alt="" class="prev-img" style="<?php if(empty(getFormDataDailyreport('pic_breakfast'))) echo 'display:none;' ?>">
                                                <span class="char-in-area-drop">ドラッグ＆ドロップ</span>
                                            </label>
                                        </div>
                                        
                                        <div class="one-food-space">
                                            <div class="kind-of-food">昼食</div>
                                            <div class="area-msg">
                                                <?php if(!empty($err_msg)) echo $err_msg['pic_lunch'] ?>
                                            </div>      
                                            <label class="js-area-drop">
                                                <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
                                                <input type="file" name="pic_lunch" class="js-input-file">
                                                <img src="<?php echo sanitize(getFormDataDailyreport('pic_lunch')); ?>" alt="" class="prev-img" style="<?php if(empty(getFormDataDailyreport('pic_lunch'))) echo 'display:none;' ?>">
                                                <span class="char-in-area-drop">ドラッグ＆ドロップ</span>
                                            </label>
                                        </div>
                                        
                                        <div class="one-food-space">
                                            <div class="kind-of-food">夕食</div>
                                            <div class="area-msg">
                                                <?php if(!empty($err_msg)) echo $err_msg['pic_dinner'] ?>
                                            </div>      
                                            <label class="js-area-drop">
                                                <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
                                                <input type="file" name="pic_dinner" class="js-input-file">
                                                <img src="<?php echo sanitize(getFormDataDailyreport('pic_dinner')); ?>" alt="" class="prev-img" style="<?php if(empty(getFormDataDailyreport('pic_dinner'))) echo 'display:none;' ?>">
                                                <span class="char-in-area-drop">ドラッグ＆ドロップ</span>
                                            </label>
                                        </div>
                                        
                                    </div>
                                </div>
                               
                            <div style="overflow:hidden;">
                                <div class="imgDrop-container">
                                    <div class="one-food-space">
                                        <div class="kind-of-food">間食１</div>
                                        <div class="area-msg">
                                            <?php if(!empty($err_msg)) echo $err_msg['pic_another_food1'] ?>
                                        </div>      
                                        <label class="js-area-drop">
                                            <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
                                            <input type="file" name="pic_another_food1" class="js-input-file">
                                            <img src="<?php echo sanitize(getFormDataDailyreport('pic_another_food1')); ?>" alt="" class="prev-img" style="<?php if(empty(getFormDataDailyreport('pic_another_food1'))) echo 'display:none;' ?>">
                                            <span class="char-in-area-drop">ドラッグ＆ドロップ</span>
                                        </label>
                                    </div>

                                    <div class="one-food-space">
                                        <div class="kind-of-food">間食２</div>
                                        <div class="area-msg">
                                            <?php if(!empty($err_msg)) echo $err_msg['pic_another_food2'] ?>
                                        </div>      
                                        <label class="js-area-drop">
                                            <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
                                            <input type="file" name="pic_another_food2" class="js-input-file">
                                            <img src="<?php echo sanitize(getFormDataDailyreport('pic_another_food2')); ?>" alt="" class="prev-img" style="<?php if(empty(getFormDataDailyreport('pic_another_food2'))) echo 'display:none;' ?>">
                                            <span class="char-in-area-drop">ドラッグ＆ドロップ</span>
                                        </label>
                                    </div>


                                    <div class="one-food-space">
                                        <div class="kind-of-food">間食３</div>
                                        <div class="area-msg">
                                            <?php if(!empty($err_msg)) echo $err_msg['pic_another_food3'] ?>
                                        </div>      
                                        <label class="js-area-drop">
                                            <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
                                            <input type="file" name="pic_another_food3" class="js-input-file">
                                            <img src="<?php echo sanitize(getFormDataDailyreport('pic_another_food3')); ?>" alt="" class="prev-img" style="<?php if(empty(getFormDataDailyreport('pic_another_food3'))) echo 'display:none;' ?>">
                                            <span class="char-in-area-drop">ドラッグ＆ドロップ</span>
                                        </label>
                                    </div>

                                </div>
                            </div>

                        </div>

                        <div class="area-msg">
                            <?php if(!empty($err_msg)) echo $err_msg['comment'] ?>
                        </div>
                        <label>
                            備考・コメント(他人に公開されます)
                            <textarea name="comment" id="js-count" cols="30" rows="10" style="height:150px;"><?php echo sanitize(getFormDataDailyreport('comment')); ?></textarea>
                        </label>

                        <div class="btn-container">
                            <input type="submit" class="btn" value="<?php echo ($today_regist_flg) ? '修正する' : '登録する' ?>">
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
