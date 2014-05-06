<div class="ipsImportForm">
    <?php echo $formImport->render(); ?>
</div>

<div class="ipsLoading hidden">
    <div><?php _e('Importing data. Please wait', 'ImportExport') ?></div>
    <img src="<?php echo ipFileUrl('Plugin/ImportExport/assets/loading.gif'); ?>"
         alt="<?php _e('Importing', 'ImportExport') ?>"/>
</div>

<div class="ipsImportBack hidden">
    <div><a href="?aa=ImportExport"><?php echo _e('Back', 'ImportExport'); ?></a></div>
</div>

<hr>
<div>
    <?php echo $formExport->render(); ?>
</div>

<div class="ipsLog hidden">
    <div class="alert ipsLogRecord hidden"></div>
</div>

<div class="ipsExportBack hidden">
    <div><a class="ipsExportDownloadUrl" href="#"><?php echo _e('Download', 'ImportExport'); ?></a></div>
    <div><a href="?aa=ImportExport"><?php echo _e('Back', 'ImportExport'); ?></a></div>
</div>
