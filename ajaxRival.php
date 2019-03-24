<?php
//
////共通変数・関数ファイルを読込み
require('function.php');
//
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　Ajax　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();


//================================
// Ajax処理
//================================

//POSTがあり、ユーザIDがあり、ログインしている場合
if(isset($_POST['rivalId']) && isset($_SESSION['user_id'] )){
    debug('POST送信があります。');
    $rival_user_id = $_POST['rivalId'];
    debug('ユーザーID:'.$_SESSION['user_id']);
    debug('ライバルユーザーID:'.$rival_user_id);
    
    
    if( $rival_user_id != $_SESSION['user_id']){

        try {

            $dbh = dbConnect();
            $sql = 'SELECT * FROM rival WHERE user_id = :user_id AND rival_user_id = :rival_user_id';

            $data = array(':user_id' => $_SESSION['user_id'], ':rival_user_id' => $rival_user_id);

            $stmt = queryPost($dbh, $sql, $data);
            $resultCount = $stmt->rowCount();
            debug('見つかったレコード数:'.$resultCount);

            //レコードが一見でもあればレコードを削除
            if(!empty($resultCount)){
                $sql = 'DELETE FROM rival WHERE user_id = :user_id AND rival_user_id = :rival_user_id';
                $data = array(':user_id' => $_SESSION['user_id'], ':rival_user_id' => $rival_user_id);

                $stmt = queryPost($dbh, $sql, $data);

            //なければ挿入する
            }else{
                $sql = 'INSERT INTO rival (user_id, rival_user_id, create_date) VALUES(:user_id, :rival_user_id, :create_date)';
                $data = array(':user_id' => $_SESSION['user_id'], ':rival_user_id' => $rival_user_id, ':create_date' => date('Y-m-d H:i:s'));

                $stmt = queryPost($dbh, $sql, $data);

        }


        } catch ( Exception $e){
            error_log('エラー発生:' . $e->getMessage());
            $err_msg['common'] = MSG07;
        }
    }else{
        debug('ユーザーIDとライバルユーザーIDが同じのためライバル処理は実施しませんでした。');
    }
}