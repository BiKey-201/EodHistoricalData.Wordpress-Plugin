<div class="eod_financials eod_t_<?= $key ?>"
     data-target="<?= $target ?>"
     data-cols="<?= implode(';', $financials_list) ?>"
     data-group="<?= $financial_group ?>"
     <?= isset($years) ? "data-years='$years'" : '' ?>>
    <div class="eod_table" data-simplebar>
        <div class="eod_tbody"></div>
    </div>
</div>