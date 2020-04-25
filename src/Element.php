<?php

namespace src;

/** DOM элемент */
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
     * @return self[]|string|null
     */
    public function getChildren()
    {
        return $this->contain;
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