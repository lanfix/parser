<?php

namespace src;

use Exception;

/**
 * DOM элемент
 * @property string $tag имя HTML тега
 */
class Element
{

    /**
     * @var int уникальный id элемента
     */
    private $uniqueId = 0;

    /**
     * @var string имя тега
     */
    private $tag = '';

    /**
     * Если тег одиночный, наподобие <br>, то $contain == null
     * Если строка, то контент - обычный текст
     * @var self[]|string|null внутреннее содержимое тега
     */
    private $contain = null;

    /**
     * Список атрибутов данного DOM элемента
     * @var object атрибуты тега [key => val]
     */
    private $attr = null;

    /**
     * Счетчик для генерации уникальных id элемента
     */
    private static $autoIncrement = 0;

    /**
     * @var Element|null родительский элемент
     */
    private $parent;

    /**
     * @param string $tagName
     * @param array|string|null $contain
     * @param array $attrs
     */
    public function __construct(string $tagName, $contain = null, array $attrs = [])
    {
        $this->uniqueId = self::$autoIncrement;
        $this->tag = $tagName;
        $this->contain = $contain;
        $this->attr = (object)$attrs;
        self::$autoIncrement++;
    }

    /**
     * Добавляем магии
     * @param $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        if(in_array($name, ['tag'])) {
            return $this->{$name};
        }
        throw new Exception('Property "'.$name.'" is not defined');
    }

    /**
     * Метод вызывается перед добавлением в другой объект
     * @param Element $parent
     */
    public function beforeAppend(Element &$parent)
    {
        $this->parent = $parent;
    }

    /**
     * Добавить новый элемент внутрь текущего
     * @param Element $element
     */
    public function append(self $element)
    {
        $element->beforeAppend($this);
        $this->contain[] = $element;
    }

    /**
     * Получить содержимое
     * @param string $selector
     * @return self[]|string|null
     */
    public function getChildren(string $selector = null)
    {
        if($selector === null) {
            return $this->contain;
        }
        /** Распозначем что за селектор: */
        /** Выборка по ID */
        if(Common::match('/[#].+/ui', $selector)) {
            // TODO
        }
        /** Выборка по классу */
        elseif(Common::match('/[\.].+/ui', $selector)) {
            // TODO
        }
        /** Выборка по имени тега */
        else {
            $selector = strtolower($selector);
            foreach($this->contain as $element) {
                if($element->tag == $selector) {
                    return $element;
                }
            }
        }
    }

    /**
     * Получить родительский элемент
     * @return self
     */
    public function parent()
    {
        return $this->parent;
    }

    /**
     * Дабавить HTML атрибут для этемента
     * @param string $key
     * @param string $value
     */
    public function addAttr(string $key, string $value = '')
    {
        $this->attr->{$key} = $value;
    }

}