# TranslationBundle
Вспомогательный бандл для работы с языковыми версиями на сайте

## Установка

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