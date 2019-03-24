<header>
    <div class="site-width">
<!--
        <h1><a href="index.html">Taihetter</a></h1>
        <nav id="top-nav">
            <ul>
-->
                <?php
                    if(empty($_SESSION['user_id'])){
                ?>
                    <h1><a href="index.php">Taihetter</a></h1>
                    <nav id="top-nav">
                        <ul>

                        <li><a href="signup.php" class="btn btn-primary">ユーザー登録</a></li>
                        <li><a href="login.php" class="btn">ログイン</a></li>
                        <li><a href="about.php" class="btn">about</a></li>
                <?php
                    }else{
                ?>
                    <h1><a href="index.php">Taihetter</a></h1>
                    <nav id="top-nav">
                        <ul>

                        <li><a href="logout.php" class="btn">ログアウト</a></li>
                        <li><a href="indexRival.php" class="btn">ライバル</a></li>
                        <li><a href="resistDailyReport.php" class="btn">体重・食事の登録</a></li>
                        <li><a href="indexDailyReport.php" class="btn">毎日の記録</a></li>
                        <li><a href="mypage.php" class="btn">マイページ</a></li>
                <?php
                    }
                ?>
            </ul>
        </nav>
    </div>
</header>