<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 19.11.20 20:54:49
 */

declare(strict_types = 1);

namespace dicr\json;

use JsonSerializable;
use RuntimeException;
use yii\base\Exception;
use yii\base\Model;
use yii\helpers\Inflector;

use function array_key_exists;
use function array_search;
use function in_array;
use function is_array;
use function is_callable;
use function is_scalar;
use function is_string;

/**
 * Модель данных для JSON-структуры.
 * Позволяет конвертировать между вложенными структурами JSON-данных в модели Yii.
 *
 * - позволяет сопоставлять названия аттрибутов с названиями полей в JSON
 * - позволяет создавать вложенные объекты
 * - позволяет определить пользовательские функции для конвертирования значений аттрибутов из/в JSON
 *
 * @property array $json конфигурация объекта в виде JSON
 */
abstract class JsonEntity extends Model implements JsonSerializable
{
    /**
     * Карта соответствия названий аттрибутов названиям полей данных JSON.
     *
     * Необходимо определить только те аттрибуты, название которых отличается
     * от названия полей в данных.
     *
     * Если аттрибуту соответствует поле JSON null или "", то аттрибут не выводится в JSON.
     *
     * По умолчанию составляет карту CamelCase => snake_case. Если это не нужно, то переопределить этот метод в
     * наследнике.
     *
     * @return string[] [attribute => json field]
     */
    public function attributeFields(): array
    {
        /** @var string[] $fields кжш значения */
        static $fields = [];

        $class = static::class;
        if (! isset($fields[$class])) {
            $fields[$class] = [];

            foreach ($this->attributes() as $attribute) {
                $field = Inflector::camel2id($attribute, '_');
                if ($field !== $attribute) {
                    $fields[$class][$attribute] = $field;
                }
            }
        }

        return $fields[$class];
    }

    /**
     * Классы дочерних объектов для конвертирования из JSON в значения характеристик.
     *
     * @return string[]|string[][] [attribute => string|array[1]]
     * Возвращает типы объектов аттрибутов:
     * - string $class - класс объекта JsonEntity в который конвертируются данные
     * - array [$class] - класс объекта JsonEntity элемента массива
     */
    public function attributeEntities(): array
    {
        return [];
    }

    /**
     * Пользовательские функции, переопределяющие функционал конвертирования значений аттрибутов в JSON.
     *
     * @return callable[] карта [attribute => function($attributeValue, string $attribute, Model $model)|mixed]
     * Функция должна принимать значение аттрибута модели и возвращать значение для JSON.
     * Вместо функции может быть просто JSON-значение аттрибута.
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function attributesToJson(): array
    {
        return [];
    }

    /**
     * Пользовательские функции, переопределяющие функционал конвертирования значений аттрибутов из JSON.
     *
     * @return callable[] карта [attribute => function($jsonValue, string $attribute, Model $model)]
     * Функция должна принимать значение JSON и конвертировать его в значение аттрибута.
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function attributesFromJson(): array
    {
        return [];
    }

    /**
     * Инициализирует вложенный объект данными.
     *
     * @param string $class класс вложенного объекта
     * @param object|array $data данные для инициализации
     * @return object переданный entity
     * @throws Exception
     */
    protected static function createChildEntity(string $class, $data): object
    {
        // создаем объект
        $entity = new $class();

        // конвертируем данные в массив
        $data = (array)$data;

        // инициализация объекта
        if ($entity instanceof self) {
            // JsonEntity инициализируем через setJson
            $entity->setJson($data);
        } elseif ($entity instanceof Model) {
            // модели инициализируем через setAttributes
            $entity->setAttributes($data);
        } else {
            // другие объекты инициализируем через установку значений напрямую
            foreach ($data as $key => $val) {
                $entity->{$key} = $val;
            }
        }

        return $entity;
    }

