<?php
global $eod_api;
$fd_lib = $eod_api->get_fd_hierarchy();
?>
<?php if($fd_list['error']){ ?>
    <div class="eod_error">Fundamental data: <?= $fd_list['error'] ? : 'error' ?></div>
<?php }else if(is_array($fd_list) && count($fd_list)){ ?>
    <ul class="eod_fd_list eod_t_<?= $key ?>" data-target="<?= $target ?>">
        <?php foreach($fd_list as $slug){ ?>
            <?php
            // Define title
            $title = eod_get_fd_title_by_slug($slug);
            ?>
            <li data-slug="<?= $slug ?>">
                <b><?= $title ? $title.': ' : '' ?></b>
            </li>
        <?php } ?>
    </ul>
<?php } ?>