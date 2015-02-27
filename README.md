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

В файле `schema.yml` у таблицы прописываем бихейвор, например:
