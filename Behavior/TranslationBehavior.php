<?php

namespace ItBlaster\TranslationBundle\Behavior;

class TranslationBehavior extends \Behavior
{
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
}