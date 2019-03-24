<?php
//
////共通変数・関数ファイルを読込み
require('function.php');
//
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　毎日の記録一覧ページ　');
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


if(!empty($_GET['u'])){
    $user_id = $_GET['u'];
}else{
    $user_id = $_SESSION['user_id'];
}

$dbUserData = getUser($user_id);

$username = $dbUserData['username'];

//表示件数
$listSpan = 10;


//現在の表示レコード先頭を算出
$currentMinNum = (($currentPageNum-1)*$listSpan);



$dbWeightAndDailyreportDataList = getWeightAndDailyreportDataList($user_id, $currentMinNum);


if($currentPageNum < 1 || $currentPageNum > intval($dbWeightAndDailyreportDataList['total_page'])){
    error_log('エラー発生：指定ページに存在しないページを指定されました');
    header("Location:mypage.php");
}


debug('現在のページ：'.$currentPageNum);


debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');

?>





<?php
$siteTitle = '毎日の記録一覧ページ';
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
                <div class="sub-nav site-width">
                    <a class="sub-nav-list" href="mypage.php<?php echo '?u='.$user_id ?>"><?php echo sanitize(($user_id === $_SESSION['user_id']) ? 'マイ' : $username.'さんの')?>ページ</a>
                    <a class="sub-nav-list sub-nav-nowview" href="indexDailyReport.php<?php echo '?u='.$user_id ?>"><?php echo sanitize(($user_id === $_SESSION['user_id']) ? '' : $username.'さんの')?>毎日の記録</a>
                </div>


                <div class="search-title">
                    <div class="search-left">
                        <span class="total-num"><?php echo $dbWeightAndDailyreportDataList['total'] ?></span>件の記録があります
                    </div>
                    <div class="search-right">
                        <?php 
                            if($dbWeightAndDailyreportDataList['total'] < $listSpan){
                                $currentMaxShow = $dbWeightAndDailyreportDataList['total'];
                            } else{
                                $currentMaxShow = $currentMinNum + $listSpan;
                            }
                        ?>
                        <span class="num"><?php echo $currentMinNum + 1 ?></span> - <span class="num"><?php echo ($currentMaxShow > $dbWeightAndDailyreportDataList['total']) ? $dbWeightAndDailyreportDataList['total'] : $currentMaxShow ?></span>件 / <span class="num"><?php echo $dbWeightAndDailyreportDataList['total'] ?></span>件中
                    </div>
                </div>
               
                <?php foreach($dbWeightAndDailyreportDataList['data'] as $key => $val): ?>
                <div class="one-dailyreport-container">
                    <div class="dailyreport-header">
                        <div class="info-dailyreport">

                            <table>
                                <thead>
                                    <tr>
                                        <td>日付</td>
                                        <td>
                                            <?php 
                                            echo
                                            sanitize(($user_id === $_SESSION['user_id']) ? '体重' : '減少量')
                                            ?>
                                        </td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        //始めたときの体重を取得
                                        $rivalUserInfo = getUser($val['user_id']);
                                        $start_weight = $rivalUserInfo['start_weight'];
                                    ?>
                                    <tr><td>
                                       <?php 
                                            echo sanitize($val['date_of_mesure']) ?>
                                        </td>
                                        <td>
                                        <?php 
                                        if($user_id === $_SESSION['user_id']){
                                            echo sanitize($val['weight']);
                                            echo 'kg';

                                        }else{
                                            echo sanitize($val['weight'] - $start_weight);
                                            echo 'kg';

                                            if(($val['weight'] - $start_weight) > 0){ 
                                                echo '増加';
                                            }else{
                                                echo '減少';
                                            }
                                }
                                        ?>
                                    
                                    </td></tr>
                                </tbody>
                            </table>

                        </div>
                        <?php if($user_id === $_SESSION['user_id']): ?>
                            <a class="" href="resistDailyReport.php?p=<?php echo $val['weight_id']; ?>">修正する</a>
                        <?php endif; ?>
                    </div>
                    <div class="food-images">
                        <label>
                           
                            <?php 
                                if(empty($val['pic_breakfast'])){
                                    $val['pic_breakfast'] = 'img/sample-img.png';
                                }
                            ?>
                            <div class="one-food-image-area">
                                朝食
                                <img src="<?php echo sanitize($val['pic_breakfast']) ?>" alt="" class="one-food-image">
                            </div>

                            <?php 
                                if(empty($val['pic_lunch'])){
                                    $val['pic_lunch'] = 'img/sample-img.png';
                                }
                            ?>
                            <div class="one-food-image-area">
                                昼食
                                <img src="<?php echo sanitize($val['pic_lunch']) ?>" alt="" class="one-food-image">
                            </div>

                            
                            <?php 
                                if(empty($val['pic_dinner'])){
                                    $val['pic_dinner'] = 'img/sample-img.png';
                                }
                            ?>
                            <div class="one-food-image-area">
                                夕食
                                <img src="<?php echo sanitize($val['pic_dinner']) ?>" alt="" class="one-food-image">
                            </div>

                            <?php 
                                if(empty($val['pic_another_food1'])){
                                    $val['pic_another_food1'] = 'img/sample-img.png';
                                }
                            ?>
                            <div class="one-food-image-area">
                                間食１
                                <img src="<?php echo sanitize($val['pic_another_food1']) ?>" alt="" class="one-food-image">
                            </div>
                            
                            <?php 
                                if(empty($val['pic_another_food2'])){
                                    $val['pic_another_food2'] = 'img/sample-img.png';
                                }
                            ?>
                            <div class="one-food-image-area">
                                間食２
                                <img src="<?php echo sanitize($val['pic_another_food2']) ?>" alt="" class="one-food-image">
                            </div>
                            
                            <?php 
                                if(empty($val['pic_another_food3'])){
                                    $val['pic_another_food3'] = 'img/sample-img.png';
                                }
                            ?>
                            <div class="one-food-image-area">
                                間食３
                                <img src="<?php echo sanitize($val['pic_another_food3']) ?>" alt="" class="one-food-image">
                            </div>
                        </label>
                        <div class="dailyreport-comment">
                            <div>備考・コメント</div>
                            <p><?php echo sanitize($val['comment']) ?></p>
                        </div>
                    </div>

                </div>

                <?php 
                    endforeach;
                ?>

                <div class="pagenationArea">
                    <ul class="pagenation">
                       <?php
                        $pageColNum = 5;
                        $totalPageNum = intval($dbWeightAndDailyreportDataList['total_page']);

                        if($currentPageNum == $totalPageNum && $totalPageNum >= $pageColNum){
                            $minPageNum = $currentPageNum - 4;
                            $maxPageNum = $currentPageNum;
                        }elseif($currentPageNum == ($totalPageNum -1) && ($totalPageNum >= $pageColNum)){
                            $minPageNum = $currentPageNum -3;
                            $maxPageNum = $currentPageNum + 1;
                        }elseif($currentPageNum == 2 && $totalPageNum >= $pageColNum){
                            $minPageNum = $currentPageNum - 1;
                            $maxPageNum = $currentPageNum + 3;
                        }elseif($currentPageNum == 1 && $totalPageNum >= $pageColNum){
                            $minPageNum = $currentPageNum;
                            $maxPageNum = $currentPageNum + 4;
                        }elseif($totalPageNum < $pageColNum){
                            $minPageNum = 1;
                            $maxPageNum = $totalPageNum;
                        }else{
                            $minPageNum = $currentPageNum - 2;
                            $maxPageNum = $currentPageNum + 2;
                            debug('111111111');
                        }
                       ?>
                       <?php if($currentPageNum != 1): ?>
                            <li class="Page"><a href="<?php echo '?u='.$user_id ?>&p=1">&lt;</a></li>
                        <?php endif;  ?>
                       
                        <?php 
                            for($i = $minPageNum; $i <= $maxPageNum; $i++):
                        ?>
                        <li class="Page <?php if($i == $currentPageNum) echo currentPage ?>"><a href="
                        <?php echo '?u='.$user_id ?>&p=<?php echo $i; 
                        ?>"><?php 
                            echo $i 
                        ?>
                        </a></li>
                        <?php
                            endfor;
                        ?>
                        
                       <?php if($currentPageNum != $maxPageNum): ?>
                        <li class="Page"><a href="<?php echo '?u='.$user_id ?>&p=<?php echo $maxPageNum; ?>">&gt;</a></li>
                       <?php endif;  ?>

                        
                    </ul>
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

