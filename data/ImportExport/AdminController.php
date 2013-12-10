<?php
namespace Modules\data\ImportExport;


use \Modules\developer\Form\Exception;

class AdminController extends \Ip\Controller
{

    public function index()
    {
        global $site;

        $formImport = Model::getFormImport();
        $formExport = Model::getFormExport();

        $data = array (
            'formImport' => $formImport,
            'formExport' => $formExport
        );

        $view = \Ip\View::create('view/index.php', $data);

        $site->addJavascript(BASE_URL.PLUGIN_DIR.'data/ImportExport/public/importExport.js');

        return $view->render();
    }

    public function import()
    {
        $form = Model::getFormImport();

        $fileField = $form->getField('siteFile');
        $files = $fileField->getFiles($_POST, $fileField->getName());

        $service = New Service();


        foreach ($files as $file){

            $service->startImport($file);
        }


        $response['log'] =   $service->getImportLog();
        $response['status'] =   'success';

        return $this->returnJson($response);
    }

    public function export(){
        print "TEST: Data export will be here"; //TODO Add exporting
    }
}