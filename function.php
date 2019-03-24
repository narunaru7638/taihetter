<?php
//================================
// ログ
//================================
//ログを取るか
ini_set('log_errors','on');
//ログの出力ファイルを指定
ini_set('error_log','php.log');

//================================
// デバッグ
//================================
//デバッグフラグ
$debug_flg = true;
//デバッグログ関数
function debug($str){
    global $debug_flg;
    if(!empty($debug_flg)){
        error_log('デバッグ：'.$str);
    }
}

//================================
// セッション準備・セッション有効期限を延ばす
//================================
//セッションファイルの置き場を変更する（/var/tmp/以下に置くと30日は削除されない）
session_save_path("/var/tmp/");
//ガーベージコレクションが削除するセッションの有効期限を設定（30日以上経っているものに対してだけ１００分の１の確率で削除）
ini_set('session.gc_maxlifetime', 60*60*24*30);
//ブラウザを閉じても削除されないようにクッキー自体の有効期限を延ばす
ini_set('session.cookie_lifetime', 60*60*24*30);
//セッションを使う
session_start();
//現在のセッションIDを新しく生成したものと置き換える（なりすましのセキュリティ対策）
session_regenerate_id();

//================================
// 画面表示処理開始ログ吐き出し関数
//================================
function debugLogStart(){
    debug('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> 画面表示処理開始');
    debug('セッションID：'.session_id());
    debug('セッション変数の中身：'.print_r($_SESSION,true));
    debug('現在日時タイムスタンプ：'.time());
    if(!empty($_SESSION['login_date']) && !empty($_SESSION['login_limit'])){
        debug( 'ログイン期限日時タイムスタンプ：'.( $_SESSION['login_date'] + $_SESSION['login_limit'] ) );
    }
}

//================================
// 定数
//================================
//エラーメッセージを定数に設定
define('MSG01','エラーが発生しました。しばらく経ってからやり直してください。');
define('MSG02','値が未入力です。');
define('MSG03','255文字以下で入力してください。');
define('MSG04','8文字以上で入力してください。');
define('MSG05','半角英数記号で入力してください。');
define('MSG06','本サービスは人間向けサービスです。体重は0〜1000kg の範囲で登録してください。');
define('MSG07','エラーが発生しました。しばらく経ってからやり直してください。');
define('MSG08','すでに登録済みのユーザーがいます。');
define('MSG09','パスワード(再入力)が合っていません。');
define('MSG10','アドレスかパスワードが合っていません。');
define('MSG11', '古いパスワードが違います。');
define('MSG12', '古いパスワードと同じです。');
define('MSG13', 'メールの形式で入力してください。');
define('MSG14','すでに登録されているアドレスです。');
define('MSG15','認証キーが一致していません。');
define('MSG16','認証キーの有効期限が切れています。');
define('MSG17','文字で入力してください。');
define('MSG18','エラーが発生しました。しばらく経ってから認証キーの作成からやり直してください。以前のパスワードはすでに使用できません。');
define('MSG19','ゲストユーザーのアドレスのためこの機能は利用できません。');
define('SUC01','プロフィール編集が完了しました。');
define('SUC02','パスワード編集が完了しました。');
define('SUC03','メール送信が完了しました。');
define('SUC04','本日の体重と食事の登録が完了しました。');
define('SUC05','体重と食事の修正が完了しました。');
define('SUC06','処理に成功しました。（ゲストユーザーのため反映はされていません。）');

//ゲストユーザーのIDとemailを定義
$gestUserId = 28;
$gestUserEmail = 'gestuser@mail.com';

//エラーメッセージを格納する配列を用意
$err_msg = array();

//================================
// データベース
//================================
//DB接続関数
function dbConnect(){
    //DBへの接続準備
    $dsn = 'mysql:dbname=taihetter;host=localhost;charset=utf8';
    $user = 'root';
    $password = 'root';
    $options = array(
        // SQL実行失敗時にはエラーコードのみ設定
        PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
        // デフォルトフェッチモードを連想配列形式に設定
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // バッファードクエリを使う(一度に結果セットをすべて取得し、サーバー負荷を軽減)
        // SELECTで得た結果に対してもrowCountメソッドを使えるようにする
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    );
    // PDOオブジェクト生成（DBへ接続）
    $dbh = new PDO($dsn, $user, $password, $options);
    return $dbh;
}


