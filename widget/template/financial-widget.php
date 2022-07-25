<?= $args['before_widget'] ?>

<?php if (!empty($title)){ ?>
    <?= $args['before_title'] . $title . $args['after_title']; ?>
<?php } ?>

    <div class="eod_widget_fundamental">
        <?php
        echo eod_load_template("template/financials.php", array(
            'financials_list'  => $financials_list,
            'financial_group'  => $financial_group,
            'years'            => $years,
            'target'           => $target,
            'key'              => str_replace('.', '_', strtolower($target))
        ));
        ?>
    </div>

<?= $args['after_widget']; ?>