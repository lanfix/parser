<?php

namespace lanfix\parser\src;

use Exception;

class Common
{

    /**
     * Список одиночных тегов
     */
    const ALONE_TAGS = ['area', 'base', 'basefont', 'bgsound', 'br', 'col', 'command', 'embed', 'hr',
        'img', 'input', 'isindex', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    /**
     * Получить данные после парсинга контента по регулярке
     * @param string $regExp регулярное выражение
     * @param string $htmlContain контент
     * @return array
     */
    public static function parse(string $regExp, string $htmlContain)
    {
        preg_match($regExp, $htmlContain, $matches);
        return $matches ?: [];
    }

    /**
     * Взять все найденный элементы
     * @param string $regExp регулярное выражение
     * @param string $htmlContain контент
     * @return array
     */
    public static function parseAll(string $regExp, string $htmlContain)
    {
        $matches = self::parse($regExp, $htmlContain);
        if($matches) array_shift($matches);
        return $matches;
    }

    /**
     * Взять первый найденный элемент
     * @param string $regExp регулярное выражение
     * @param string $htmlContain контент
     * @return string
     */
    public static function parseOne(string $regExp, string $htmlContain)
    {
        $matches = self::parseAll($regExp, $htmlContain);
        return (string)$matches[0];
    }

    /**
     * Проверяет совпадение по регулярке
     * @param string $regExp регулярное выражение
     * @param string $htmlContain контент
     * @return bool
     */
    public static function match(string $regExp, string $htmlContain)
    {
        return (bool)preg_match($regExp, $htmlContain);
    }

    /**
     * Вырезать данные из строки по регулярке
     * @param string $regExp регулярное выражение
     * @param string $containPointer переменная с контентом
     */
    public static function cut(string $regExp, string &$containPointer)
    {
        $containPointer = preg_replace($regExp, '', $containPointer);
    }

    /**
     * Оставить по пробелу между словами. Остальные удалить.
     * @param string $containPointer переменная с контентом
     */
    public static function trim(string &$containPointer)
    {
        $containPointer = trim($containPointer);
        $containPointer = preg_replace('/([\s]|[\t]|[\n]|[\r\n])+/ui', ' ', $containPointer);
    }

    /**
     * Проверяет является ли символ пробелом
     * @param $sym
     * @return bool
     */
    public static function symIsSpace($sym)
    {
        return self::match('/[\s]|[\t]|[\n]|[\r\n]/u', $sym);
    }

    /**
     * Спарсить HTML в объектную структуру
     * @param string $html
     * @return Element[]
     * @throws Exception
     */
    public static function parseDOM(string $html)
    {
        $finalDOMElement = new Element('undefined', []);
        $htmlLength = strlen($html);
        $tagOpen = false;
        $tagName = '';
        $isCloseTag = false;
        $nesting = 0;
        /** Флаги и хранилица для обработки атрибутов */
        $isAttr = false;
        $attrName = '';
        $attrValue = '';
        $attrInQuotes = false;
        $isAttrValue = false;
        $isAttrValueArea = false;
        $attrList = [];
        /** Поиск скриптов */
        $isScript = false;
        $script = '';
        /** Текст внутри тегов */
        $text = '';
        /** @var Element $nowElement */
        $parentElement = $finalDOMElement;
        /** Парсим HTML */
        for($iter = 0; $iter < $htmlLength; $iter++) {
            $sym = $html[$iter];
            if(!$tagOpen && $isScript && $sym === '<' && !$script) {
                $script = '';
                $tagName = '';
                $isScript = false;
                $tagOpen = true;
            }
            /** Начало тега (если есть текст) */
            elseif(!$tagOpen && $isScript && $sym === '<') {
                $element = new Element('', $script);
                $parentElement->append($element);
                $script = '';
                $tagName = '';
                $isScript = false;
                $tagOpen = true;
            }
            elseif($isScript) {
                $script .= $sym;
            }
            /** Завершение ооткрывающего тега */
            elseif($tagOpen && $sym === '>' && !$isCloseTag) {
                if($attrName) $attrList[$attrName] = $attrValue;
                $tagName = strtolower($tagName);
                $element = new Element($tagName, [], $attrList);
                $parentElement->append($element);
                /** Увеличиваем вложенность только ести тег не одиночный */
                if(!in_array($tagName, self::ALONE_TAGS)) {
                    $nesting++;
                    $parentElement = $element;
                }
                if($tagName == 'script') {
                    $isScript = true;
                }
                $tagName = '';
                $tagOpen = false;
                $isAttr = false;
                $attrName = '';
                $attrValue = '';
                $attrInQuotes = false;
                $isAttrValue = false;
                $isAttrValueArea = false;
                $attrList = [];
            }
            /** Ищем начало атрибутов */
            elseif($tagOpen && $tagName && !$isAttr && self::symIsSpace($sym)) {
                $isAttr = true;
            }
            /** Ищем закрытие значения атрибута */
            elseif($tagOpen && $isAttrValueArea && $isAttrValue &&
                /** Значение было в кавычках */
                ($attrInQuotes && in_array($sym, ['"', '\'']) ||
                    /** Значение было без кавычек - находим первый пробел */
                    !$attrInQuotes && self::symIsSpace($sym))
            ) {
                $attrInQuotes = false;
                $isAttrValue = false;
                $isAttrValueArea = false;
                $attrList[$attrName] = $attrValue;
                $attrName = '';
                $attrValue = '';
            }
            /** Ищем начало значения атрибута */
            elseif($tagOpen && $isAttrValueArea && !$isAttrValue && !self::symIsSpace($sym)) {
                if(in_array($sym, ['"', '\''])) {
                    $attrInQuotes = true;
                    continue;
                }
                $attrValue .= $sym;
                $isAttrValue = true;
            }
            /** Собираем значение атрибута */
            elseif($tagOpen && $isAttrValue) {
                $attrValue .= $sym;
            }
            /** Ищем начало значения */
            elseif($tagOpen && $isAttr && $attrName && $sym === '=') {
                $isAttrValueArea = true;
            }
            /** Собираем имя атрибута */
            elseif($tagOpen && $isAttr && !$isAttrValueArea && !self::symIsSpace($sym)) {
                $attrName .= $sym;
            }
            /** Если закрывающий тег */
            elseif($tagOpen && !$tagName && $sym == '/') {
                $isCloseTag = true;
            }
            /** Завершение закрывающего тега */
            elseif($tagOpen && $tagName && $sym == '>' && $isCloseTag) {
                if(!in_array($tagName, self::ALONE_TAGS) && $nesting > 0) {
                    $parentElement = $parentElement->parent();
                    $nesting--;
                }
                $tagName = '';
                $tagOpen = false;
                $isAttr = false;
                $isCloseTag = false;
            }
            /** Собирает атрибуты */
            elseif($tagOpen && !$isAttr && self::symIsSpace($sym)) {
                $isAttr = true;
            }
            /** Собираем имя открытого тега */
            elseif($tagOpen && !$isAttr) {
                $tagName .= $sym;
            }
            /** Начало тега (если нет текста) */
            elseif(!$tagOpen && $sym === '<' && !$text) {
                $tagName = '';
                $tagOpen = true;
            }
            /** Начало тега (если есть текст) */
            elseif(!$tagOpen && $sym === '<') {
                $element = new Element('', $text);
                $parentElement->append($element);
                $text = '';
                $tagName = '';
                $tagOpen = true;
            }
            /** Если пробел, таб, null или перенос строки */
            elseif(!$text && (!$sym || self::symIsSpace($sym))) {
                continue;
            }
            /** Собираем текст */
            elseif(!$tagOpen) {
                $text .= $sym;
            }
            else {
                continue;
            }
        }
        return $finalDOMElement->getChildren();
    }

}