//SQL実行関数
//クエリー結果をtrue or falseで返す
function queryPost($dbh, $sql, $data){
    //クエリー作成
    $stmt = $dbh->prepare($sql);
    
    
    //プレースホルダに値をセットし、SQL文を実行
    if(!$stmt->execute($data)){
        debug('クエリに失敗しました。');
        debug('失敗したSQL：'.print_r($stmt,true));
        $err_msg['common'] = MSG01;
        return 0;
    }
    debug('クエリ成功。');
    return $stmt;
}

//================================
// バリデーション
//================================
//未入力チェック
function validRequired($str, $key){
    if(empty($str)){
        global $err_msg;
        $err_msg[$key] = MSG02;
    }
}

//最大文字数チェック
function validMaxLen($str, $key, $max = 255){
    if(mb_strlen($str) > $max){
        global $err_msg;
        $err_msg[$key] = MSG03;
    }
}

//最小文字数チェック
function validMinLen($str, $key, $min = 8){
    if(mb_strlen($str) < $min){
        global $err_msg;
        $err_msg[$key] = MSG04;
    }
}

//半角チェック
function validHalf($str, $key){
    if (!preg_match("/^[!-~]+$/", $str)) {
        global $err_msg;
        $err_msg[$key] = MSG05;
    }
}

//最大および最小体重チェック
function validWeight($str, $key){
    if ($str <= 0 || $str >= 1000) {
        global $err_msg;
        $err_msg[$key] = MSG06;
    }
}

//バリデーション関数（Email形式チェック）
function validEmail($str, $key){
    if(!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $str)){
        global $err_msg;
        $err_msg[$key] = MSG13;
    }
}

//固定長チェック
function validLength($str, $key, $leng = 8){
    if(mb_strlen($str) === $keng){
        global $err_msg;
        $err_msg[$key] = $leng . MSG17;
    }
}

//emailの重複チェック
function validEmailDup($str){
    global $err_msg;
    try {
        $dbh = dbConnect();
        $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
        $data = array(':email' => $str);
        $stmt = queryPost($dbh, $sql, $data);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!empty(array_shift($result))){
            $err_msg['email'] = MSG14;
        }
    } catch (Exception $e) {
        error_log('エラー発生:' . $e->getMessage());
        $err_msg['email'] = MSG07;
    }
}

//usernameの重複がないか確認
function validUsernameDup($str){
    global $err_msg;
    try {
        $dbh = dbConnect();
        $sql = 'SELECT COUNT(*) FROM users WHERE username = :username';
        $data = array(':username' => $str);
        $stmt = queryPost($dbh, $sql, $data);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!empty(array_shift($result))){
            $err_msg['username'] = MSG08;
        }
    } catch (Exception $e) {
        error_log('エラー発生:' . $e->getMessage());
        $err_msg['username'] = MSG07;
    }
}

//パスワードが一致しているかチェック
function validMatch($str1, $str2, $key){
    if($str1 !== $str2){
        global $err_msg;
        $err_msg[$key] = MSG09;
    }
}

//パスワードチェック
function validPass($str, $key){
    //半角英数字チェック
    validHalf($str, $key);
    //最大文字数チェック
    validMaxLen($str, $key);
    //最小文字数チェック
    validMinLen($str, $key);

}

//ゲストユーザーのEmailかチェック
function validGestUserEmail($str, $key){
    global $gestUserEmail;
    if($gestUserEmail == $str){
        global $err_msg;
        $err_msg[$key] = MSG19;
        debug('ゲストユーザのアドレスが入力されました。');
    }
}

//================================
// 情報取得
//================================

//ユーザIDからユーザ情報を取得
function getUser($user_id){
    debug(' ユーザ情報を取得します。 ');
    try{

        //DBへ接続
        $dbh = dbConnect();
        //SQL文作成
        $sql = 'SELECT * FROM users WHERE id = :user_id AND delete_flg = 0';
        $data = array(':user_id' => $user_id);

        //クエリ実行
        $stmt = queryPost($dbh, $sql, $data);


        if($stmt){
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }else{
            return false;
        }


    }catch (Exception $e) {
        error_log('エラー発生：' . $e->getMessage());
    }

}

