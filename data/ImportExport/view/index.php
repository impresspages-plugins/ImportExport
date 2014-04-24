<div class="ipsImportForm"><?php echo $formImport->render(); ?></div>


<div class="ipsLoading ipgHide">
    <div><?php $this->esc('Importing data. Please wait', 'ImportExport') ?></div>
    <img src="<?php echo BASE_URL.PLUGIN_DIR.'data/ImportExport/public/loading.gif'; ?>" alt="<?php $this->esc('Importing', 'ImportExport') ?>" />
</div>
<div class="ipsLog ipgHide">
    <div class="alert ipsLogRecord ipgHide"></div>
</div>

<div class="ipsExportForm"><?php echo $formExport->render(); ?></div>

<div class="ipsImportExportBack ipgHide">
    <div><a class="ipsImportExportDownloadUrl" href="#"><?php echo $this->esc('Download'); ?></a></div>
    <div><a href="?g=data&m=ImportExport&aa=index"><?php echo $this->esc('Back'); ?></a></div>
</div>

