<?php

namespace src;

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
     */
    public function __construct($html)
    {
        /** Получаем DOCTYPE и удаляем его из контента чтобы не мешал */
        $this->doctype = Common::parseOne('/<!DOCTYPE (.+)>/m', $html);
        Common::cut('/<!DOCTYPE (.+)>/m', $html);
        /** Ищем <html>, если нет - создаем */
        if(!$htmlInside = Common::parseOne('/<html.*>(.*)<\/html>/musU', $html)) {
            $htmlInside = '<head></head><body>'.$html.'</body>';
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

    public function getHtml()
    {
        return $this->dom;
    }

    public function getHead()
    {
        return $this->dom->getChildren('head');
    }

    public function getBody()
    {
        return $this->dom->getChildren('body');
    }

}