//ユーザIDと日付から体重を取得
function getWeight($user_id, $date){
    debug('体重情報を取得します。');
    try{
        //DBへ接続
        $dbh = dbConnect();
        //SQL文作成
        $sql = 'SELECT * FROM weights WHERE user_id = :user_id AND date_of_mesure = :date_of_mesure AND delete_flg =0';
        $data = array(':user_id' => $user_id, ':date_of_mesure' => $date);
        
        $stmt = queryPost($dbh, $sql, $data);
        
        if($stmt){
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }else{
            return false;
        }
        
    }catch (Exception $e) {
        error_log('エラー発生：' . $e->getMessage());
    }
}

function getWeightById($weight_id){
    debug('IDを使って体重情報を取得します。');
    try{
        //DBへ接続
        $dbh = dbConnect();
        //SQL文作成
        $sql = 'SELECT * FROM weights WHERE id = :weight_id AND delete_flg =0';
        $data = array(':weight_id' => $weight_id);

        $stmt = queryPost($dbh, $sql, $data);

        if($stmt){
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }else{
            return false;
        }

    }catch (Exception $e) {
        error_log('エラー発生：' . $e->getMessage());
    }
}

//weight_idからデイリーレポートを取得
function getDailyreport($weight_id){
    debug('デイリーレポート情報を取得します。');
    try{
        //DBへ接続
        $dbh =dbConnect();
        //SQL文作成
        $sql = 'SELECT * FROM dailyreport WHERE weight_id = :weight_id AND delete_flg = 0';
        $data = array(':weight_id' => $weight_id);
        $stmt = queryPost($dbh, $sql, $data);
        
        if($stmt){
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }else{
            return false;
        }
    }catch (Exception $e) {
        error_log('エラー発生：' . $e->getMessage());
    }
    
}



////一覧表示用の体重データを取得
//function getWeightDataList($user_id, $currentMinNum = 0, $span =10){
//    debug('一覧表示用の体重情報を取得します。');
//    try{
//        $dbh = dbConnect();
//        $sql = 'SELECT id FROM weights WHERE user_id = :user_id AND delete_flg = 0';
//        $data = array(':user_id' => $user_id);
//        $stmt = queryPost($dbh, $sql, $data);
//        $rst['total'] = $stmt->rowCount();//総レコード数
//
//        $rst['total_page'] = ceil($rst['total']/$span);//総ページ数
//        
//        if(!stmt){
//            return false;
//        }
//        
////        $sql = 'SELECT * FROM weights WHERE user_id = :user_id AND delete_flg = 0 LIMIT :span OFFSET :currentMinNum';
////        $data = array(':user_id' => $user_id, ':span' => $span, ':currentMinNum' => $currentMinNum);
//        
////        $sql = 'SELECT * FROM weights WHERE user_id = :user_id AND delete_flg = 0';
////        $sql .= ' LIMIT '.$span.' OFFSET '.$currentMinNum;
//        $sql = 'SELECT * FROM weights WHERE user_id = :user_id AND delete_flg = 0 LIMIT :span OFFSET :currentMinNum';
//
//        //クエリー作成
//        $stmt = $dbh->prepare($sql);
//
//        $stmt->bindValue(':user_id', $user_id);
//        $stmt->bindValue(':span', (int)$span, PDO::PARAM_INT);
//        $stmt->bindValue(':currentMinNum', (int)$currentMinNum, PDO::PARAM_INT);
//
//        debug('SQL内容:'.$sql);
//
//
//        
//        //プレースホルダに値をセットし、SQL文を実行
//        if(!$stmt->execute()){
//            debug('クエリに失敗しました。');
//            debug('失敗したSQL：'.print_r($stmt,true));
//            $err_msg['common'] = MSG01;
//        }
//        debug('クエリ成功。');
//        debug('成功したSQL：'.print_r($stmt,true));
//
//
//        //$stmt = queryPost($dbh, $sql, $data);
//        
//        
//        if($stmt){
//            $rst['data'] = $stmt->fetchAll();
//            return $rst;
//            
//        }else{
//            return false;
//        }
//        
//    } catch (Exception $e) {
//        error_log('エラー発生：' . $e->getMessage());
//    }
//    
//}

