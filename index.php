<?php
//
////共通変数・関数ファイルを読込み
require('function.php');
//
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　タイムライン　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');

debugLogStart();





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



$dbWeightAndDailyreportDataListAll = getWeightAndDailyreportDataListAll($currentMinNum);


if($currentPageNum < 1 || $currentPageNum > intval($dbWeightAndDailyreportDataListAll['total_page'])){
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

<body class="page-login page-1colum">

    <!-- ヘッダー -->
    <?php
    require('header.php'); 
    ?>

        <!-- メインコンテンツ -->
        <div id="contents" class="site-width">

            <!-- Main -->
            <section id="main" >
                <h2 class="title">タイムライン</h2>
                <div class="search-title">
                    <div class="search-left">
                        <span class="total-num"><?php echo $dbWeightAndDailyreportDataListAll['total'] ?></span>件の記録があります
                    </div>
                    <div class="search-right">
                        <?php 
                            if($dbWeightAndDailyreportDataListAll['total'] < $listSpan){
                                $currentMaxShow = $dbWeightAndDailyreportDataListAll['total'];
                            } else{
                                $currentMaxShow = $currentMinNum + $listSpan;
                            }
                        ?>
                        <span class="num"><?php echo $currentMinNum + 1 ?></span> - <span class="num"><?php echo ($currentMaxShow > $dbWeightAndDailyreportDataListAll['total']) ? $dbWeightAndDailyreportDataListAll['total'] : $currentMaxShow ?></span>件 / <span class="num"><?php echo $dbWeightAndDailyreportDataListAll['total'] ?></span>件中
                    </div>
                </div>
               
                
                <?php foreach($dbWeightAndDailyreportDataListAll['data'] as $key => $val): ?>
                <div class="one-dailyreport-container">
                    <div class="dailyreport-header">
                        
                        <div class="info-dailyreport">
                            <div class = "user-icon">
                                <img src="<?php echo sanitize($val['pic']) ?>" alt="">
                            </div>
  
                            <table>
                                <thead>
                                    <tr><td>ユーザ名</td><td>日付</td><td>体重</td></tr>
                                </thead>
                                <tbody>
                                    <tr><td><?php echo sanitize($val['username']) ?></td><td><?php echo sanitize($val['date_of_mesure']) ?></td><td><?php echo sanitize($val['weight']) ?>kg</td></tr>
                                </tbody>
                            </table>

                        </div>
                        <?php
                            if($val['user_id'] !== $_SESSION['user_id'] && !empty($_SESSION['user_id'])):
                        ?>
                        <i name="<?php echo icon.$val['user_id'] ?>" class="fas fa-thumbs-up icn-rival js-click-rival  <?php if(isRival($_SESSION['user_id'], $val['user_id'])){ echo 'active'; } ?>" aria-hidden="true" data-rivalid="<?php echo sanitize($val['user_id']); ?>" >
                        </i>
                        <?php
                            endif;
                        ?>
                    </div>
                    <div class="food-images">
                        <label>
                            <div class="one-food-image-area">
                                朝食
                                <img src="<?php echo sanitize(showNoImg($val['pic_breakfast'])) ?>" alt="" class="one-food-image">
                            </div>

                            <div class="one-food-image-area">
                                昼食
                                <img src="<?php echo sanitize(showNoImg($val['pic_lunch'])) ?>" alt="" class="one-food-image">
                            </div>

                            <div class="one-food-image-area">
                                夕食
                                <img src="<?php echo sanitize(showNoImg($val['pic_dinner'])) ?>" alt="" class="one-food-image">
                            </div>

                            <div class="one-food-image-area">
                                間食１
                                <img src="<?php echo sanitize(showNoImg($val['pic_another_food1'])) ?>" alt="" class="one-food-image">
                            </div>

                            <div class="one-food-image-area">
                                間食２
                                <img src="<?php echo sanitize(showNoImg($val['pic_another_food2'])) ?>" alt="" class="one-food-image">

                            </div>

                            <div class="one-food-image-area">
                                間食３
                                <img src="<?php echo sanitize(showNoImg($val['pic_another_food3'])) ?>" alt="" class="one-food-image">
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

                <?php
                $pageColNum = 5;
                $totalPageNum = $dbWeightAndDailyreportDataListAll['total_page'];
                
                if($currentPageNum == $totalPageNum && $totalPageNum >= $pageColNum){
                    $minPageNum = $currentPageNum - 4;
                    $maxPageNum = $currentPageNum;
                }elseif($currentPageNum == $totalPageNum - 1 && $totalPageNum >= $pageColNum){
                    $minPageNum = $currentPageNum - 3;
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
                }
                    
                
                ?>
                   
                <div class="pagenationArea">
                    <ul class="pagenation">
                       
                        <?php if($currentPageNum != 1): ?>
                            <li class="Page">
                                <a href="?p=1">&lt;</a>
                            </li>
                        <?php endif; ?>

                        <?php 
                            for($i = $minPageNum; $i <= $maxPageNum; $i++):
                        ?>
                                <li class="Page <?php if($i == $currentPageNum) echo currentPage ?>">
                                    <a href="?p=<?php echo $i ?>"><?php echo $i ?></a>
                                </li>
                        <?php
                            endfor;
                        ?>

                       
                        
                                                                        
                        <?php if($currentPageNum != $dbWeightAndDailyreportDataListAll['total_page']): ?>
                            <li class="Page">
                                <a href="?p=<?php echo $totalPageNum ?>">&gt;</a>
                            </li>
                        <?php endif; ?>


                    </ul>
                    
                </div>

                

            </section>


        </div>

    <!-- footer -->
    <?php
    require('footer.php'); 
    ?>
