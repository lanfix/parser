<?php

namespace lanfix\parser\src;

use Exception;

/**
 * Единица из DOM дерева
 * @property string $tag имя HTML тега
 * @property int $type тип элемента (тег, одиночный тег, текст)
 * @property int $classes список классов
 */
class Element
{

    /** Элемент является обычным HTML тегом */
    const TYPE_TAG = 1;
    /** Элемент является одиночным HTML тегом (не может иметь контента) */
    const TYPE_ALONE = 2;
    /** Элемент является блоком текста */
    const TYPE_TEXT = 3;

    /**
     * @var int уникальный id элемента
     */
    private $uniqueId = 0;

    /**
     * @var string имя тега
     */
    private $tag = '';

    /**
     * @var int тип элемента
     */
    private $type = 0;

    /**
     * Список атрибутов данного DOM элемента
     * @var object атрибуты тега [key => val]
     */
    private $attr = null;

    /**
     * @var array список классов элемента
     */
    private $classes = [];

    /**
     * Счетчик для генерации уникальных id элемента
     */
    private static $autoIncrement = 0;

    /**
     * Если тег одиночный, наподобие <br>, то $contain == null,
     * если строка, то контент - обычный текст
     * @var self[]|string|null внутреннее содержимое тега
     */
    private $contain = null;

    /**
     * @var Element|null родительский элемент
     */
    private $parent;

