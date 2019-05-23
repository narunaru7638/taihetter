        <footer id="footer">
            Copyright <a href="">なる</a>. All Rights Reserved.
        </footer>
        <script src="js/vendor/jquery-2.2.2.min.js"></script>
        <script>
            // フッターの高さを揃える
            $(function(){
                var $ftr = $('#footer');
                if( window.innerHeight > $ftr.offset().top + $ftr.outerHeight() ){
                    $ftr.attr({'style': 'position:fixed; top:' + (window.innerHeight - $ftr.outerHeight()) +'px;' });
                }
            });
            
            // テキストカウント
            var $countUp = $('.js-count');
            $countUp.on('keyup', function(e){
                var $countView = $(this).siblings('.js-count-view');
                var count = $(this).val().length;
                $countView.html(count);
            });

            
            //画像登録
            var $dropArea = $('.js-area-drop');
            var $fileInput = $('.js-input-file');
            $dropArea.on('dragover', function(e){
                e.stopPropagation();
                e.preventDefault();
                $(this).css('border', '3px #ccc dashed');
            });
            $dropArea.on('dragleave', function(e){
                e.stopPropagation();
                e.preventDefault();
                $(this).css('border', 'none');
            });
            $fileInput.on('change', function(e){
                e.stopPropagation();
                e.preventDefault();
                $dropArea.css('border', 'none');
                //$(this).attr('src', )
                var file = this.files[0],
                    $img = $(this).siblings('.prev-img'),
                    fileReader = new FileReader();
                fileReader.onload = function(event){
                    $img.attr('src', event.target.result).show();
                };
                fileReader.readAsDataURL(file);
            });
            
            //フラッシュメッセージ
            //DOMを変数に入れる。DOMが入った変数名には$をつける。
            var $msgShow = $('#js-show-msg');
            //DOMのテキスト内容を変数に入れる。
            var msg = $msgShow.text();
            //replaceで余分なスペース、タブを無くし、長さ(中身があるか)を確認
            if(msg.replace(/^[\s　]+|[\s　]+$/g, "").length){
                $msgShow.slideToggle('slow');
                setTimeout(function(){ $msgShow.slideToggle('slow')}, 5000 );
            }
            
            //ライバルユーザー登録・削除
            var $rival,
                rivalUserId;
            $rival = $('.js-click-rival') || null;

            //data属性の中身になるのでdata()の中はすべて小文字にしないといけない。html側で例え大文字になっていたとしても。
//            rivalUserId = $rival.data('rivalid') || null;
            
                $rival.on('click', function(){
                    var $this = $(this);
                    rivalUserId = $this.data('rivalid') || null;

                    $.ajax({
                        type: "POST",
                        url: "ajaxRival.php",
                        //rivalUserIdの値を$_POST['rivalId']に格納して送信
                        data: { rivalId : rivalUserId }
                        
                        
                        
                    }).done(function( data ){
                        console.log('Ajax Success');
                        var icon_name = $this.attr("name");
                        $('*[name='+icon_name+']').toggleClass('active');


                        
                    }).fail(function( msg ){
                        console.log('Ajax Error');
                    });
                    
                    
                });
            
        </script>
    </body>
</html>
