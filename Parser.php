<?php

namespace lanfix\parser;

use lanfix\parser\src\Document;
use lanfix\parser\src\Element;
use Exception;

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
     * @param string $from Url сайта который парсить
     * @param bool $readyHtml Если === true, то в $url нужно передать готовый html контент
     * @throws Exception
     */
    public function __construct(string $from, bool $readyHtml = false)
    {
        if ($readyHtml) {
            $htmlContain = $from;
        } else {
            $finalUrl = iconv("utf-8","windows-1251", $from);
            $htmlContain = file_get_contents($finalUrl);
        }
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
        if (in_array($name, ['document'])) {
            return $this->{$name};
        }
        throw new Exception('Property "'.$name.'" is not defined');
    }

}