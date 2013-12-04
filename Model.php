<?php
namespace Plugin\ImportExport;

class Model
{

    public static function getForm()
    {
        $form = new \Ip\Form();


        $field = new \Ip\Form\Field\File(
            array(
                'name' => 'siteFile', //html "name" attribute
                'label' => 'ZIP file:', //field label that will be displayed next to input field
            ));
        $form->addField($field);
        $fileField = $field;


        $field = new \Ip\Form\Field\Submit(
            array(
                'name' => 'submit', //html "name" attribute
                'label' => 'submit', //field label that will be displayed next to input field
                'defaultValue' => 'Import site widget content from file'
            ));
        $form->addField($field);

        $field = new \Ip\Form\Field\Hidden(
            array(
                'name' => 'action',
                'defaultValue' => 'import'
            )
        );

        $form->addField($field);

        $field = new \Ip\Form\Field\Hidden(
            array(
                'name' => 'aa',
                'defaultValue' => 'ImportExport.import'
            ));
        $form->addField($field);

        return $form;
    }

    public static function languageExists($url)
    {

        $ra =  \Ip\Module\Languages\Db::getLanguageByUrl($url);

        if (isset($ra['id'])){
            return true;
        }else{
            return false;
        }

    }


}