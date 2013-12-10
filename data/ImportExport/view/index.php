<div class="ipsImportForm"><?php echo $form->render(); ?></div>


<div class="ipsLoading ipgHide">
    <div><?php $this->esc('Importing data. Please wait', 'ImportExport') ?></div>
    <img src="<?php echo BASE_URL.PLUGIN_DIR.'data/ImportExport/public/loading.gif'; ?>" alt="<?php $this->esc('Importing', 'ImportExport') ?>" />
</div>
<div class="ipsLog ipgHide">
    <div class="alert ipsLogRecord ipgHide"></div>
</div>