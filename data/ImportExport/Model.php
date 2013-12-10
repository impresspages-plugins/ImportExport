<?php
namespace Modules\data\ImportExport;

class Model
{

    public static function getForm()
    {
        $form = new  \Modules\developer\form\Form();


        $form->setAction(BASE_URL);

        $field = new \Modules\developer\form\Field\File(
            array(
                'name' => 'siteFile', //html "name" attribute
                'label' => 'ZIP file:', //field label that will be displayed next to input field
            ));
        $form->addField($field);
        $fileField = $field;


        $field = new \Modules\developer\form\Field\Submit(
            array(
                'name' => 'submit', //html "name" attribute
                'label' => 'submit', //field label that will be displayed next to input field
                'defaultValue' => 'Import site widget content from file'
            ));
        $form->addField($field);

        $field = new \Modules\developer\form\Field\Hidden(
            array(
                'name' => 'action',
                'defaultValue' => 'import'
            )
        );

        $form->addField($field);

        $field = new \Modules\developer\form\Field\Hidden(
            array(
                'name' => 'aa',
                'defaultValue' => 'import'
            ));
        $form->addField($field);

        $field = new \Modules\developer\form\Field\Hidden(
            array(
                'name' => 'g',
                'defaultValue' => 'data'
            ));
        $form->addField($field);

        $field = new \Modules\developer\form\Field\Hidden(
            array(
                'name' => 'm',
                'defaultValue' => 'ImportExport'
            ));
        $form->addField($field);

        return $form;
    }

    public static function languageExists($url)
    {

        global $site;

        $ra = $site->getLanguageByUrl($url);

        if (is_object($ra)) {
            return true;
        } else {
            return false;
        }

    }


    public static function addWidget(
        $widgetName,
        $zoneName,
        $pageId,
        $blockName = null,
        $revisionId = null,
        $position = null
    ) {

        global $site;

        if (is_null($revisionId)) {
            //Static block;
            //TODOX use \Ip\Revision::getLastRevision instead
            $revisionId = \Ip\Revision::createRevision($zoneName, $pageId, true);
        } else {
            //check revision consistency
            $revisionRecord = \Ip\Revision::getRevision($revisionId);

            if (!$revisionRecord) {
                throw new Exception("Can't find required revision " . $revisionId, Exception::UNKNOWN_REVISION);
            }

            $zoneName = $revisionRecord['zoneName'];
            $pageId = $revisionRecord['pageId'];

//
//            $zone = $site->getZone($zoneName);
//            if ($zone === false) {
//                //TODOX service must not return Response object.
//                throw new Exception( 'Unknown zone "' . $zoneName . '"');
//            }

//            $page = $zone->getPage($pageId);
//            if ($page === false) {
//                //TODOX service must not return Response object.
//                throw new Exception('Page not found "' . $zoneName . '"/"' . $pageId . '"');
//            }

        }

        $widgetObject = \Modules\standard\content_management\Model::getWidgetObject($widgetName);

        if ($widgetObject === false) {
            //TODOX service must not return Response object.
            return 'Unknown widget "' . $widgetName . '"';
        }

        try {

            $layouts = $widgetObject->getLayouts();
            $widgetId = \Modules\standard\content_management\Model::createWidget(
                $widgetName,
                array(),
                $layouts[0]['name'],
                null
            );

        } catch (Exception $e) {
            //TODOX service must not return Response object.
            return $e;
        }

        try {
            $instanceId = \Modules\standard\content_management\Model::addInstance(
                $widgetId,
                $revisionId,
                $blockName,
                $position,
                true
            );
        } catch (Exception $e) {
            //TODOX service must not return Response object.
            throw new Exception( 'Cannot add instance for page id ' . $pageId. '');
        }

//        print "<br>INSTANCE ID ".$instanceId;

        return $instanceId;

    }


    public static function addWidgetContent($instanceId, $content, $layout = 'default')
    {

        try {
            $record = \Modules\standard\content_management\Model::getWidgetFullRecord($instanceId);
            $widgetObject = \Modules\standard\content_management\Model::getWidgetObject($record['name']);


            $newData = $widgetObject->update($record['widgetId'], $content, $record['data']);
            $updateArray = array(
                'data' => $newData,
                'layout' => $layout
            );

            \Modules\standard\content_management\Model::updateWidget($record['widgetId'], $updateArray);
        } catch (Exception $e) {
            return $e;
        }

    }

