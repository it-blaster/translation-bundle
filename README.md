TranslationBundle
====================

[![Build Status](https://scrutinizer-ci.com/g/it-blaster/translation-bundle/badges/build.png?b=master)](https://scrutinizer-ci.com/g/it-blaster/translation-bundle/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/it-blaster/translation-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/it-blaster/translation-bundle/?branch=master)

Вспомогательный бандл для работы с языковыми версиями на сайте

Installation
------------

Добавьте <b>ItBlasterTranslationBundle</b> в `composer.json`:

```js
{
    "require": {
        "it-blaster/translation-bundle": "dev-master"
	},
}
```

Теперь запустите композер, чтобы скачать бандл командой:

``` bash
$ php composer.phar update it-blaster/translation-bundle
```

Композер установит бандл в папку проекта `vendor/it-blaster/translation-bundle`.

Далее подключите бандл в ядре `AppKernel.php`:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new ItBlaster\TranslationBundle\ItBlasterTranslationBundle(),
    );
}
```

В `app/config/config.yml` переопределяеем путь до класса propel-бихейвора <b>sluggable</b>

``` bash
 propel:
     ...
     behaviors:
         ...
         sluggable: ItBlaster\TranslationBundle\Behavior\ExtendedSluggableBehavior
         it_blaster_translation_model: ItBlaster\TranslationBundle\Behavior\TranslationModelBehavior
```

В файле `schema.yml` у таблицы прописываем бихейвор с указанием параметра `primary_string`, на основе которой будет формироваться `slug`. Например:
``` bash
    <behavior name="sluggable">
        <parameter name="primary_string" value="title" />
    </behavior>
```

Если вам создавать `slug` у таблицы не нужно, а нужно только выводить поля языковых версий в правильном порядке, то достаточно прописать бихейвор `it_blaster_i18n`:
``` bash
        <behavior name="it_blaster_i18n">
            <parameter name="primary_string" value="question" />
        </behavior>
```

И прописать в config.yml его подключение:
``` bash
 propel:
     ...
     behaviors:
         ...
         it_blaster_i18n: ItBlaster\TranslationBundle\Behavior\ExtendedI18nBehavior
```

Бихейвор `ExtendedSluggableBehavior` уже включает в себя методы из бихейвора `ExtendedI18nBehavior`, поэтому вместе их прописывать в схеме не нужно.

Если вы используете языковый версии (i18n), необходимо в файле `config.yml` указать параметры `it_blaster_translation.locales` и `it_blaster_translation.slug_locales`.
``` bash
it_blaster_translation:
    locales: ['ru', 'en','uk','cs']
    slug_locales: ['en','ru']
```
Параметр `it_blaster_translation.locales` отвечает за порядок вывода полей в форме редактирвоания в CMS.
Параметр `it_blaster_translation.slug_locales` отвечает за порядок языков, на основе которых будет формироваться slug. Если значение по первому языку не заполнено, система будет сформировать slug на основе значения следующей языковой версии.

Таблица переводов
-------
Необходимо в папке проекта src создать файлы переводов <b>messages.`locale`.php</b> и <b>validators.`locale`.php</b> с содержимым:
``` bash
<?php
return \ItBlaster\TranslationBundle\Model\TranslationPeer::getListForLocale("LOCALE");
```

Например, для английского языка нужно создать файлы:
1. src\App\MainBundle\Resources\translations\messages.en.php
2. src\App\MainBundle\Resources\translations\validators.en.php

Со следующим содержимым:
``` bash
<?php
return \ItBlaster\TranslationBundle\Model\TranslationPeer::getListForLocale("en");
```

Для того, чтобы наполнить таблицу переводов необходимо запустить индексирующий таск в консоле:
``` bash
'php app/console translation:extract en --dir=./src/ --output-dir=./src/App/MainBundle/Resources/translations/ --exclude-name="*.php" --output-format="php"'
```

Credits
-------

It-Blaster <it-blaster@yandex.ru>