//一覧表示用のデイリーレポートデータを取得
function getDailyreportDataList($weightDataList, $currentMinNum = 0, $span =10){
    debug('一覧表示用の体重情報を取得します。');
    //var_dump($weightDataList);
    //var_dump($weightDataList[0]['id']);
    try{
        $dbh = dbConnect();
        for($i = 0; $i < count($weightDataList); $i++){
            //var_dump($i);
            $sql = 'SELECT * FROM dailyreport WHERE weight_id = :weight_id AND delete_flg = 0';
            $data = array(':weight_id' => $weightDataList[$i]['id']);
            debug('SQL内容:'.$sql);
            $stmt = queryPost($dbh, $sql, $data);
            if($stmt){
                $rst[$i] = $stmt->fetchAll();
//                $rst = $stmt->fetchAll();

            }else{
                $rst[$i] ='';
//                $rst ='';
            }
            
        }
        return $rst;
        
    }catch(Exception $e){
        error_log('エラー発生：' . $e->getMessage());
    }
        
}

//特定ユーザの体重とデイリーレポートをすべて取得する
function getWeightAndDailyreportDataList($user_id, $currentMinNum = 0, $span =10){
    debug('一覧表示用の体重と日報情報を取得します。');
    try{
        $dbh = dbConnect();
        $sql = 'SELECT * FROM weights AS w RIGHT JOIN dailyreport AS d ON w.id = d.weight_id WHERE w.user_id = :user_id AND w.delete_flg = 0 AND d.delete_flg = 0';
        $data = array(':user_id' => $user_id);
        $stmt = queryPost($dbh, $sql, $data);
        $rst['total'] = $stmt->rowCount();//総レコード数

        $rst['total_page'] = ceil($rst['total']/$span);//総ページ数

        if(!stmt){
            return false;
        }
        
        $sql .= ' ORDER BY w.date_of_mesure DESC LIMIT :span OFFSET :currentMinNum';

        //クエリー作成
        $stmt = $dbh->prepare($sql);

        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':span', (int)$span, PDO::PARAM_INT);
        $stmt->bindValue(':currentMinNum', (int)$currentMinNum, PDO::PARAM_INT);

        debug('SQL内容:'.$sql);

        //プレースホルダに値をセットし、SQL文を実行
        if(!$stmt->execute()){
            debug('クエリに失敗しました。');
            debug('失敗したSQL：'.print_r($stmt,true));
            $err_msg['common'] = MSG01;
        }
        debug('クエリ成功。');
        debug('成功したSQL：'.print_r($stmt,true));

        
        
        
        
//        $data = array(':user_id' => $user_id);

//        debug('SQL内容:'.$sql);
//        $stmt = queryPost($dbh, $sql, $data);


        if($stmt){
            $rst['data'] = $stmt->fetchAll();
            return $rst;

        }else{
            return false;
        }

    } catch (Exception $e) {
        error_log('エラー発生：' . $e->getMessage());
    }
}

//すべてのユーザの体重とデイリーレポートをすべて取得する
function getWeightAndDailyreportDataListAll($currentMinNum = 0, $span =10){
    debug('一覧表示用にすべてのユーザの体重と日報情報を取得します。');
    try{
        $dbh = dbConnect();
        $sql = 'SELECT * FROM weights AS w LEFT JOIN dailyreport AS d ON w.id = d.weight_id LEFT JOIN users AS u ON u.id = w.user_id WHERE w.delete_flg = 0 AND d.delete_flg = 0 AND u.delete_flg = 0';
        $data = array();
        $stmt = queryPost($dbh, $sql, $data);
        $rst['total'] = $stmt->rowCount();//総レコード数

        $rst['total_page'] = ceil($rst['total']/$span);//総ページ数

        if(!stmt){
            return false;
        }

//        $sql .= ' ORDER BY w.date_of_mesure DESC LIMIT '.$span.' OFFSET '.$currentMinNum;
        $sql .= ' ORDER BY w.date_of_mesure DESC LIMIT :span OFFSET :currentMinNum';

        //クエリー作成
        $stmt = $dbh->prepare($sql);

        $stmt->bindValue(':span', (int)$span, PDO::PARAM_INT);
        $stmt->bindValue(':currentMinNum', (int)$currentMinNum, PDO::PARAM_INT);

        debug('SQL内容:'.$sql);
        $stmt->execute();
        
        if($stmt){
            $rst['data'] = $stmt->fetchAll();
            return $rst;

        }else{
            return false;
        }

    } catch (Exception $e) {
        error_log('エラー発生：' . $e->getMessage());
    }
}

