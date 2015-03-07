<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace ItBlaster\TranslationBundle\Translation\Dumper;

use ItBlaster\TranslationBundle\Model\Translation;
use ItBlaster\TranslationBundle\Model\TranslationQuery;
use ItBlaster\TranslationBundle\Traits\TranslationTrait;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\Dumper\ArrayStructureDumper;
use JMS\TranslationBundle\Util\Writer;
use JMS\TranslationBundle\Model\MessageCatalogue;

class PhpDumper extends ArrayStructureDumper
{
    use TranslationTrait;

    private $writer;
    protected $catalogue;
    protected $locale;

    public function __construct()
    {
        $this->writer = new Writer();
    }

    public function dump(MessageCatalogue $catalogue, $domain = 'messages')
    {
        $this->catalogue = $catalogue;
        $this->locale = $catalogue->getLocale();
        return parent::dump($catalogue, $domain);
    }

    protected function dumpStructure(array $structure)
    {
        $this->writer
            ->reset()
            ->writeln('<?php')
            ->writeln('return \Itblaster\TranslationBundle\Model\TranslationPeer::getListForLocale("' . $this->locale . '");')
            ->indent();
        $this->dumpStructureRecursively($structure);

        $result = $this->writer
            ->outdent()
            ->getContent();
        return $result;
    }

    private function dumpStructureRecursively(array $structure)
    {
        $locales = $this->getLocales();
        $translation_strings = array();
        foreach ($locales as $locale) {
            $translation_strings[$locale] = array();
            $translation_list = TranslationQuery::create()->joinWithI18n($locale)->find();
            foreach ($translation_list as $translation_item) {
                /** @var Translation $translation_item */
                $translation_item->setLocale($locale);

                if ($translation_item->getTitle() === null) {
                    $translation_item->setTitle('')->save();
                }
                $translation_item->setLocale('en');
                $translation_strings[$locale][$translation_item->getTitle()] = $translation_item;
            }
        }

        foreach ($structure as $k => $v) {
            /** @var Message $v */
            if (!isset($translation_strings['en'][$k])) {
                $trans_obj = new Translation();
                $this->setParamsTransObj($trans_obj, 'en', $k);
                $translation_strings['en'][$k] = $trans_obj;
            }

            foreach ($locales as $locale) {
                if ($locale != 'en') {
                    if (!isset($translation_strings[$locale][$k])) {
                        /** @var Translation $trans_obj */
                        $trans_obj = $translation_strings['en'][$k];
                        $this->setParamsTransObj($trans_obj,$locale);
                    }
                }
            }

            $this->writer->indent();
            $this->writer->outdent();
        }
    }

    /**
     * Проставляет параметры у объекта перевода
     *
     * @param $trans_obj
     * @param $locale
     * @param string $title
     */
    private function setParamsTransObj(&$trans_obj, $locale, $title='')
    {
        $trans_obj
            ->setLocale($locale)
            ->setTitle($title)
            ->save();
    }
}