    /**
     * Рекурсивно конвертирует значение аттрибута в данные JSON.
     *
     * @param string $attribute название характеристики
     * @param mixed $value значение характеристики
     * @return mixed значение данных для JSON
     */
    protected function value2json(string $attribute, $value)
    {
        // используем пользовательское конвертирование значений
        $map = $this->attributesToJson();

        if (isset($map[$attribute])) {
            return is_callable($map[$attribute]) ?
                // вызываем функцию
                $map[$attribute]($value, $attribute, $this) :
                // используем значение
                $map[$attribute];
        }

        // скалярные и пустые значения возвращаем как есть
        if ($value === null || $value === '' || $value === [] || is_scalar($value) || empty($value)) {
            return $value;
        }

        // вложенный объект
        if ($value instanceof self) {
            return $value->getJson();
        }

        // Json Serializable сам предоставляет данные
        if ($value instanceof JsonSerializable) {
            return $value->jsonSerialize();
        }

        // массив обходим рекурсивно
        if (is_array($value)) {
            foreach ($value as $key => &$val) {
                $val = $this->value2json($attribute, $val);

                // не передаем null-значения
                if ($val === null) {
                    unset($value[$key]);
                }
            }

            unset($val);
        }

        return $value;
    }

    /**
     * Рекурсивно конвертирует данные JSON в значение аттрибута.
     *
     * @param string $attribute название аттрибута
     * @param mixed $data данные
     * @return mixed
     * @throws Exception
     */
    protected function json2value(string $attribute, $data)
    {
        // пользовательская функция
        $map = $this->attributesFromJson();
        if (isset($map[$attribute])) {
            return $map[$attribute]($data, $attribute, $this);
        }

        // пустые и скалярные данные возвращаем как есть
        if ($data === null || $data === '' || $data === [] || is_scalar($data) || empty($data)) {
            return $data;
        }

        // карта типов вложенных значений
        $entities = $this->attributeEntities();
        $class = $entities[$attribute] ?? null;

        // если задано конвертирование значения аттрибута
        if ($class !== null) {
            if (is_string($class)) {
                // создаем вложенный объект JsonEntity
                $data = static::createChildEntity($class, $data);
            } elseif (is_array($class) && ! empty($class[0])) {
                // массив объектов JsonEntity
                foreach ($data as &$v) {
                    // создаем вложенный объект - элемент массива
                    $v = static::createChildEntity($class[0], $v);
                }

                unset($v);
            } else {
                throw new RuntimeException('Неизвестный тип объекта аттрибута: ' . $attribute . ': ' . $class);
            }
        }

        return $data;
    }

    /**
     * Конфигурация объекта из данных JSON.
     *
     * @param array $json данные конфигурации
     * @param bool $skipUnknown пропускать неизвестные аттрибуты (иначе exception)
     * @throws Exception
     */
    public function setJson(array $json, bool $skipUnknown = true): void
    {
        // карта соответствия полей данных аттрибутам
        $map = $this->attributeFields();
        $attributes = $this->attributes();

        $data = [];

        // обходим все данные
        foreach ($json as $field => $d) {
            // получаем название аттрибута по имени поля данных в карте аттрибутов
            $attribute = array_search($field, $map, true);
            if ($attribute === false) {
                // если аттрибут помечен как пропускаемый
                if (array_key_exists($field, $map) && ((string)$map[$field]) === '') {
                    continue;
                }

                $attribute = $field;
            }

            // если аттрибут не найден
            if (! in_array($attribute, $attributes, true)) {
                // пропускаем неизвестные
                if ($skipUnknown) {
                    continue;
                }

                throw new Exception('Неизвестный аттрибут: ' . $attribute);
            }

            // конвертируем и устанавливаем значение
            $data[(string)$attribute] = $this->json2value($attribute, $d);
        }

        if (! empty($data)) {
            $this->setAttributes($data, false);
        }
    }

    /**
     * Возвращает JSON данные объекта.
     *
     * @return array данные JSON
     */
    public function getJson(): array
    {
        $json = [];
        $map = $this->attributeFields();

        foreach ($this->getAttributes() as $attribute => $value) {
            // получаем название поля из карты аттрибутов
            if (array_key_exists($attribute, $map)) {
                $field = $map[$attribute];

                // аттрибут пропускаемый
                if ($field === null || $field === '') {
                    continue;
                }
            } else {
                $field = $attribute;
            }

            // получаем значение данных
            $data = $this->value2json($attribute, $value);

            // пропускаем пустые значения
            if ($data !== null) {
                // сохраняем значение данных
                $json[$field] = $data;
            }
        }

        return $json;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->getJson();
    }
}
