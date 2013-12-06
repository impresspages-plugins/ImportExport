<?php
/**
 *
 * Simple Form post plugin example
 *
 * @package   ImpressPages
 * @copyright Copyright (C) 2009 JSC Apro media.
 * @license   GNU/GPL, see license.html
 */
namespace Modules\data\export;

if (!defined('BACKEND')) {
    exit;
} //this file can be acessed only in backend


class Template
{

    public static function generateForm($actionUrl)
    {
        $answer = '

        <div>
          <h2>Website export/import</h2>
          <form method="post" action="' . $actionUrl . '">
             <button class="ipExportBtn" name="impexp_action" value="export">Export website to ZIP</button>
          </form>
        </div>
        ';

        return $answer;
    }


    public static function generateExportComplete($actionUrl, $downloadLink)
    {

        $answer = '
        <div>
          <h2>Export complete</h2>
          <form method="post" action="' . $actionUrl . '">
                Click the link to download the archived site files: <a href="'.$downloadLink.'" target="blank">'.$downloadLink.'</a>
             <button class="ipExportBtn">Continue</button>
          </form>
        </div>
        ';

        return $answer;
    }

    public static function addHtmlHead()
    {

        $answer = '<head>';
        $answer .= '<link href="';

        $answer .= BASE_URL . 'ip_cms/modules/developer/std_mod/design/style.css';
        $answer .= '" rel="stylesheet">';
        $answer .= '</head>';

        // TODO
        $answer = '';

        return $answer;
    }

}