function getRivalId($user_id){
    
    debug('すべてのライバルのIDを取得します。');
    try{
        $dbh = dbConnect();      
        $sql = 'SELECT rival_user_id FROM rival WHERE user_id = :user_id AND delete_flg = 0';
        $data = array(':user_id' => $user_id);
        $stmt = queryPost($dbh, $sql, $data);

        if($stmt){
            return $stmt->fetchAll();

        }else{
            return false;
        }

    }catch (Exception $e) {
        error_log('エラー発生：' . $e->getMessage());
    }
    
}

//一人のライバルユーザの情報を取得する
function getRivalRecentInfo($rival_user_id){
    debug('ライバルの最新情報を取得します。');
    try{
        $dbh = dbConnect();        
        $sql = 'SELECT * FROM weights AS w LEFT JOIN dailyreport AS d ON w.id = d.weight_id LEFT JOIN users AS u ON u.id = w.user_id WHERE w.user_id = :rival_user_id AND w.delete_flg = 0 AND d.delete_flg = 0 AND u.delete_flg = 0 ORDER BY w.date_of_mesure DESC';
        $data = array(':rival_user_id' => $rival_user_id);
        $stmt = queryPost($dbh, $sql, $data);

        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($result){
            return $result;
        }else{
            return false;
        }

    } catch (Exception $e) {
        error_log('エラー発生：' . $e->getMessage());
    }
}


//ライバルであるユーザの情報
function isRival($user_id, $rival_user_id){
    debug('ライバルユーザー情報があるか確認します。');
    debug('ユーザID:'.$user_id);
    debug('ライバルユーザID:'.$rival_user_id);

    try {
        
        $dbh = dbConnect();
        $sql = 'SELECT * FROM rival WHERE user_id = :user_id AND rival_user_id = :rival_user_id';
        $data = array(':user_id' => $user_id, ':rival_user_id' => $rival_user_id);
        $stmt = queryPost($dbh, $sql, $data);
        
        if($stmt->rowCount()){
            debug('ライバルのユーザーです');
            return true;
        }else{
            debug('特にライバルのユーザーではありません');
            return false;
        }
        
        
    } catch ( Exception $e ){
        error_log('エラー発生：' . $e->getMessage());
    }
}

