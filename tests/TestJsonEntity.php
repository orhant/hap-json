<?php
/*
 * @copyright 2019-2020 hap http://hap.org
 * @author Igor A Tarasov <develop@hap.org>
 * @license MIT
 * @version 05.11.20 05:44:22
 */

declare(strict_types = 1);
namespace hap\tests;

use hap\json\JsonEntity;

/**
 * JsonEntity для тестирования.
 */
class TestJsonEntity extends JsonEntity
{
    public ?int $id = null;

    /** @var ?int[] */
    public ?array $ids = null;

    /** @var ?int аттрибут с другим названием в JSON */
    public ?int $my_name = null;

    /** @var ?string аттрибут в CamelCase */
    public ?string $entityTitle = null;

    /** @var ?self дочерний объект */
    public ?TestJsonEntity $child = null;

    /** @var ?self[] массив дочерних объектов */
    public ?array $list = null;

    /**
     * @inheritDoc
     */
    public function attributeFields() : array
    {
        // тестируем подмену названия поля в данных JSON
        return array_merge(parent::attributeFields(), [
            'my_name' => 'name'
        ]);
    }

    /**
     * @inheritDoc
     */
    public function attributeEntities() : array
    {
        return [
            // дочерний объект
            'child' => self::class,

            // дочерний массив объектов
            'list' => [self::class]
        ];
    }

    /**
     * Эталонная карта соответствия названий аттрибутов названиям полей JSON.
     *
     * @return string[]
     */
    public static function sampleAttributeFields() : array
    {
        return [
            'my_name' => 'name',
            'entityTitle' => 'entity_title'
        ];
    }

    /**
     * Эталонный JSON, соответствующий эталонному объекту.
     *
     * @return array
     */
    public static function sampleJson() : array
    {
        return [
            // поле данных с пустым значением
            'ids' => [],

            // поле данных с другим названием аттрибута
            'name' => 'Test name',

            // поле данных в snake-case
            'entity_title' => 'Test title',

            // дочерний объект
            'child' => [
                'name' => 'Child Name',

                // вложенный объект
                'child' => [
                    'name' => 'Second Level Object'
                ]
            ],

            // массив вложенных объектов
            'list' => [
                ['name' => 'List Name 1'],
                ['entity_title' => 'List Name 2']
            ]
        ];
    }

    /**
     * Эталонный объект, соответствующий эталонному JSON.
     *
     * @return TestJsonEntity
     */
    public static function sampleEntity() : TestJsonEntity
    {
        return new self([
            // поле данных с пустым значением
            'ids' => [],

            // поле данных с другим названием поля в JSON
            'my_name' => 'Test name',

            // поле данных в CamelCase
            'entityTitle' => 'Test title',

            // вложенный объект
            'child' => new self([
                'my_name' => 'Child Name',

                // вложенный объект
                'child' => new self([
                    'my_name' => 'Second Level Object'
                ])
            ]),

            // массив вложенных объектов
            'list' => [
                new self([
                    'my_name' => 'List Name 1'
                ]),
                new self([
                    'entityTitle' => 'List Name 2'
                ]),
            ]
        ]);
    }
}
