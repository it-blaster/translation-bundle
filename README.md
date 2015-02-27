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

В app/config/config.yml переопределяеем путь до класса propel-бихейвора sluggable

``` bash
 propel:
     ...
     behaviors:
         ...
         sluggable: ItBlaster\TranslationBundle\Behavior\ExtendedSluggableBehavior
```

В файле `schema.yml` у таблицы прописываем бихейвор с указанием параметра `primary_string`, на основе которой будет формироваться slug. Например:
``` bash
    <behavior name="sluggable">
        <parameter name="primary_string" value="title" />
    </behavior>
```

Если вы используете языковый версии (i18n), необходимо указать параметр `i18n_languages` с указанием языков, по переводам которых будет формироваться slug. Если по первому языку нет значения, система будет пытаться сформировать slug на основе следующего перевода.
``` bash
    <behavior name="sluggable">
        <parameter name="primary_string" value="title" />
        <parameter name="i18n_languages" value="en,ru" />
    </behavior>
```