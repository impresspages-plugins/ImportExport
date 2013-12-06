<?php
/**
 * @package ImpressPages
 * @copyright   Copyright (C) 2011 ImpressPages LTD.
 * @license GNU/GPL, see ip_license.html
 */
namespace Modules\data\export;

use Modules\developer\widgets\Exception;

require_once(__DIR__ . '/pageContent.php');
if (!defined('CMS')) {
    exit;
} //this file can be acessed only in backend


class Model
{

    private $errors = null;

    public function getElements($pageId)
    {

        $sql = "SELECT
            id, row_number, element_id, visible, module_key, module_id, instance_id
            FROM " . DB_PREF . "content_element_to_modules
            WHERE
                element_id=" . $pageId . "
            ORDER BY
                element_id ASC,
                row_number ASC,
                module_id ASC";


        try {
            $rs = mysql_query($sql);
        } catch (\ErrorException $e) {
            echo $e;
        }

        $elements = null;

        while ($ra = mysql_fetch_assoc($rs)) {
            $content = new pageContent();
            try {
                $elements[] = $content->getContent($ra['module_key'], $ra['module_id']);
            } catch (\Exception $e) {
                throw new \Exception("Page id=" . $pageId . " - " . $e->getMessage());
            }

        };

        return $elements;

    }


    public static function getPageSettings($pageId)
    {

        $sql = "SELECT
        id,
        row_number,
        parent,
        button_title,
        visible,
        page_title,
        keywords,
        description,
        url,
        last_modified,
        created_on,
        type,
        redirect_url
            FROM " . DB_PREF . "content_element
            WHERE
                id=" . $pageId;


        try {
            $rs = mysql_query($sql);
        } catch (\ErrorException $e) {
            echo $e;
        }

        $elements = null;

        try {
            $settings = mysql_fetch_assoc($rs);
        } catch (Exception $e) {
            throw new \Exception("Cannot retrieve settings for " . $pageId . ". Exception: " . $e);

        };

        return $settings;

    }

    public static function getExportLanguages($languages)
    {
        global $site;

        $languageList = Array();



        foreach ($languages as $language) {

            $languageRecord['code'] = $language->getCode();
            $languageRecord['d_short'] = $language->getShortDescription();
            $languageRecord['d_long'] = $language->getLongDescription();
            $languageRecord['url'] = $language->getUrl();
//            $languageRecord['text_direction'] = $language->getTextDirection();
            $languageRecord['visible'] = $language->getVisible();

            $languageList[] = $languageRecord;
        }

        return $languageList;
    }


    public static function getExportZones($zones)
    {

        $zoneList = Array();

        foreach ($zones as $zone) {
            $item['name'] = $zone->getName();
            $item['title'] = $zone->getTitle();
            $item['url'] = $zone->getUrl();
            $item['description'] = $zone->getDescription();
            $zoneList[] = $item;
        }

        return $zoneList;
    }
}