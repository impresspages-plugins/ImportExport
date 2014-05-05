<?php
namespace Plugin\ImportExport;


use Ip\Form\Exception;

class AdminController extends \Ip\Controller
{

    public function index()
    {

        $formImport = ModelImport::getForm();
        $formExport = ModelExport::getForm();

        $data = array(
            'formImport' => $formImport,
            'formExport' => $formExport,
        );

        $view = ipView('view/index.php', $data);

        ipAddJs('assets/import.js');
        ipAddJs('assets/export.js');

        return $view->render();
    }

    public function import()
    {
        $form = ModelImport::getForm();

        $fileField = $form->getField('siteFile');
        $files = $fileField->getFiles($_POST, $fileField->getName());

        $service = New Service();

        foreach ($files as $file) {
            $service->startImport($file);
        }

        $response['log'] = Log::getLog();
        $response['status'] = 'success';

        return new \Ip\Response\Json($response);
    }

    public function export()
    {
        $service = New Service();

        $results = $service->startExport();

        $response['status'] = 'success';
        $response['log'] = Log::getLog();
        $response['downloadUrl'] = $results['results'];

        return new \Ip\Response\Json($response);
    }

 
}