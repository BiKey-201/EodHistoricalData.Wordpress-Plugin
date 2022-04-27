<?php if($news['error'] || $news['errors']){ ?>
    <div class="eod_error">News widget: <?= $news['error'] ? : 'error' ?></div>
<?php }else if(count($news)){ ?>
    <div class="eod_news_list">
    <?php foreach($news as $item){ ?>
    <?php 
        $display_date = '';
        $timestamp = strtotime($item['date']);
        $time_ago = time() - $timestamp;
        if($time_ago > 24*60*60){
            $display_date = date("F j, Y h:i a", $timestamp);
        }else{
            if($time_ago > 3600){
                $number = floor($time_ago/3600);
                $display_date = $number . ( $number>1 ? ' hours ago' : ' hour ago');
            }else{
                $number = floor($time_ago/60);
                $display_date = $number . ( $number>1 ? ' minutes ago' : ' minute ago');
            }
        }
    ?>
        <div class="eod_news_item">
            <div class="thumbnail"></div>
            <a rel="nofollow" target="_blank" class="h" href="<?= $item['link'] ?>">
                <?= $item['title'] ?>
            </a>
            <time datetime="<?= $item['date'] ?>" class="date"><?= $display_date ?></time>
            <blockquote cite="<?= $item['link'] ?>">
                <div class="description"><?= strlen($item['content']) > 300 ? substr($item['content'], 0, 300) . '...' : $item['content'] ?></div>
            </blockquote>
            <ul class="tags">
                <?php foreach($item['tags'] as $index => $tag){ ?>
                    <li><?= $tag ?></li>
                <?php } ?>
            </ul>
        </div>
    <?php } ?>
    </div>
<?php } ?>