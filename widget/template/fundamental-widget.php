<?= $args['before_widget'] ?>

<?php if (!empty($title)){ ?>
    <?= $args['before_title'] . $title . $args['after_title']; ?>
<?php } ?>

    <div class="eod_widget_fundamental">
        <?php
        echo eod_load_template("template/fundamental.php", array(
            'fd_list' => $fd_list,
            'target'  => $target,
            'key'     => str_replace('.', '_', strtolower($target))
        ));
        ?>
    </div>

<?= $args['after_widget']; ?>