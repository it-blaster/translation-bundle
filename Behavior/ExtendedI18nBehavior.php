<?php

namespace ItBlaster\TranslationBundle\Behavior;

class ExtendedI18nBehavior extends TranslationBehavior
{
    /**
     * Add the slug_column to the current table
     */
    public function modifyTable()
    {
        $table = $this->getTable();
        $primary_string = $this->getParameter('primary_string');

        if(!$primary_string) {
            $this->exceptionError('Need set parameter "primary_string" in table '.$table->getName());
        }

        if(!$table->hasColumn($primary_string)) {
            $this->exceptionError('Not found column "'.$primary_string.'" in table '.$table->getName());
        }
    }

    public function objectMethods(\PHP5ObjectBuilder $builder)
    {
        $this->builder = $builder;
        $script = '';

        $this->addSortI18ns($script);

        return $script;
    }
}