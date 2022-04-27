<?php
global $eod_api;
$fd_lib = $eod_api->get_fd_lib();
?>
<?php if($fd_list['error']){ ?>
    <div class="eod_error">Fundamental data: <?= $fd_list['error'] ? : 'error' ?></div>
<?php }else if(is_array($fd_list) && count($fd_list)){ ?>
    <ul class="eod_fd_list eod_t_<?= $key ?>" data-target="<?= $target ?>">
        <?php foreach($fd_list as $slug){ ?>
            <?php
            // Define title
            $path = explode('->', $slug);
            $buffer = $fd_lib;
            foreach ($path as $key){
                if(!isset($buffer[$key])) break;
                $buffer = $buffer[$key];
            }
            $title = is_string($buffer) ? $buffer : '';
            ?>
            <li data-slug="<?= $slug ?>">
                <b><?= $title ? $title.': ' : '' ?></b>
            </li>
        <?php } ?>
    </ul>
<?php } ?>