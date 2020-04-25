<?php

use src\Element;
use src\Common;

class Parser
{

    /**
     * @var Element корневой <html> элемент
     */
    private $dom;

    /**
     * @var string содержимое тега <!DOCTYPE>
     */
    private $doctype;

    /**
     * @param string $url url сайта который парсить
     */
    public function __construct($url)
    {
        $finalUrl = iconv("utf-8","windows-1251", $url);
        $htmlContain = file_get_contents($finalUrl);
        $htmlContain = mb_convert_encoding($htmlContain, 'utf-8', mb_detect_encoding($htmlContain));
        /** Получаем DOCTYPE и удаляем его из контента чтобы не мешал */
        $this->doctype = Common::parseOne('/<!DOCTYPE (.+)>/m', $htmlContain);
        Common::cut('/<!DOCTYPE (.+)>/m', $htmlContain);
        /** Ищем <html>, если нет - создаем */
        if(!$htmlInside = Common::parseOne('/<html.*>(.*)<\/html>/musU', $htmlContain)) {
            $htmlInside = '<head></head><body>'.$htmlContain.'</body>';
        }
        $htmlTag = new Element('html', []);
        /** Получаем содержимое <bead> и <body> */
        $headInside = Common::parseOne('/<head.*>(.*)<\/head>/musU', $htmlInside);
        $bodyInside = Common::parseOne('/<body.*>(.*)<\/body>/musU', $htmlInside);
        /** Парсим дерево */
        $headTag = new Element('head', Common::parseDOM($headInside));
        $bodyTag = new Element('body', Common::parseDOM($bodyInside));
        $htmlTag->append($headTag);
        $htmlTag->append($bodyTag);
        $this->dom = $htmlTag;
    }

    public function getHead()
    {
        return $this->head;
    }

}