<?= $args['before_widget'] ?>

<?php if (!empty($title)){ ?>
    <?= $args['before_title'] . $title . $args['after_title']; ?>
<?php } ?>

    <div class="eod_widget_news">
        <?php
            global $eod_api;
            if($target || $topic)
                $news = $eod_api->get_news($target, array(
                    'tag'    => $topic,
                    'limit'  => $limit,
                    'offset' => $offset,
                    'from'   => $from,
                    'to'     => $to
                ));
            else
                $news = array('error' => 'wrong target or topic');

            echo eod_load_template("template/news.php", array(
                'news' => $news
            ));
        ?>
    </div>

<?= $args['after_widget']; ?>