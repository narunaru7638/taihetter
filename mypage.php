<?php
//
////共通変数・関数ファイルを読込み
require('function.php');
//
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　マイページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

//ログイン認証
require('auth.php');


//================================
// 画面処理
//================================

//GETパラメータを元にユーザIDを取得。GETパラメータがなければセッションIDがユーザIDに。
if(!empty($_GET['u'])){
    $user_id = $_GET['u'];
}else{
    $user_id = $_SESSION['user_id'];
}


//DBからユーザ情報と体重情報を取得する
$dbUserAndWeightInfo = getUserAndWeightInfo($user_id);

//ユーザネームを取得
$username = $dbUserAndWeightInfo['user_data']['username'];

    ////本日の体重を算出
//date_of_mesureのみが入っている配列を作成
$array_date_of_mesure = array_column($dbUserAndWeightInfo['weight_data'], 'date_of_mesure');

//本日の日付が入ったカラムを探す
$column_today_weight = array_search(date('Y-m-d'), $array_date_of_mesure);

//本日の日付が入ったカラムがあればその中身を本日の体重として変数にいれる
if($column_today_weight > 0 || $column_today_weight === 0){
    $today_weight = $dbUserAndWeightInfo['weight_data'][$column_today_weight]['weight'];
    
    //トータル減少量を算出
    $total_reduce_weight = $dbUserAndWeightInfo['user_data']['start_weight'] - $today_weight;
    
    //本日すでに体重登録済みか判断するフラグ。未登録ならfalseで新規登録、登録済みならtrue編集へ。
    $today_regist_flg = true;
    
//本日の日付が入ったカラムがなかったときの処理
}else{
    
    //過去に体重を登録したことがあれば最新の記録を元にトータル減少量を算出
    if(!empty($dbUserAndWeightInfo['weight_data'])){
        $today_weight = $dbUserAndWeightInfo['weight_data'][0]['weight'];

        $total_reduce_weight = $dbUserAndWeightInfo['user_data']['start_weight'] - $today_weight;
            
    //過去に体重を登録したことがなければ本日の体重は50.0として仮置き、トータル減少量は０
    }else{
        $today_weight = 50.0;
        $total_reduce_weight = 0;
    }
    
    //本日すでに体重登録済みか判断するフラグ。未登録ならfalseで新規登録、登録済みならtrue編集へ。
    $today_regist_flg = false;

}  

//毎日の平均減少量を算出
$average_reduce_weight = round($total_reduce_weight * 1000 / (day_diff( $dbUserAndWeightInfo['user_data']['start_date'], date('Y-m-d')) + 1), 0);

//開始したときより体重が増えてる場合フラグを立てる
if($total_reduce_weight < 0){
    $gain_weight_flg = 1;
}else{
    $gain_weight_flg = 0;
}


//date_of_mesureのみが入っている配列を作成
$array_date_of_graph['date_of_mesure'] = array_column($dbUserAndWeightInfo['weight_data'], 'date_of_mesure');
//weightのみが入っている配列を作成
$array_date_of_graph['weight'] = array_column($dbUserAndWeightInfo['weight_data'], 'weight');


//グラフ表示用の配列を作成
$array_graph = array();

//経過日数を算出
$passed_days = day_diff( $dbUserAndWeightInfo['user_data']['start_date'], date('Y-m-d'));

//日付と体重の初期値を入れる
$array_graph['date'][0] = $dbUserAndWeightInfo['user_data']['start_date'];
$array_graph['weight'][0] = $dbUserAndWeightInfo['user_data']['start_weight'];

