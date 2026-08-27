<?php if (get_option('cackle_apiId', '')==""){
    echo'<script> window.location="edit-comments.php?page=cackle_settings"; </script> ';
}
?>
<style>
    #wpcontent {

        padding: 10px;
    }
    #wpbody-content > div.error{
        display: none;
    }

    #wpwrap {
        background-color: #FFFFFF;

    }
    .manacost-cackle-login-help {
        margin: 12px 0 18px;
        padding: 12px 14px;
        border-left: 4px solid #2271b1;
        background: #f6f7f7;
        color: #1d2327;
        font-size: 14px;
        line-height: 1.45;
    }
    .manacost-cackle-login-help .button {
        margin-left: 8px;
    }
</style>

<div class="manacost-cackle-login-help">
    Если кнопка «Авторизоваться» внутри Cackle не открывается, войдите в Cackle в отдельной вкладке и затем обновите эту страницу.
    <a class="button button-primary" href="https://cackle.me/account/signin?returnUrl=/admin" target="_blank" rel="noopener noreferrer">Войти в Cackle</a>
</div>
<div id="mc-comment-admin"></div>
<script type="text/javascript">
    cackle_widget = window.cackle_widget || [];
    cackle_widget.push({widget: 'CommentAdmin', id: <?php print_r(get_option('cackle_apiId', '')); ?>});
    (function () {
        var mc = document.createElement('script');
        mc.type = 'text/javascript';
        mc.async = true;
        mc.src = ('https:' == document.location.protocol ? 'https' : 'http') + '://cackle.me/widget.js';
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(mc, s.nextSibling);
    })();
</script>



