<?php
namespace Modules\data\ImportExport\widgetsExport;

class IpTextImage extends IpText
{

    public function getIp4Content()
    {

        $widgetData = array();
        $widgetData[] = $this->getIp4TextContent();
        $widgetData[] = $this->getIp4ImageContent();

        return $widgetData;
    }

    public function getIp4TextContent()
    {

        $widgetName = 'Text';

        $elements = array(
            'type' => $widgetName,
            'layout' => $this->getLayout()
        );

        if (isset($this->data['text'])) {
            $elements['data']['text'] = $this->data['text'];
        } else {
            $elements['data']['text'] = '';
        }

        return $elements;

    }

    public function getIp4ImageContent()
    {

        $widgetName = 'Image';

        $elements = array(
            'type' => $widgetName,
            'layout' => $this->getLayout()
        );

        if (isset($this->data['imageOriginal'])) {
            self::copyImage($this->data['imageOriginal']);
        }

        $elements['data'] = self::getSelectedWidgetParams($this->data, array('imageOriginal', 'cropX1', 'cropY1', 'cropX2', 'cropY2'));
        return $elements;

    }


}