    public static function addZone(
        $title,
        $zoneName,
        $associatedModule,
        $defaultLayout,
        $associatedGroup = 'standard',
        $description = '',
        $url = ''
    ) {

        global $site;

        if ($site->getZone($zoneName)) {
            throw new CoreException("Zone '" . $zoneName . "' already exists.");
        }

//TODO  throw new Exception("Zone name ".$zoneName." already exists");

        $rowNumber = self::getLastRowNumber();

        $languages = $site->getLanguages();


        $dbh = \Ip\Db::getConnection();

        $sql = "INSERT INTO `" . DB_PREF . "zone`
                (name, template, translation, associated_module, associated_group, row_number)
            VALUES
                 (:name, :template, :translation, :associated_module, :associated_group, :row_number) ";

        $sth = $dbh->prepare($sql);

        $queryData = array(
            'name' => $zoneName,
            'template' => $defaultLayout,
            'translation' => $title,
            'associated_module' => self::updateAssocModuleTo3x($associatedModule),
            'associated_group' => $associatedGroup,
            'row_number' => ++$rowNumber
        );

        $sth->execute($queryData);

        $zoneId = $dbh->lastInsertId();


        foreach ($languages as $key => $languageObj) {


            $language_id = $languageObj->getId();

            $queryData = array(
                'visible' => 1
            );
            $sql = "INSERT INTO `".DB_PREF."content_element`
                (visible)
                VALUES
                (:visible)";
//            $element_id = ipDb()->insert(DB_PREF . 'content_element', $row);
            $sth = $dbh->prepare($sql);
            $sth->execute($queryData);
            $element_id = $dbh->lastInsertId();

            $queryData = array(
                'language_id' => $language_id,
                'zone_id' => $zoneId,
                'element_id' => $element_id
            );

//            ipDb()->insert(DB_PREF . 'zone_to_content', $row);
            $sql = "INSERT INTO `" . DB_PREF . "zone_to_content`
                (language_id, zone_id, element_id)
                     VALUES
                (:language_id, :zone_id, :element_id)";

            $sth = $dbh->prepare($sql);
            $sth->execute($queryData);


            require_once(BASE_DIR.MODULE_DIR.'standard/languages/db.php');

            $queryData = array(
                'title' => $title,
                'language_id' => $language_id,
                'zone_id' => $zoneId,
                'url' => \Modules\standard\languages\Db::newUrl($language_id, $url)
            );

//            ipDb()->insert(DB_PREF . 'zone_parameter', $row);


            $sql = "INSERT INTO `" . DB_PREF . "zone_parameter`
                (title, language_id, zone_id, url)
                VALUES
                (:title, :language_id, :zone_id, :url)";

            $sth = $dbh->prepare($sql);
            $sth->execute($queryData);



        }

//        $site->invalidateZones();

        return $zoneId;
    }


    public static function getLastRowNumber()
    {

        $dbh = \Ip\Db::getConnection();

        $sql = "select MAX(row_number) as max_row from " . DB_PREF . "zone";

        $sth = $dbh->prepare($sql);
        $sth->execute();

        $result = $sth->fetch();

        return $result['max_row'];
    }

    public static function getZoneIdByName($zoneName)
    {


        $dbh = \Ip\Db::getConnection();

        $queryData = array('zoneName' => $zoneName);

        $sql = "select id from " . DB_PREF . "zone
            WHERE
            name=:zoneName";

        $sth = $dbh->prepare($sql);
        $sth->execute($queryData);


        $result = $sth->fetch();

        if ($sth->rowCount()>0){
            return $result['id'];
        }else{
            return false;
        }

    }



    public static function addPage(
        $zoneName,
        $parentPageId,
        $buttonTitle = 'page',
        $pageTitle = 'Page',
        $url = null,
        $position = null,
        $visible = 0
    ) {

        global $site; //TODO Replace $site
        $zone = $site->getZone($zoneName);

        $parentPage = \Modules\standard\menu_management\Db::getPage($parentPageId);

        $data = array();

        $data['buttonTitle'] = $buttonTitle;
        $data['pageTitle'] = $pageTitle;

        if (!is_null($url)) {
            $data['url'] = \Modules\standard\menu_management\Db::makeUrl($url);
        }

        $data['createdOn'] = date("Y-m-d");
        $data['lastModified'] = date("Y-m-d");

        if ($visible == "1"){
            $data['visible'] = true; //TODO !ipGetOption('Pages.hideNewPages');
        }else{
            $data['visible'] = false;
        }


        $newPageId =  \Modules\standard\menu_management\Db::insertPage($parentPage['id'], $data);

        if (!is_null($position) && (intval($position)>0)){
            self::setPagePosition($newPageId, $position);
        }

        return $newPageId;
    }

    public static function setPagePosition($pageId, $position){

        $dbh = \Ip\Db::getConnection();

        $sql = "UPDATE ".DB_PREF."content_element SET row_number=:position WHERE id=:pageId";
        $sth = $dbh->prepare($sql);
        $parameters = Array("position" => intval($position), "pageId" => $pageId);
        $sth->execute($parameters);
    }

    public static function getLanguageByUrl($url){

        $dbh = \Ip\Db::getConnection();

        $sql = "SELECT * FROM ".DB_PREF."language WHERE url=:url";
        $parameters = Array("url" => $url);

        $sth = $dbh->prepare($sql);
        $sth->execute($parameters);

        $result = $sth->fetch();

        return $result;
    }

    public static function updateAssocModuleTo3x($moduleName){

        switch ($moduleName){
            case 'Content':
                $moduleName3x = 'content_management';
                break;
            default:
                $moduleName3x = $moduleName;
        }

        return $moduleName3x;

    }
}