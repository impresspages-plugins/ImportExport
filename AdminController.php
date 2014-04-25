<?php
namespace Plugin\ImportExport;


use Ip\Form\Exception;

class AdminController extends \Ip\Controller
{

    public function index()
    {

        $form = ModelImport::getForm();

        $data = array (
            'form' => $form
        );

        $view = ipView('view/index.php', $data);

        ipAddJs('assets/importExport.js');

        return $view->render();
    }

    public function import()
    {
        $form = ModelImport::getForm();

        $fileField = $form->getField('siteFile');
        $files = $fileField->getFiles($_POST, $fileField->getName());

        $service = New Service();


        foreach ($files as $file){
            $service->startImport($file);
        }


        $response['log'] =   Log::getLog();
        $response['status'] =   'success';
        return new \Ip\Response\Json($response);
    }
}