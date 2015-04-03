<?php

namespace ItBlaster\TranslationBundle\Model;

use ItBlaster\TranslationBundle\Model\om\BaseTranslation;

class Translation extends BaseTranslation
{
    public function getAliasShort()
    {
        $alias = $this->getAlias();
        if (mb_strlen($alias, "UTF-8")>26) {
            $alias = mb_substr($alias, 0, 26, "utf-8")."...";
        }
        return $alias;
    }
}
