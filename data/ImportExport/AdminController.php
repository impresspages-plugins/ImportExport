<?php
namespace Modules\data\ImportExport;


use \Modules\developer\Form\Exception;

class AdminController extends \Ip\Controller
{

    public function index()
    {
        global $site;

        $form = Model::getForm();

        $data = array (
            'form' => $form
        );

        $view = \Ip\View::create('view/index.php', $data);

        $site->addJavascript(BASE_URL.PLUGIN_DIR.'data/ImportExport/public/importExport.js');

        return $view->render();
    }

    public function import()
    {
        $form = Model::getForm();

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
}