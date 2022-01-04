<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 05.11.20 05:44:22
 */

/** @noinspection PhpMethodMayBeStaticInspection */
declare(strict_types = 1);
namespace dicr\tests;

use PHPUnit\Framework\TestCase;

/**
 * Class TelegramEntityTest
 */
class JsonEntityTest extends TestCase
{
    public function testAttributeFields() : void
    {
        $sampleFields = TestJsonEntity::sampleAttributeFields();
        $actualFields = (new TestJsonEntity())->attributeFields();

        self::assertEquals($sampleFields, $actualFields);
    }

    public function testGetJson() : void
    {
        $sampleJson = TestJsonEntity::sampleJson();
        $actualJson = TestJsonEntity::sampleEntity()->json;
        self::assertEquals($sampleJson, $actualJson);
    }

    public function testSetJson() : void
    {
        $sampleEntity = TestJsonEntity::sampleEntity();

        $actualEntity = new TestJsonEntity([
            'json' => TestJsonEntity::sampleJson()
        ]);

        self::assertEquals($sampleEntity, $actualEntity);
    }
}
