<div class="ipsImportForm"><?php echo $form->render(); ?></div>


<div class="ipsLoading ipgHide">
    <div><?php _e('Importing data. Please wait', 'ImportExport') ?></div>
    <img src="<?php echo ipFileUrl('Plugin/ImportExport/assets/loading.gif'); ?>" alt="<?php _e('Importing', 'ImportExport') ?>" />
</div>
<div class="ipsLog ipgHide">
    <div class="alert ipsLogRecord ipgHide"></div>
</div>