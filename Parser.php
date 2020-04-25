<?php

use src\Document;
use src\Element;

/**
 * @property Document $document
 */
class Parser
{

    /**
     * @var Element[] корневой <html> элемент
     */
    private $document;

    /**
     * @param string $url url сайта который парсить
     * @throws Exception
     */
    public function __construct($url)
    {
        $finalUrl = iconv("utf-8","windows-1251", $url);
        $htmlContain = file_get_contents($finalUrl);
        $htmlContain = mb_convert_encoding($htmlContain, 'utf-8', mb_detect_encoding($htmlContain));
        $this->document = new Document($htmlContain);
    }

    /**
     * Добавляем магии
     * @param $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        if(in_array($name, ['document'])) {
            return $this->{$name};
        }
        throw new Exception('Property "'.$name.'" is not defined');
    }

}