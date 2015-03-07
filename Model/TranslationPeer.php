<?php

namespace ItBlaster\TranslationBundle\Model;

use ItBlaster\TranslationBundle\Model\om\BaseTranslationPeer;

class TranslationPeer extends BaseTranslationPeer
{
    /**
     * Список перевода для локали
     *
     * @param $locale
     * @return array
     */
    public static function getListForLocale($locale)
    {
        $translation_list_en = array();
        $translation_list_locale = array();
        $result = array();

        $translation_list = TranslationQuery::create()->joinWithI18n('en')->find();
        foreach ($translation_list as $translation_item) {
            /** @var Translation $translation_item */
            $translation_list_en[$translation_item->getId()] = $translation_item->getTitle();
        }

        $translation_list = TranslationQuery::create()->joinWithI18n($locale)->find();
        foreach ($translation_list as $translation_item) {
            /** @var Translation $translation_item */
            $translation_list_locale[$translation_item->getId()] = $translation_item->getTitle();
        }

        foreach ($translation_list_en as $id => $translation_title_en) {
            $result[$translation_title_en] = isset($translation_list_locale[$id]) && $translation_list_locale[$id] ?  $translation_list_locale[$id] : $translation_title_en;
        }

        return $result;
    }
}
