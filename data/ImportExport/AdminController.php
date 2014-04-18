<?php
namespace Modules\data\ImportExport;


use \Modules\developer\Form\Exception;

class AdminController extends \Ip\Controller
{

    public function index()
    {
        global $site;

        $formImport = ModelImport::getFormImport();
        $formExport = ModelExport::getFormExport();

        $data = array (
            'formImport' => $formImport,
            'formExport' => $formExport
        );

        $view = \Ip\View::create('view/index.php', $data);

        $site->addJavascript(BASE_URL.PLUGIN_DIR.'data/ImportExport/public/import.js');
        $site->addJavascript(BASE_URL.PLUGIN_DIR.'data/ImportExport/public/export.js');

        return $view->render();
    }

    public function import()
    {
        $form = ModelImport::getFormImport();

        $fileField = $form->getField('siteFile');
        $files = $fileField->getFiles($_POST, $fileField->getName());

        $service = New Service();


        foreach ($files as $file){

            $service->startImport($file);
        }


        $response['log'] =   Log::getLog();
        $response['status'] =   'success';

        return $this->returnJson($response);
    }

    public function export(){

        $service = New Service();
        $results = $service->startExport();

        $response['status'] =   'success';
        $response['log']  = Log::getLog();

       return $this->returnJson($response);
    }
}