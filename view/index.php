<div class="ipsImportForm">
    <?php echo $formImport->render(); ?>
</div>

<div class="ipsLoading hidden">
    <div><?php _e('Importing data. Please wait', 'ImportExport') ?></div>
    <img src="<?php echo ipFileUrl('Plugin/ImportExport/assets/loading.gif'); ?>" alt="<?php _e('Importing', 'ImportExport') ?>" />
</div>
<hr>
<div class="ipsExportForm">
    <?php echo $formExport->render(); ?>
</div>

<div class="ipsImportExportBack hidden">
    <div><a class="ipsImportExportDownloadUrl" href="#"><?php echo _e('Download', 'ImportExport'); ?></a></div>
    <div><a href="?g=data&m=ImportExport&aa=index"><?php echo _e('Back', 'ImportExport'); ?></a></div>
</div>


<div class="ipsLog hidden">
    <div class="alert ipsLogRecord hidden"></div>
</div>
