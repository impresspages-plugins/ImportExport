<?php
/**
 * Created by JetBrains PhpStorm.
 * User: marijus
 * Date: 13.11.22
 * Time: 12.08
 * To change this template use File | Settings | File Templates.
 */

namespace Modules\data\export;


//use Modules\developer\zones\Manager;

//use Modules\developer\zones\Manager;

class pageContent
{

    public $module_id = 0;

    public function getContent($module_key, $module_id)
    {
        $this->module_id = $module_id;


        switch ($module_key) {
            case 'title':
                $elements = $this->getTitle();
                break;
            case 'text':
                $elements = $this->getText();
                break;
            case 'text_photo':
                $elements = $this->getTextPhoto();
                break;
            case 'text_title':
                $elements = $this->getTextTitle();
                break;
            case 'file':
                $elements = $this->getFile();
                break;
            case 'html_code':
                $elements = $this->getHtmlCode();
                break;
            case 'photo':
                $elements = $this->getPhoto();
                break;
            case 'separator':
                $elements = $this->getSeparator();
                break;
            case 'table':
                $elements = $this->getTable();
                break;
            default:
                $elements = null;
                throw new \Exception('EXPORT ERROR MISSING PARSER:' . $module_key . ', MODULE ID:' . $module_id);
//                $this->logError('EXPORT ERROR MISSING PARSER:'.$module_key.', MODULE ID:'.$module_id);
        }

        return $elements;

    }


    public function getPhoto()
    {


        $sql_cont = "SELECT title, photo, photo_big, layout FROM `ip_mc_text_photos_photo` WHERE id=" . $this->module_id;

        $rs_cont = mysql_query($sql_cont);

        $elements['type'] = 'IpImage';

        if (mysql_num_rows($rs_cont) > 0) {
            $ra_cont = mysql_fetch_assoc($rs_cont);
            $elements['title'] = $ra_cont['title'];
            $elements['photo'] = $ra_cont['photo'];
            $elements['photo_big'] = $ra_cont['photo_big'];
            $elements['layout'] = $ra_cont['layout'];
        } else {

            throw new \Exception('EXPORT ERROR MISSING PHOTO. MODULE ID:' . $this->module_id);

        }

        return $elements;


    }


    public function getTextTitle()
    {


        $sql_cont = "SELECT title, text, level, layout FROM `ip_mc_text_photos_text_title` WHERE id=" . $this->module_id;

        $rs_cont = mysql_query($sql_cont);

        $elements['type'] = 'IpText';

        if (mysql_num_rows($rs_cont) > 0) {
            $ra_cont = mysql_fetch_assoc($rs_cont);
            $elements['data']['title'] = $ra_cont['title'];
            $elements['data']['text'] = $ra_cont['text'];
            $elements['data']['level'] = $ra_cont['level'];
            $elements['layout'] = $ra_cont['layout'];
        } else {
            throw new \Exception('EXPORT ERROR MISSING TEXT TITLE. MODULE ID:' . $this->module_id);
        }

        return $elements;


    }

    public function getTitle()
    {

        $sql_cont = "SELECT title FROM `ip_mc_text_photos_title` WHERE id=" . $this->module_id;

        $rs_cont = mysql_query($sql_cont);

        $elements['type'] = 'IpTitle';

        if (mysql_num_rows($rs_cont) > 0) {
            $ra_cont = mysql_fetch_assoc($rs_cont);
            $elements['data']['title'] = $ra_cont['title'];
        } else {
            throw new \Exception('EXPORT ERROR MISSING TITLE. MODULE ID:' . $this->module_id);
        }

        return $elements;

    }

    public function getHtmlCode()
    {

        $sql_cont = "SELECT text, layout FROM `ip_mc_misc_html_code` WHERE id=" . $this->module_id;

        $rs_cont = mysql_query($sql_cont);

        $elements['type'] = 'IpHtml';

        if (mysql_num_rows($rs_cont) > 0) {
            $ra_cont = mysql_fetch_assoc($rs_cont);
            $elements['data']['html'] = $ra_cont['text'];
            $elements['layout'] = $ra_cont['layout'];
        } else {
            throw new \Exception('EXPORT ERROR MISSING HTML CODE:' . $this->module_id);
        }

        return $elements;

    }

    public function getSeparator()
    {

        $sql_cont = "SELECT layout FROM `ip_mc_text_photos_separator` WHERE id=" . $this->module_id;

        $rs_cont = mysql_query($sql_cont);

        $elements['type'] = 'IpSeparator';

        if (mysql_num_rows($rs_cont) > 0) {
            $ra_cont = mysql_fetch_assoc($rs_cont);
            $elements['layout'] = $ra_cont['layout'];
        } else {
            throw new \Exception('EXPORT ERROR MISSING SEPARATOR:' . $this->module_id);
        }

        return $elements;

    }

    public function getText()
    {

        $sql_cont = "SELECT text, layout FROM `ip_mc_text_photos_text` WHERE id=" . $this->module_id;

        $rs_cont = mysql_query($sql_cont);

        $elements['type'] = 'IpText';

        if (mysql_num_rows($rs_cont) > 0) {
            $ra_cont = mysql_fetch_assoc($rs_cont);
            $elements['data']['text'] = $ra_cont['text'];
            $elements['layout'] = $ra_cont['layout'];
        } else {
            throw new \Exception('EXPORT ERROR MISSING TEXT:' . $this->module_id);
        }

        return $elements;

    }


    public function getTextPhoto()
    {

        $sql_cont = "SELECT title, photo, photo_big, text, layout FROM `ip_mc_text_photos_text_photo` WHERE id=" . $this->module_id;

        $rs_cont = mysql_query($sql_cont);

        $elements['type'] = 'IpTextImage';

        if (mysql_num_rows($rs_cont) > 0) {
            $ra_cont = mysql_fetch_assoc($rs_cont);
            $elements['data']['title'] = $ra_cont['title'];
            $elements['data']['photo'] = $ra_cont['photo'];
            $elements['data']['photo_big'] = $ra_cont['photo_big'];
            $elements['data']['text'] = $ra_cont['text'];
            $elements['layout'] = $ra_cont['layout'];
        } else {
            throw new \Exception('EXPORT ERROR MISSING TEXT PHOTO:' . $this->module_id);
        }

        return $elements;

    }

    public function getFile()
    {

        $sql_cont = "SELECT title, photo, layout FROM `ip_mc_misc_file` WHERE id=" . $this->module_id;

        $rs_cont = mysql_query($sql_cont);

        $elements['type'] = 'IpFile';

        if (mysql_num_rows($rs_cont) > 0) {
            $ra_cont = mysql_fetch_assoc($rs_cont);
            $elements['data']['title'] = $ra_cont['title'];
            $elements['data']['photo'] = $ra_cont['photo'];
            $elements['layout'] = $ra_cont['layout'];
        } else {
            throw new \Exception('EXPORT ERROR MISSING FILE:' . $this->module_id);
        }

        return $elements;

    }

    public function getTable()
    {

        $sql_cont = "SELECT text, layout FROM `ip_mc_text_photos_table` WHERE id=" . $this->module_id;

        $rs_cont = mysql_query($sql_cont);

        $elements['type'] = 'IpTable';

        if (mysql_num_rows($rs_cont) > 0) {
            $ra_cont = mysql_fetch_assoc($rs_cont);
            $elements['data']['text'] = $ra_cont['text'];
            $elements['layout'] = $ra_cont['layout'];
        } else {
            throw new \Exception('EXPORT ERROR MISSING FILE:' . $this->module_id);
        }

        return $elements;

    }


}