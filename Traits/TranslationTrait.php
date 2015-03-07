<?php

namespace ItBlaster\TranslationBundle\Traits;

/**
 * Вспомогательные методы для работы
 * с языковыми версиями
 *
 * Class TranslationTrait
 * @package ItBlaster\TranslationBundle\Trait
 */
trait TranslationTrait {

    protected $container;

    /**
     * Да да, тот самый контейнер, про который вы подумали
     *
     * @return mixed
     */
    protected function getContainer()
    {
        if (!$this->container) {
            $kernel = new \AppKernel('prod', false);
            $kernel->boot();
            $this->container = $kernel->getContainer();
        }
        return $this->container;
    }

    /**
     * Список локалей из конфига "it_blaster_translation.locales"
     *
     * @return array
     */
    protected function getLocales()
    {
        return $this->getContainer()->getParameter("it_blaster_translation.locales");
    }

    /**
     * Список локалей из конфига "it_blaster_translation.locales"
     *
     * @return array
     */
    protected function getSlugLocales()
    {
        return $this->getContainer()->getParameter("it_blaster_translation.slug_locales");
    }
}