//経過日数分for文を回す
for($i = 0; $i < $passed_days; $i++){
    //日付＋１日した日付を次の配列に入れる
    $array_graph['date'][$i+1] = date('Y-m-d', strtotime($array_graph['date'][$i]."+1 day"));
    
    ////for文で回している日付が元の配列にあれば、そのときの体重を配列にいれる
    //for文で回している日付が元の配列にあるか確認
    $column_date_of_mesure = array_search($array_graph['date'][$i+1], $array_date_of_graph['date_of_mesure']);
    

    
    //日付が元の配列にあれば、そのときの体重を配列にいれる
    if($column_date_of_mesure > 0 || $column_date_of_mesure === 0){
        $array_graph['weight'][$i+1] = $array_date_of_graph['weight'][$column_date_of_mesure];

        
    //日付が元の配列になければ、その前のループの体重をいれる
    }else{
        $array_graph['weight'][$i+1] = $array_graph['weight'][$i];
    }

}


if(!empty($_POST)){
    debug('POST送信があります。');
    debug('POST情報:'.print_r($_POST, true));
    
    //POST情報を変数に代入
    $weight_now = (!empty ($_POST['weight_now'])) ? $_POST['weight_now'] : '';
    
    validWeight($weight_now,'weight_now');
    validRequired($weight_now, 'weight_now');
    
    if(empty($err_msg)){
        debug('バリデーションOKです。');
        try{
            $dbh = dbConnect();
            //SQL文作成(登録済みならUPDATE、新規ならINSERT)
            if($today_regist_flg){
                $sql1 = 'UPDATE weights SET weight = :weight WHERE user_id = :user_id AND date_of_mesure = :date_of_mesure AND delete_flg = 0';
                $data1 = array(':weight' => $weight_now, ':date_of_mesure' => date('Y-m-d'), ':user_id' => $user_id );

            }else{
                $sql1 = 'INSERT INTO weights (weight, date_of_mesure, user_id, create_date) VALUES(:weight, :date_of_mesure, :user_id, :create_date)';
                $data1 = array(':weight' => $weight_now, ':date_of_mesure' => date('Y-m-d'), ':user_id' => $user_id, ':create_date' => date('Y-m-d H:i:s') );
            }
            //クエリー実行
            $stmt1 = queryPost($dbh, $sql1, $data1);
            
            //体重を登録が成功したら、デイリーレポートを登録するために、weightsテーブルのIDを取得する
            if($stmt1){
                debug('体重の登録/編集が完了しました。');
                //SQL文作成(現在のユーザの今日の日付のweight情報を取得する)
                $sql2 = 'SELECT id FROM weights WHERE user_id = :user_id AND date_of_mesure = :date_of_mesure AND delete_flg = 0';
                $data2 = array(':user_id' => $_SESSION['user_id'], ':date_of_mesure' => date('Y-m-d'));
                
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
                        
                    }else{
                        $sql3 = 'INSERT INTO dailyreport (weight_id, create_date) VALUES(:weight_id, :create_date)';
                        $data3 = array(':weight_id' => $weight_id, ':create_date' => date('Y-m-d H:i:s') );
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
                    
        } catch (Exception $e) {
            error_log('エラー発生：'. $e->getMessage());
            $err_msg['common'] = MSG07;
        }
    }
    
}


debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>


<?php
$siteTitle = 'マイページ';
require('head.php'); 
?>

    <body class="page-login page-2colum">

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




                <div class="sub-nav site-width">
                    <a class="sub-nav-list sub-nav-nowview" href="mypage.php<?php echo '?u='.$user_id ?>"><?php echo sanitize(($user_id === $_SESSION['user_id']) ? 'マイ' : $username.'さんの')?>ページ</a>
                    <a class="sub-nav-list" href="indexDailyReport.php<?php echo '?u='.$user_id ?>"><?php echo sanitize(($user_id === $_SESSION['user_id']) ? '' : $username.'さんの')?>毎日の記録</a>
                </div>

                
                <?php 
                    if($user_id === $_SESSION['user_id']):
                ?>
                        <form action="" class="form form-for-mypage" method="post">
                            <h2 class="title">体重の<?php echo ($today_regist_flg) ? '修正' : '登録' ?></h2>
                            <div class="area-msg area-msg-for-mypage">
                            <?php
                                if($column_today_weight > 0 || $column_today_weight === 0):
                            ?>
                                本日の体重はすでに登録済みです。
                            <?php
                                else:
                            ?>
                                本日の体重がまだ登録されていません。
                            <?php
                                endif;
                            ?>
                                <?php if(!empty($err_msg)) echo $err_msg['weight_now'] ?>
                            </div>
                            <div class="area-of-mypage-input">
                                <?php

                                ?>

                                <label>
                                    本日の体重(単位：kg)
                                    <input class="input-follow-unit" type="number" step="0.1" name="weight_now" placeholder="50.0" value="<?php echo sanitize($today_weight) ?>">
                                </label>


                                <div class="btn-container">
                                    <input type="submit" class="btn" value="体重を<?php echo ($today_regist_flg) ? '修正' : '登録' ?>">
                                </div>
                            </div>
                            <span class="sub-message sub-message-for-mypage">食事も<?php echo ($today_regist_flg) ? '修正' : '登録' ?>する場合は<a href="resistDailyReport.php">コチラ</a></span>
                        </form>
                <?php
                    endif;
                ?>







                <div class="graph-area">
                    <canvas id="myChart"></canvas>
                </div>
                <!-- グラフ用のchart.jsの読み込み -->
                <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.1.4/Chart.min.js"></script>

                <script>
                    var ctx = document.getElementById('myChart').getContext('2d');
                    var myChart = new Chart(ctx, {
                        type: 'line',//線の種類
                        data: {
                            labels: [
                                <?php
                                    $i=0;
                                   for($i = 0; $i < count($array_graph['date']); $i++){
                                       echo " ' ".$array_graph['date'][$i]." ' ".",";
                                   }
                                ?>
                            
                            ],//横軸のラベル
                            datasets: [{
                                label: "<?php echo sanitize($dbUserAndWeightInfo['user_data']['username']) ?>の体重",
                                lineTension: 0, //グラフの曲がり度合い(デフォルトは0.5)
                                fill: false, //グラフの線より下側を塗りつぶさないようにする
                                data: [
                                    <?php
                                        $i=0;
                                        if($user_id === $_SESSION['user_id']){
                                            for($i = 0; $i < count($array_graph['weight']); $i++){
                                                echo $array_graph['weight'][$i].",";
                                            }
                                        }else{
                                            for($i = 0; $i < count($array_graph['weight']); $i++){
                                                echo $array_graph['weight'][$i]-$dbUserAndWeightInfo['user_data']['start_weight']
                                                    .",";
                                            }
                                        }
                                    ?>
                                ],　　//縦軸の値
                                borderColor: "#0066FF", //線の色
                                backgroundColor: "#0066FF" //塗りつぶしの色
                            }]
                        },
                        options: {
                            title: {
                                display: true, //タイトルの表示可否
                                text: '<?php echo sanitize($dbUserAndWeightInfo['user_data']['username']) ?>の体重推移' //タイトル
                            },
                            scales: {
                                yAxes: [{//縦軸のスケールを指定
                                    ticks: {
                                        //suggestedMax: 75,//縦軸の最大値
                                        //suggestedMin: 65,//縦軸の最小値（最小値以下の値があれば自動で変更）
                                        stepSize: 10,//縦軸の間隔
                                        callback: function(value, index, values) {
                                            return value + 'kg'
                                        }
                                    }
                                }]
                            },
                        }
                    });
                </script>


                <div class="display-your-record">
                    <p class="p-of-mypage">減少量 &emsp; &nbsp;&nbsp;：<?php echo sanitize(abs($total_reduce_weight)) ?>kg
                    <?php 
                        if($gain_weight_flg === 1){ 
                            echo '増加';
                        }else{
                            echo '減少';
                        } 
                    ?>
                    </p>
                    <p class="p-of-mypage">経過日数 &emsp;：<?php echo $passed_days ?>日</p>
                    <p class="p-of-mypage">平均減少量&nbsp;：<?php echo sanitize(abs($average_reduce_weight)) ?>g
                    <?php 
                        if($gain_weight_flg === 1){ 
                            echo '増加';
                        }else{
                            echo '減少';
                        }
                    ?>
                    /日</p>
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
