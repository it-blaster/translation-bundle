<?php

namespace ItBlaster\TranslationBundle\Behavior;
use ItBlaster\TranslationBundle\Traits\TranslationTrait;

/**
 * Базовый класс
 *
 * Class TranslationBehavior
 * @package ItBlaster\TranslationBundle\Behavior
 */
abstract class TranslationBehavior extends \Behavior
{
    use TranslationTrait;

    protected $parameters = array(
        'primary_string'  => '',
    );

    /**
     * Метож сортировки элементов языковых версий
     *
     * @param $script
     */
    protected function addSortI18ns(&$script)
    {
        $locales = $this->getLocales();
        $langs = "array(";
        foreach ($locales as $locale) {
            $langs.='"'.$locale.'",';
        }
        $langs.=')';

        $script .= '
/**
 * Сортирует элементы массива по порядку следования языков
 *
 * @param $elementsf
 * @return array
 */
protected function sortI18ns($elements) {
    if (count($elements)) {
        $result = new \PropelObjectCollection();
        $langs = array_flip('.$langs.');
        foreach($elements as $element) {
            $result[$langs[$element->getLocale()]] = $element;
        }
        $result->ksort();
        return $result;
    } else {
        return $elements;
    }
}
    ';
    }

    /**
     * Переопределение метода _toString
     *
     * @return string
     * @throws \Exception
     */
    protected function getToStringMethod()
    {
        $primary_string = $this->getParameter('primary_string');
        $i18n_languages = $this->getLocales();
        $primary_string_column =  count($i18n_languages) ? $primary_string : $this->getColumnForParameter('primary_string');
        $get_primary_string = 'get'.(count($i18n_languages) ? $this->CamelCase($primary_string) : $primary_string_column->getPhpName());

        if(!$primary_string_column) {
            $this->exceptionError('Not found column "'.$primary_string.'" in table '.$this->getTable()->getName());
        }

        $toString = '

    /**
     * Отдаём PrimaryString
     *
     * @return string
     */
    public function __toString() {';

        //есть языковые версии
        if (count($i18n_languages)) {
            $languages = 'array(';
            foreach ($i18n_languages as $lang) {
                $languages.='"'.$lang.'",';
            }
            $languages.=')';

            $toString .= '
        $to_string = $this->isNew() ? "Новая запись" : "";
        $languages = '.$languages.';
        foreach ($languages as $language) {
            $str = $this->setLocale($language)->'.$get_primary_string.'();
            if ($str) {
		        $to_string = $str;
                break;
            }
        }
        if (mb_strlen($to_string, "UTF-8")>26) {
            $to_string = mb_substr($to_string, 0, 26, "utf-8")."...";
        }
        ';
            if ($this->getTable()->hasColumn('slug')) {
                $toString .= '$to_string = $to_string ? $to_string : $this->getSlug();
        ';
            }
        $toString .= 'return $to_string;';
        } else { //нет языковых версий
            $toString .= '
        return $this->'.$get_primary_string.'() ? $this->'.$get_primary_string.'() : "Новая запись";';
        }
        $toString .= '
    }
    ';
        return $toString;
    }

    /**
     * Перелпределение метода getI18ns
     *
     * @return string
     */
    protected function getI18nsMethod()
    {
        $class_name = $this->CamelCase($this->getTable()->getName());
        $get_i18ns_method = '
    /**
     * Gets an array of '.$class_name.'I18n objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this FaqQuestionGroup is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param Criteria $criteria optional Criteria object to narrow the query
     * @param PropelPDO $con optional connection object
     * @return PropelObjectCollection|'.$class_name.'I18n[] List of '.$class_name.'I18n objects
     * @throws PropelException
     */
    public function get'.$class_name.'I18ns($criteria = null, PropelPDO $con = null)
    {
        $partial = $this->coll'.$class_name.'I18nsPartial && !$this->isNew();
        if (null === $this->coll'.$class_name.'I18ns || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->coll'.$class_name.'I18ns) {
                // return empty collections
                $this->init'.$class_name.'I18ns();
            } else {
                $coll'.$class_name.'I18ns = '.$class_name.'I18nQuery::create(null, $criteria)
                    ->filterBy'.$class_name.'($this)
                    ->find($con);
                $coll'.$class_name.'I18ns = $this->sortI18ns($coll'.$class_name.'I18ns);
                if (null !== $criteria) {
                    if (false !== $this->coll'.$class_name.'I18nsPartial && count($coll'.$class_name.'I18ns)) {
                      $this->init'.$class_name.'I18ns(false);

                      foreach ($coll'.$class_name.'I18ns as $obj) {
                        if (false == $this->coll'.$class_name.'I18ns->contains($obj)) {
                          $this->coll'.$class_name.'I18ns->append($obj);
                        }
                      }

                      $this->coll'.$class_name.'I18nsPartial = true;
                    }

                    $coll'.$class_name.'I18ns->getInternalIterator()->rewind();

                    return $coll'.$class_name.'I18ns;
                }

                if ($partial && $this->coll'.$class_name.'I18ns) {
                    foreach ($this->coll'.$class_name.'I18ns as $obj) {
                        if ($obj->isNew()) {
                            $coll'.$class_name.'I18ns[] = $obj;
                        }
                    }
                }

                $this->coll'.$class_name.'I18ns = $coll'.$class_name.'I18ns;
                $this->coll'.$class_name.'I18nsPartial = false;
            }
        }

        return $this->coll'.$class_name.'I18ns;
    }
        ';
        return $get_i18ns_method;
    }

    /**
     * Перевод из венгерского стиля в CamelCase
     *
     * @param $name
     * @return mixed
     */
    protected function CamelCase($name)
    {
        return ucfirst(\Propel\PropelBundle\Util\PropelInflector::camelize($name));
    }

    /**
     * Переопределяем метод __toString
     *
     * @param $script
     */
    public function objectFilter(&$script)
    {
        $to_string_method = $this->getToStringMethod();
        $get_i18ns_method = $this->getI18nsMethod();
        $i18ns_method_name = 'get'.$this->CamelCase($this->getTable()->getName()).'I18ns';

        $table = $this->getTable();
        $newToStringMethod = sprintf($to_string_method, $table->getName(), $table->getPhpName(), $table->getPhpName());
        $newI18nMethod = sprintf($get_i18ns_method, $table->getName(), $table->getPhpName(), $table->getPhpName());

        $parser = new \PropelPHPParser($script, true);
        $parser->replaceMethod('__toString', $newToStringMethod);
        $parser->replaceMethod($i18ns_method_name, $newI18nMethod);
        $script = $parser->getCode();
    }

    /**
     * Выкидываем эксепшн с ошибкой
     *
     * @param $error
     * @throws \Exception
     */
    protected function exceptionError($error)
    {
        throw new \Exception('<------ERROR------- '.$error.' ------ERROR------->');
    }
}