<?php

namespace ItBlaster\TranslationBundle\Behavior;

/**
 * Используется только в модели Translation
 *
 * Class TranslationModelBehavior
 * @package ItBlaster\TranslationBundle\Behavior
 */
class TranslationModelBehavior extends TranslationBehavior
{
    protected $get_column_method;

    /**
     * @throws InvalidArgumentException
     */
    public function modifyTable()
    {
        $this->get_column_method = 'get'.$this->getColumnForParameter('primary_string')->getPhpName();
    }

    /**
     * добавляем методы в модель
     *
     * @param $builder
     * @return string
     */
    public function objectMethods($builder)
    {
        $this->builder = $builder;
        $script = '';
        $this->getFieldTranslation($script);    //методы переводов указанного поля

        return $script;
    }

    public function getFieldTranslation(&$script)
    {
        $locales = $this->getContainer()->getParameter("it_blaster_translation.locales");
        $field = $this->getColumnForParameter('primary_string');
        foreach ($locales as $locale) {
            $script .= '
/**
 * Возврашает '.$field.' в локале '.$locale.'
 *
 * @return string
 */
public function '.$this->get_column_method.$locale.'()
{
    return $this->setLocale("'.$locale.'")->'.$this->get_column_method.'();
}
    ';
        }

    }
}