//マイページ表示用のユーザ情報を取得する
function getUserAndWeightInfo($user_id){
    debug('マイページ用のユーザ情報を取得します。');
    debug('ユーザID:'.$user_id);

    try {

        $dbh = dbConnect();
        $sql = 'SELECT * FROM users WHERE id = :user_id AND delete_flg = 0';
        $data = array(':user_id' => $user_id);
        $stmt = queryPost($dbh, $sql, $data);

        if($stmt){
            debug('ユーザ情報が取得できました');
//            $rst['user_data'] = $stmt->fetchAll(); 
            $rst['user_data'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }else{
            debug('ユーザ情報が取得できませんでした');
            return false;
        }

        $sql = 'SELECT * FROM weights WHERE user_id = :user_id ORDER BY date_of_mesure DESC';
        
        $data = array(':user_id' => $user_id);
        $stmt = queryPost($dbh, $sql, $data);
        
        if($stmt){
            debug('体重情報が取得できました');
            $rst['weight_data'] = $stmt->fetchAll();
            return $rst;
        }else{
            debug('体重情報が取得できませんでした');
            return false;
        }

    } catch ( Exception $e ){
        error_log('エラー発生：' . $e->getMessage());
    }
}

//================================
// メール送信
//================================
function sendMail($from, $to, $subject, $comment){
    if(!empty($to) && !empty($subject) && !empty($comment)){
        //文字化けしないように設定(お決まりパターン)
        mb_language('Japanese');//現在使っている言語を設定する
        mb_internal_encoding("UTF-8");//内部の日本語をどうエンコーディング(機械が分かる言葉へ変換)するかを設定

        //メールを送信(送信結果はtrueかfalseで返ってくる)
        $result = mb_send_mail($to, $subject, $comment, "From: ".$from);
        //送信結果を判定
        if($result) {
            debug('メールを送信しました。');
        } else {
            debug('【エラー発生】メールの送信に失敗しました。');
        }
                
        return $result;
    }
}



//================================
// その他
//================================


//画像アップロード
function uploadImg($file, $key){
    debug('画像アップロード開始');
    debug('FILE情報：'.print_r($file,true));

    //エラーがはいっているかを確認かつエラーに数字が入っているかを確認
    if (isset($file['error']) && is_int($file['error'])) {
        try{
            //バリデーション
            //$file['error']の値を確認（中身はUPLOAD＿ERR_OKなどの定数）
            //定数はPHPでアップロード時に自動的に定義
            //定数には値として、0,1などの数値が入っている
            //数値で分岐でもよいが、このような定数を使うとなんのエラーでの分岐なのかわかりやすい
            switch ($file['error']){
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new RuntimeException('ファイルが選択されていません');
                case UPLOAD_ERR_INI_SIZE:
                    //php.ini のupload_max_filesize,post_max_size,memory_limitを超えるとエラー
                    throw new RuntimeException('ファイルサイズが大きすぎます');
                case UPLOAD_ERR_FORM_SIZE:
                    //フォーム定義のMAX_FILE_SIZEを超えていた場合
                    throw new RuntimeException('ファイルサイズが大きすぎます');
                default:
                    throw new RuntimeException('その他のエラーが発生しました');
            }


            //$file['mime']の値はブラウザ側で偽装可能なので、MIMEタイプを自前でチェックする
            //exif_imagetype関数は「IMAGETYPE_GIF」などの定数を返す
            //定数「IMAGETYPE_GIF」は値「１」、定数「IMAGETYPE_JPEG」は値「2」、定数「IMAGETYPE_PNG」は値「3」
            //exif_imagetype関数は使うときには必ず@をつける
            //exif_imagetype関数はエラーが出るときがあるが、@をつけると処理が止まらず、無視して進む
            $type = @exif_imagetype($file['tmp_name']);


            //指定する配列(第一引数)の中に、第二引数の値とマッチするものがあるかを確認する
            //第三引数にはtrueを設定すると厳密にチェックしてくれるので必ずつける
            //IMAGETYPE_GIFはPHPで用意される定数
            if (!in_array($type, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
                throw new RuntimeException(' 画像形式が未対応です ');
            }

            //ファイルデータからSHA-1ハッシュを取って、ファイル名を決定しファイルを保存する
            //ハッシュ化しておかないとアップロードされたファイル名そのままで保存してしまうと同じファイル名がアップロードされる可能性があり、
            //DBにパスを保存した場合、どっちの画像のパスなのか判断つかなくなる
            //image_type_to_extension関数はファイルの拡張子を取得する
            $path = 'uploads/'.sha1_file($file['tmp_name']).image_type_to_extension($type);

            if (!move_uploaded_file($file['tmp_name'], $path)) {
                throw new RuntimeExeption('ファイル保存時にエラーが発生しました');
            }

            //指定されたファイルのモードを mode で指定したものに変更しようと試みます。
            //保存したファイルパスのパーミッションを変更
            //所有者自身、所有者が属するグループ、その他のユーザーの順で アクセス制限を設定
            //1 は実行権限、2 はファイルに対する書き込み権限、 4 はファイルに対する読み込み権限
            chmod($path, 0644);

            debug('ファイルは正常にアップロードされました');
            debug('ファイルパス：'.$path);
            return $path;
        } catch (RuntimeException $e){
            debug($e->getMessage());
            global $err_msg;
            $err_msg[$key] = $e->getMessage();
        }
    }
}

//画像がないときはサンプル画像を表示する画像
function showNoImg($str){
    if(empty($str)){
        $str = 'img/sample-img.png';
    }
    return $str;
}

//フォーム入力保持( usersテーブルのデータ用 )
function getFormData($str){
    global $user_info;
    //ユーザデータがある場合
    if(!empty($user_info)){
        // フォームのエラーがある場合
        if(!empty($err_msg[$str])){
            // POSTにデータがある場合
            if(isset($err_msg[$str])){
                return $_POST[$str];
            }else{
                //ない場合（フォームにエラーがある時点で、POSTにデータがあるはずなので、まずありえない状況だが）は、DBの情報を開示
                return $user_info[$str];
            }
        }else{
            //POSTにデータが有り、かつDBの情報と違う場合
            if(isset($_POST[$str]) && $_POST[$str] !== $user_info[$str]){
                return $_POST[$str];
            }else{
                //POSTにデータがない、あるいはPOSTとDBの情報が同じなら（そもそも変更していない）、DBの情報を表示
                return $user_info[$str];
            }
        }
    //ユーザデータがない場合
    }else{
        if(isset($_POST[$str])){
            return $_POST[$str];
        }
    }
}

// サニタイズ
function sanitize($str){
    return htmlspecialchars($str,ENT_QUOTES);
}

//フォーム入力保持( weightsテーブルのデータ用 )
function getFormDataWeight($str){
    global $weight_info;
    //ユーザデータがある場合
    if(!empty($weight_info)){
        // フォームのエラーがある場合
        if(!empty($err_msg[$str])){
            // POSTにデータがある場合
            if(isset($err_msg[$str])){
                return $_POST[$str];
            }else{
                //ない場合（フォームにエラーがある時点で、POSTにデータがあるはずなので、まずありえない状況だが）は、DBの情報を開示
                return $weight_info[$str];
            }
        }else{
            //POSTにデータが有り、かつDBの情報と違う場合
            if(isset($_POST[$str]) && $_POST[$str] !== $weight_info[$str]){
                return $_POST[$str];
            }else{
                //POSTにデータがない、あるいはPOSTとDBの情報が同じなら（そもそも変更していない）、DBの情報を表示
                return $weight_info[$str];
            }
        }
        //ユーザデータがない場合
    }else{
        if(isset($_POST[$str])){
            return $_POST[$str];
        }
    }
}

//フォーム入力保持( dailyreportテーブルのデータ用 )
function getFormDataDailyreport($str){
    global $dailyreport_info;
    //ユーザデータがある場合
    if(!empty($dailyreport_info)){
        // フォームのエラーがある場合
        if(!empty($err_msg[$str])){
            // POSTにデータがある場合
            if(isset($err_msg[$str])){
                return $_POST[$str];
            }else{
                //ない場合（フォームにエラーがある時点で、POSTにデータがあるはずなので、まずありえない状況だが）は、DBの情報を開示
                return $dailyreport_info[$str];
            }
        }else{
            //POSTにデータが有り、かつDBの情報と違う場合
            if(isset($_POST[$str]) && $_POST[$str] !== $dailyreport_info[$str]){
                return $_POST[$str];
            }else{
                //POSTにデータがない、あるいはPOSTとDBの情報が同じなら（そもそも変更していない）、DBの情報を表示
                return $dailyreport_info[$str];
            }
        }
        //ユーザデータがない場合
    }else{
        if(isset($_POST[$str])){
            return $_POST[$str];
        }
    }
}


//フラッシュメッセージ
function getSesssionFlash($key){
    
    if(!empty($_SESSION[$key])){
        $data = $_SESSION[$key];
        $_SESSION[$key] = '';
        return $data;
    }
    
}

//ランダムなキーを作成
function makeRandKey($length = 8){
    $key = '';
    $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $i = 0;
    while($i<$length){
        
        $key .= $char[mt_rand(0,61)];
        $i++;
    }
    return $key;
}
    
//日付の差を計算する関数    
function day_diff($date1, $date2) {

    // 日付をUNIXタイムスタンプに変換
    $timestamp1 = strtotime($date1);
    $timestamp2 = strtotime($date2);

    // 何秒離れているかを計算
    $seconddiff = abs($timestamp2 - $timestamp1);

    // 日数に変換
    $daydiff = $seconddiff / (60 * 60 * 24);

    // 戻り値
    return $daydiff;

}    
    
