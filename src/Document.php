<?php

namespace lanfix\parser\src;

use Exception;

class Document
{

    /**
     * @var Element[] корневой <html> элемент
     */
    private $dom;

    /**
     * @var string содержимое тега <!DOCTYPE>
     */
    private $doctype;

    /**
     * @param string $html html страницы
     * @throws Exception
     */
    public function __construct($html)
    {
        /** Получаем DOCTYPE и удаляем его из контента чтобы не мешал */
        $this->doctype = Common::parseOne('/<!DOCTYPE (.+)>/im', $html);
        Common::cut('/<!DOCTYPE (.+)>/im', $html);
        /** Ищем <html>, если нет - создаем */
        if(!$htmlInside = Common::parseOne('/<html.*>(.*)<\/html>/imusU', $html)) {
            $htmlInside = '<head></head><body>'.$html.'</body>';
        }
        $htmlTag = new Element('html', []);
        /** Получаем содержимое <bead> и <body> */
        $headCode = Common::parseOne('/(<head.*>.*<\/head>)/imusU', $htmlInside);
        $bodyCode = Common::parseOne('/(<body.*>.*<\/body>)/imusU', $htmlInside);
        /** Парсим дерево */
        $htmlTag->append(Common::parseDOM($headCode)[0]);
        $htmlTag->append(Common::parseDOM($bodyCode)[0]);
        $this->dom = $htmlTag;
    }

    /**
     * @return Element
     */
    public function getHtml()
    {
        return $this->dom;
    }

    /**
     * @return Element
     * @throws Exception
     */
    public function getHead()
    {
        return $this->dom->getChildren('head');
    }

    /**
     * @return Element
     * @throws Exception
     */
    public function getBody()
    {
        return $this->dom->getChildren('body');
    }

}