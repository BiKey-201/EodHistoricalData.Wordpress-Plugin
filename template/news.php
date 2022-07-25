<?php if($news['error'] || $news['errors']){ ?>
    <div class="eod_error">News widget: <?= $news['error'] ? : 'error' ?></div>
<?php }else{ ?>
    <script>
        eod_render_news_item(jQuery('.eod_news_list[data-target="<?= $target ?>"]'), <?= json_encode($news) ?>);
    </script>
<?php } ?>
