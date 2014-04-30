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

//    public function testFileExport(){
//
//        $testJson = '{"type":"Gallery","layout":"default","data":{"images":[{"imageOriginal":"Tulips123_12.jpg","title":"flw1yellow","cropX1":"0","cropX2":"1024","cropY1":"61","cropY2":"701"},{"imageOriginal":"Penguins_39.jpg","title":"gvinpin","cropX1":"0","cropX2":"1024","cropY1":"61","cropY2":"701"},{"imageOriginal":"Lighthouse_12.jpg","title":"castle1","cropX1":"0","cropX2":"1024","cropY1":"61","cropY2":"701"},{"imageOriginal":"Chrysanthemum_48.jpg","title":"flw2red","cropX1":"0","cropX2":"1024","cropY1":"61","cropY2":"701"}]}}';
//        $widget = json_decode($testJson, true);
//
//        ModelExport::copyWidgetGalleryFiles($widget['data']);
//    }

}