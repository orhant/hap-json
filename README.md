# Модель данных JSON-структур для Yii2.
 
Позволяет конвертировать между вложенными структурами JSON-данных в модели Yii2.
 
- позволяет сопоставлять названия аттрибутов с названиями полей в JSON
- позволяет создавать вложенные объекты
- позволяет определить пользовательские функции для конвертирования значений аттрибутов из/в JSON

Пример:

```php

/**
 * Пример модели телефона
 */
class Phone extends dicr\json\JsonEntity 
{
    /** @var ?int номер телефона */
    public $number;

    /**
     * {@inheritDoc}
     * Пользовательские функции для конвертирования некоторых аттрибутов в JSON
     */
    public function attributesToJson() : array
    {
        return [        
            // конвертируем в формат +X (XXX) XXX-XX-XX при выводе в JSON
            'number' => function($val) : ?string {
                return empty($val) ? null : Formatter::asPhone($val); // null не выводится в JSON
            }
        ];
    }

    /**
     * {@inheritDoc}
     * Пользовательские функции для конвертирования некоторых аттрибутов из JSON.
     */
    public function attributesFromJson() : array
    {
        return [
            // конвертируем телефон в int
            'number' => function($val) : ?int
            {
                return empty($val) ? null : (int)$val;
            }  
        ];   
    }
}

/**
 * Пример модели пользователя.
 */
class Customer extends dicr\json\JsonEntity
{
    /** @var ?string */
    public $fio;

    /** @var ?Phone один мобильный телефон */
    public $cellular;

    /** @var Phone[]|null рабочие телефоны */
    public $workPhones;

    /**
     * {@inheritDoc}
     * Пример переопределения названий аттрибутов и полей JSON
     */
    public function attributeFields() : array
    {
        return [
            'fio'   => 'name',
            'workPhones' => 'work_phones'
        ];    
    }

    /**
     * {@inheritDoc}
     * Пример определения типов моделей вложенных аттрибутов.
     */ 
    public function attributeEntities() : array
    {
        return [
            'cellular' => Phone::class,     // одна модель
            'workPhones' => [Phone::class]  // массив моделей
        ];
    }

    /**
     * {@inheritDoc}
     * Пример валидации вложенных аттрибутов
     */
    public function rules() : array
    {
        return [
            ['cellular', 'default'],
            ['cellular', EntityValidator::class, 'class' => Phone::class],

            // пример валидации массива вложенных объектов
            ['workPhones', 'default'],
            ['workPhones', EntityValidator::class, 'class' => [Phone::class]],
        ];    
    }
}
```

Пример JSON для модели:

```json
{
    "name": "Иван Васильевич",            // будет загружен в fio
    "cellular": {                         // будет конвертирован в Phone
        "number": "+7 (123) 456-78-93"    // будет конвертирован в int
    },
    "work_phones": [                      // будет загружен в workPhones[2]
        {
            "number": ""                  // пустое значение null
        },
        {
            "number": "123-45-67"         // будет конвертирован в (int)1234567
        }
    ]
}
```

Пример использования:

```php

// создаем модель и загружаем из JSON
$customer = new Customer([
    'json' => Json::decode($string)
]);

// выводим в JSON
echo Json::encode($customer->json);
```
