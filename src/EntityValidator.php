<?php
/*
 * @copyright 2019-2020 hap http://hap.org
 * @author Igor A Tarasov <develop@hap.org>
 * @license MIT
 * @version 05.11.20 05:44:22
 */

declare(strict_types = 1);
namespace hap\json;

use hap\validate\AbstractValidator;
use hap\validate\ValidateException;
use yii\base\InvalidConfigException;
use yii\base\Model;

use function is_a;
use function is_array;

/**
 * Валидатор аттрибутов с вложенными моделями.
 *
 * Проверяет значение аттрибута, которое имеет тип объекта с заданным классом.
 * Кроме того, если значением является подклассом Model, то выполняется еще и его валидация validate().
 */
class EntityValidator extends AbstractValidator
{
    /**
     * @var string|string[]|null класс значения аттрибута.
     *
     * Если модель типа JsonEntity, то класс может быть взят из ее attributesEntities()
     *
     * Значение `class` может иметь тип:
     * - string с названием класса, например:
     * UserContact::class - поле содержит объект OrderContact
     *
     * - string[] массив из одной строки с названием класса, например:
     * [OrderProduct::class] - поле содержит массив объектов типа OrderProduct
     *
     * Подробности:
     * @link JsonEntity::attributeEntities()
     */
    private string|array|null $class = null;

    /**
     * Проверка и конвертирование объекта значения.
     *
     * @param object|array $val значение
     * @param string $class требуемый класс объекта
     * @throws ValidateException
     */
    private static function checkVal(object|array $val, string $class): mixed
    {
        // конвертируем из конфига
        if (is_array($val)) {
            $val = new $class($val);
        }

        // проверяем тип
        if (! is_a($val, $class)) {
            throw new ValidateException('Должен быть классом: ' . $class);
        }

        // дополнительная валидация моделей
        if (($val instanceof Model) && ! $val->validate()) {
            throw new ValidateException($val);
        }

        return $val;
    }

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function parseValue($value): mixed
    {
        // для пустых значений возвращаем null
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        // проверяем задан ли класс значения
        if ($this->class === null) {
            throw new InvalidConfigException('class');
        }

        // если тип значения - массив объектов
        if (is_array($this->class)) {
            if (! is_array($value)) {
                throw new ValidateException('значение должно быть массивом');
            }

            foreach ($value as &$val) {
                $val = self::checkVal($val, $this->class[0]);
            }

            unset($val);
        } else {
            $value = self::checkVal($value, $this->class);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function validateAttribute($model, $attribute): void
    {
        // обновляем класс из аттрибутов модели
        if ($model instanceof JsonEntity) {
            $entities = $model->attributeEntities();
            if (isset($entities[$attribute])) {
                $this->class = $entities[$attribute];
            }
        }

        parent::validateAttribute($model, $attribute);
    }
}