    /**
     * Подходит ли данный класс под селектор
     * @param string $selector
     * @return bool
     */
    public function isCompare(string $selector)
    {
        /** Распозначем что за селектор: */
        /** Выборка по ID */
        if(Common::match('/[#].+/ui', $selector)) {
            Common::cut('/[#]/ui', $selector);
            if($this->getAttribute('id') === $selector) {
                return true;
            }
        }
        /** Выборка по классу */
        elseif(Common::match('/[\.].+/ui', $selector)) {
            Common::cut('/[\.]/ui', $selector);
            if(in_array($selector, $this->classes)) {
                return true;
            }
        }
        /** Выборка по имени тега */
        else {
            if($selector == '*') {
                return true;
            }
            $selector = strtolower($selector);
            if($this->tag === $selector) {
                return true;
            }
        }
        return false;
    }

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
        /** Выставляем тип в зависимости от содержимого контента */
        $this->type = is_array($contain) ? 1 : ($contain === null ? 2 : 3);
        /** Обрабатываем классы у элемента */
        if($this->isTag() && $this->attr->class) {
            $classesString = $this->attr->class;
            Common::trim($classesString);
            $this->attr->class = $classesString;
            foreach(explode(' ', $classesString) ?: [] as $className) {
                $this->classes[] = $className;
            }
        }
    }

    /**
     * Добавляем магии
     * @param $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        if(in_array($name, ['tag', 'type', 'classes'])) {
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
     * Получить содержимое.
     * Возвращеет пустой массив если ничего не найдено при выборке по классу.
     * Возвращает null если ичего не найдено при выборке в остальных случаях.
     * @param string $selector
     * @return self[]|self|string|null
     * @throws Exception
     */
    public function getChildren(string $selector = null)
    {
        if($this->isAloneTag() || $this->isText()) {
            throw new Exception('You can get children only from usual html tag');
        }
        if($selector === null) {
            return $this->contain;
        }
        foreach($this->contain as $element) {
            if($element->isCompare($selector)) {
                return $element;
            }
        }
        /** Распозначем что за селектор: */
        /** Выборка по ID */
        if(Common::match('/[#].+/ui', $selector)) {
            Common::cut('/[#]/ui', $selector);
            foreach($this->contain as $element) {
                if($selector === $element->getAttribute('id')) {
                    return $element;
                }
            }
        }
        /** Выборка по классу */
        elseif(Common::match('/[\.].+/ui', $selector)) {
            Common::cut('/[\.]/ui', $selector);
            foreach($this->contain as $element) {
                if(in_array($selector, $element->classes)) {
                    return $element;
                }
            }
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
        return null;
    }

    /**
     * Найти элемент по селектору.
     * ------------------------------
     * .content .search-results .title
     * #main .page-contain span
     * ------------------------------
     * @param string $selector
     * @return Element[]
     */
    public function find(string $selector)
    {
        $searchedElements = [];
        $baseSelectorChain = $selector;
        $selectors = explode(' ', $selector);
        $activeSelector = array_shift($selectors);
        $selector = implode(' ', $selectors);
        foreach($this->contain as $element) {
            /** @var Element $element */
            if(!$element->isTag()) continue;
            if($element->isCompare($activeSelector)) {
                $searchedElements = array_merge(
                    ($searchedElements),
                    /**
                     * Если еще остались какие-либо селекторы в цепочке, то
                     * продолжаем искать глубже.
                     * Если это элемент, который подошел под конечный селектр
                     * в цепочке - закладываем его в ответ.
                     */
                    ($selector ? $this->find($selector) : [$element])
                );
            }
            foreach($element->find($baseSelectorChain) as $nestedElement) {
                $searchedElements[] = $nestedElement;
            }
        }
        return $searchedElements;
    }

    /**
     * Найти первый элемент с таким селектором в DOM
     * @param string $selector
     * @return Element|null
     */
    public function findOne(string $selector)
    {
        return $this->find($selector)[0] ?? null;
    }

    /**
     * Является ли элемент просым html тегом
     * @return bool
     */
    public function isTag()
    {
        return $this->type === self::TYPE_TAG;
    }

    /**
     * Является ли элемент одиночным html тегом
     * @return bool
     */
    public function isAloneTag()
    {
        return $this->type === self::TYPE_ALONE;
    }

    /**
     * Является ли элемент блоком текста
     * @return bool
     */
    public function isText()
    {
        return $this->type === self::TYPE_TEXT;
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
     * Получить HTML атрибут по имени
     * @param string $key
     * @return string|object|null
     */
    public function getAttribute($key = null)
    {
        if(!$key) return $this->attr;
        return $this->attr->{$key} ?? null;
    }

    /**
     * Дабавить HTML атрибут для этемента
     * @param string $key
     * @param string $value
     */
    public function addAttribute(string $key, string $value = '')
    {
        $this->attr->{$key} = $value;
    }

    /**
     * Получить текст из данного элемента, а также вложенных
     * @return string
     */
    public function asText()
    {
        $finalText = '';
        foreach($this->contain as $element) {
            /** @var Element $element */
            if($element->isText()) {
                $finalText .= $element->contain;
                continue;
            }
            if(!$element->isTag()) {
                continue;
            }
            $finalText .= ' ' . $element->asText();
        }
        Common::trim($finalText);
        return $finalText;
    }

    /**
     * Получить элемент в виде HTML
     * @param bool $saveAttr
     * @return string
     */
    public function asHtml($saveAttr = true)
    {
        $finalText = '';
        /** Если тег - то есть содержимое */
        if($this->isTag()) {
            $finalText .= '<' . $this->tag;
            if($saveAttr) {
                /** Накручиваем атрибуты */
                foreach($this->attr as $attrName => $attrVal) {
                    $finalText .= ' ' . $attrName . '="' . $attrVal . '"';
                }
            }
            $finalText .= '>';
            foreach($this->contain as $element) {
                /** Обходим вложенные */
                $finalText .= $element->asHtml($saveAttr);
            }
            $finalText .= '</' . $this->tag . '>';
        }
        /** Если одиночный тег - рендерим только его */
        elseif($this->isAloneTag()) {
            $finalText .= '<' . $this->tag . '>';
        }
        /** Если текст - рендерим только текст */
        else {
            $finalText .= $this->contain;
        }
        return $finalText;
    }

    public function deleteContain()
    {
        $this->contain = null;
    }

}