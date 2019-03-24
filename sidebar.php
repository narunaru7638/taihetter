<?php

if($_SESSION['user_id'] === $_GET['u'] || empty($_GET['u'])):

?>
   

<section id="sidebar">
    <a href="profEdit.php">プロフィール編集</a>
    <a href="passEdit.php">パスワード変更</a>
    <a href="withdraw.php">退会</a>
</section>


<?php else: ?>

<?php 
    $rivalRecentInfo = getRivalRecentInfo($_GET['u']);
?>

<section id="sidebar">
    
    <img class="icon" src="<?php echo sanitize(showNoImg($rivalRecentInfo['pic'])) ?>" alt="">
    
    <div class = "title-in-sidebar">ニックネーム</div>
    <div class = "content-in-sidebar"><?php echo sanitize($rivalRecentInfo['username']) ?></div>
    
    <div class = "title-in-sidebar">最終更新日</div>
    <div class = "content-in-sidebar"><?php echo sanitize($rivalRecentInfo['date_of_mesure']) ?></div>
    
    <div class = "title-in-sidebar">ダイエットをはじめた理由・目標</div>
    <div class = "content-in-sidebar"><?php echo sanitize($rivalRecentInfo['your_goal']) ?></div>
    
    <div class = "title-in-sidebar">ダイエットのために実施すること</div>
    <div class = "content-in-sidebar"><?php echo sanitize($rivalRecentInfo['your_action']) ?></div>
    
    <div class = "title-in-sidebar">コメント・ブログ情報など</div>
    <div class = "content-in-sidebar"><?php echo sanitize($rivalRecentInfo['mypage_comment']) ?></div>





</section>



<?php endif; ?>