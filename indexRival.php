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

$currentPageNum = (!empty($_GET['p'])) ? $_GET['p'] : 1;
if( !is_int((int)$currentPageNum)){
    error_log('エラー発生：指定ページに不正な値が入りました');
    header("Location:mypage.php");
}


//表示件数
$listSpan = 10;


//現在の表示レコード先頭を算出
$currentMinNum = (($currentPageNum-1)*$listSpan);

$dbRivalId = getRivalId($_SESSION['user_id']);


for( $i = 0; $i < count($dbRivalId); $i++){
    $rivalRecentInfoAll[$i] = getRivalRecentInfo($dbRivalId[$i]['rival_user_id']);
}




debug('現在のページ：'.$currentPageNum);

debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>


<?php
$siteTitle = 'ライバル一覧';
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
                <h2 class="title">ライバル一覧</h2>

    
                <?php for( $i = 0; $i < count($dbRivalId); $i++): ?>

                
                <div class="one-dailyreport-container">
                    <div class="dailyreport-header">
                        <img class="icon" src="<?php echo sanitize(showNoImg($rivalRecentInfoAll[$i]['pic'])) ?>" alt="">
                        
                        
                        <div class="info-dailyreport">

                            <table>
                                <thead>
                                    <tr><td>ニックネーム</td><td>日付</td><td>減少量</td><td>経過日数</td></tr>
                                </thead>
                                
                                <?php
                                    $rivalUserInfo = getUser($dbRivalId[$i]['rival_user_id']);
                                    $start_weight = $rivalUserInfo['start_weight'];
                                    //経過日数を算出
                                    $passed_days = day_diff( $rivalUserInfo['start_date'], date('Y-m-d'));
                                ?>
                                
                                <tbody>
                                    <tr><td><a href="mypage.php<?php echo '?u='.$rivalRecentInfoAll[$i]['user_id'] ?>"><?php echo sanitize($rivalRecentInfoAll[$i]['username']) ?></a></td><td><?php echo sanitize($rivalRecentInfoAll[$i]['date_of_mesure']) ?></td><td><?php echo sanitize(abs($rivalRecentInfoAll[$i]['weight'] - $start_weight)) ?>kg
                                    <?php 
                                        if(($rivalRecentInfoAll[$i]['weight'] - $start_weight) > 0){ 
                                            echo '増加';
                                        }else{
                                            echo '減少';
                                        }
                                        ?></td><td><?php echo $passed_days ?>日</td></tr>
                                </tbody>
                            </table>

                        </div>
                        
                        <i name="<?php echo icon.$rivalRecentInfoAll[$i]['user_id'] ?>" class="rival-index fas fa-thumbs-up icn-rival js-click-rival  <?php if(isRival($_SESSION['user_id'], $rivalRecentInfoAll[$i]['user_id'])){ echo 'active'; } ?>" aria-hidden="true" data-rivalid="<?php echo sanitize($rivalRecentInfoAll[$i]['user_id']); ?>" >
                        </i>
                        
                    </div>
                    <div class="food-images">
                        <label>
                            <div class="one-food-image-area">
                                朝食
                                <img src="<?php echo sanitize(showNoImg($rivalRecentInfoAll[$i]['pic_breakfast'])) ?>" alt="" class="one-food-image">
                            </div>

                            <div class="one-food-image-area">
                                昼食
                                <img src="<?php echo sanitize(showNoImg($rivalRecentInfoAll[$i]['pic_lunch'])) ?>" alt="" class="one-food-image">
                            </div>

                            <div class="one-food-image-area">
                                夕食
                                <img src="<?php echo sanitize(showNoImg($rivalRecentInfoAll[$i]['pic_dinner'])) ?>" alt="" class="one-food-image">
                            </div>
                            
                            <div class="one-food-image-area">
                                間食１
                                <img src="<?php echo sanitize(showNoImg($rivalRecentInfoAll[$i]['pic_another_food1'])) ?>" alt="" class="one-food-image">
                            </div>

                            <div class="one-food-image-area">
                                間食２
                                <img src="<?php echo sanitize(showNoImg($rivalRecentInfoAll[$i]['pic_another_food2'])) ?>" alt="" class="one-food-image">
                            </div>

                            <div class="one-food-image-area">
                                間食３
                                <img src="<?php echo sanitize(showNoImg($rivalRecentInfoAll[$i]['pic_another_food3'])) ?>" alt="" class="one-food-image">
                            </div>

                        </label>
                        <div class="dailyreport-comment">
                            <div>備考・コメント</div>
                            <p><?php echo sanitize($rivalRecentInfoAll[$i]['comment']) ?></p>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>


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
