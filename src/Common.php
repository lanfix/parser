<?php

namespace src;

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
     * @param string $htmlContainPointer ссылка на переменную с контентом
     */
    public static function cut(string $regExp, string &$htmlContainPointer)
    {
        $htmlContainPointer = preg_replace($regExp, '', $htmlContainPointer);
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
        /** Текст внутри тегов */
        $text = '';
        /** @var Element $nowElement */
        $parentElement = $finalDOMElement;
        /** Парсим HTML */
        for($iter = 0; $iter < $htmlLength; $iter++) {
            $sym = $html[$iter];
            /** Завершение ооткрывающего тега */
            if($tagOpen && $sym === '>' && !$isCloseTag && (
                /** Атрибуты и не начинались */
                (!$isAttr) ||
                /** Если '>' встретилась внутри кавычек - пропускаем */
                ($isAttr && !$attrInQuotes)
            )) {
                $tagName = strtolower($tagName);
                $element = new Element($tagName, [], $attrList);
                $parentElement->append($element);
                /** Увеличиваем вложенность только ести тег не одиночный */
                if(!in_array($tagName, self::ALONE_TAGS)) {
                    $nesting++;
                    $parentElement = $element;
                }
                $tagName = '';
                $tagOpen = false;
                $isAttr = false;
                $attrList = [];
            }
            /** Ищем начало атрибутов */
            elseif($tagOpen && !$isAttr && self::symIsSpace($sym)) {
                $isAttr = true;
            }
            /** Ищем закрытие значения атрибута */
            elseif($tagOpen && $isAttrValue &&
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
            elseif($tagOpen && $isAttrValueArea && !self::symIsSpace($sym)) {
                if(in_array($sym, ['"', '\''])) {
                    $attrInQuotes = true;
                    continue;
                }
                $isAttrValue = true;
                $attrValue .= $sym;
            }
            /** Ищем начало значения */
            elseif($tagOpen && $isAttr && $attrName && $sym === '=') {
                $isAttrValueArea = true;
            }
            /** Собираем имя атрибута */
            elseif($tagOpen && $isAttr && !self::symIsSpace($sym)) {
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