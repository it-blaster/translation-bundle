<?php

namespace ItBlaster\TranslationBundle\Behavior;

class ExtendedSluggableBehavior extends \Behavior
{
    // default parameters value
    protected $parameters = array(
        'add_cleanup'     => 'true',
        'slug_column'     => 'slug',
        'slug_pattern'    => '',
        'replace_pattern' => '/\W+/', // Tip: use '/[^\\pL\\d]+/u' instead if you're in PHP5.3
        'replacement'     => '-',
        'separator'       => '-',
        'permanent'       => 'false',
        'scope_column'    => '',
        'primary_string'  => '',
        'i18n_languages'  => 'false', //если true, то нужно пытаться брать значение у языковой версии
    );

    /**
     * Add the slug_column to the current table
     */
    public function modifyTable()
    {
        $table = $this->getTable();
        $primary_string = $this->getParameter('primary_string');

        if(!$primary_string) {
            throw new \Exception('<------ERROR------- Need set parameter "primary_string" in table '.$table->getName().' ------ERROR------->');
        }

        if(!$table->hasColumn($primary_string)) {
            throw new \Exception('<------ERROR------- Not found column "'.$primary_string.'" in table '.$table->getName().' ------ERROR------->');
        }

        if (!$this->getTable()->containsColumn($this->getParameter('slug_column'))) {
            $this->getTable()->addColumn(array(
                'name' => $this->getParameter('slug_column'),
                'type' => 'VARCHAR',
                'size' => 255
            ));
            // add a unique to column
            $unique = new \Unique($this->getColumnForParameter('slug_column'));
            $unique->setName($this->getTable()->getCommonName() . '_slug');
            $unique->addColumn($this->getTable()->getColumn($this->getParameter('slug_column')));
            if ($this->getParameter('scope_column')) {
                $unique->addColumn($this->getTable()->getColumn($this->getParameter('scope_column')));
            }
            $this->getTable()->addUnique($unique);
        }
    }

    /**
     * Get the getter of the column of the behavior
     *
     * @return string The related getter, e.g. 'getSlug'
     */
    protected function getColumnGetter()
    {
        return 'get' . $this->getColumnForParameter('slug_column')->getPhpName();
    }

    /**
     * Get the setter of the column of the behavior
     *
     * @return string The related setter, e.g. 'setSlug'
     */
    protected function getColumnSetter()
    {
        return 'set' . $this->getColumnForParameter('slug_column')->getPhpName();
    }

    /**
     * Add code in ObjectBuilder::preSave
     *
     * @return string The code to put at the hook
     */
    public function preSave(\PHP5ObjectBuilder $builder)
    {
        $const = $builder->getColumnConstant($this->getColumnForParameter('slug_column'));
        $pattern = $this->getParameter('slug_pattern');
        $script = "
if (\$this->isColumnModified($const) && \$this->{$this->getColumnGetter()}()) {
    \$this->{$this->getColumnSetter()}(\$this->makeSlugUnique(\$this->{$this->getColumnGetter()}()));";

        if ($pattern && false === $this->booleanValue($this->getParameter('permanent'))) {
            $script .= "
} elseif (";
            $count = preg_match_all('/{([a-zA-Z]+)}/', $pattern, $matches, PREG_PATTERN_ORDER);

            foreach ($matches[1] as $key => $match) {
                $columnName = $this->underscore(ucfirst($match));
                $column = $this->getTable()->getColumn($columnName);
                if ((null == $column) && $this->getTable()->hasBehavior('symfony_i18n')) {
                    $i18n = $this->getTable()->getBehavior('symfony_i18n');
                    $column = $i18n->getI18nTable()->getColumn($columnName);
                }
                if (null == $column) {
                    throw new \InvalidArgumentException(sprintf('The pattern %s is invalid  the column %s is not found', $pattern, $match));
                }
                $columnConst = $builder->getColumnConstant($column);
                $script .= "\$this->isColumnModified($columnConst)" . ($key < $count - 1 ? " || " : "");
            }

            $script .= ") {
    \$this->{$this->getColumnSetter()}(\$this->createSlug());";
        }

        if (null == $pattern && false === $this->booleanValue($this->getParameter('permanent'))) {
            $script .= "
} else {
    \$this->{$this->getColumnSetter()}(\$this->createSlug());
}";
        } else {
            $script .= "
} elseif (!\$this->{$this->getColumnGetter()}()) {
    \$this->{$this->getColumnSetter()}(\$this->createSlug());
}";
        }

        return $script;
    }

    public function objectMethods(\PHP5ObjectBuilder $builder)
    {
        $this->builder = $builder;
        $script = '';
        if ('slug' != $this->getParameter('slug_column')) {
            $this->addSlugSetter($script);
            $this->addSlugGetter($script);
        }
        $this->addCreateSlug($script);
        $this->addCreateRawSlug($script);
        if ($this->booleanValue($this->getParameter('add_cleanup'))) {
            $this->addCleanupSlugPart($script);
        }
        $this->addLimitSlugSize($script);
        $this->addMakeSlugUnique($script);

        $this->sortI18ns($script);

        return $script;
    }

    /**
     * Метож сортировки элементов языковых версий
     *
     * @param $script
     */
    protected function sortI18ns(&$script)
    {
        $kernel = new \AppKernel('prod', false);
        $kernel->boot();
        $locales = $kernel->getContainer()->getParameter("locales");
        $langs = "array(";
        foreach ($locales as $locale) {
            $langs.='"'.$locale.'",';
        }
        $langs.=')';

        $script .= '
/**
 * Сортирует элементы массива по порядку следования языков
 *
 * @param $elements
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

    protected function addSlugSetter(&$script)
    {
        $script .= "
/**
 * Wrap the setter for slug value
 *
 * @param   string
 * @return  " . $this->getTable()->getPhpName() . "
 */
public function setSlug(\$v)
{
    return \$this->" . $this->getColumnSetter() . "(\$v);
}
";
    }

    protected function addSlugGetter(&$script)
    {
        $script .= "
/**
 * Wrap the getter for slug value
 *
 * @return  string
 */
public function getSlug()
{
    return \$this->" . $this->getColumnGetter() . "();
}
";
    }

    protected function addCreateSlug(&$script)
    {
        $script .= "
/**
 * Create a unique slug based on the object
 *
 * @return string The object slug
 */
protected function createSlug()
{
    \$slug = \$this->createRawSlug();
    \$slug = \$this->limitSlugSize(\$slug);
    \$slug = \$this->makeSlugUnique(\$slug);

    return \$slug;
}
";
    }

    protected function addCreateRawSlug(&$script)
    {
        $pattern = $this->getParameter('slug_pattern');
        $script .= "
/**
 * Create the slug from the appropriate columns
 *
 * @return string
 */
protected function createRawSlug()
{
    ";
        if ($pattern) {
            $script .= "return '" . str_replace(array('{', '}'), array('\' . $this->cleanupSlugPart($this->get', '()) . \''), $pattern) . "';";
        } else {
            $script .= "return \$this->cleanupSlugPart(\$this->__toString());";
        }
        $script .= "
}
";

        return $script;
    }

    public function addCleanupSlugPart(&$script)
    {
        $script .= "
/**
 * Cleanup a string to make a slug of it
 * Removes special characters, replaces blanks with a separator, and trim it
 *
 * @param     string \$slug        the text to slugify
 * @param     string \$replacement the separator used by slug
 * @return    string               the slugified text
 */
protected static function cleanupSlugPart(\$slug, \$replacement = '" . $this->getParameter('replacement') . "')
{
    \$slug = strtr(\$slug, array(
        'А' => 'A',  'Б' => 'B',  'В' => 'V',    'Г' => 'G',    'Д' => 'D', 'Е' => 'E', 'Ё' => 'E',  'Ж' => 'ZH',
        'З' => 'Z',  'И' => 'I',  'Й' => 'Y',    'К' => 'K',    'Л' => 'L', 'М' => 'M', 'Н' => 'N',  'О' => 'O',
        'П' => 'P',  'Р' => 'R',  'С' => 'S',    'Т' => 'T',    'У' => 'U', 'Ф' => 'F', 'Х' => 'KH', 'Ц' => 'TS',
        'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SHCH', 'Ь' => '',     'Ы' => 'Y', 'Ъ' => '',  'Э' => 'E',  'Ю' => 'YU',
        'Я' => 'YA', 'а' => 'a',  'б' => 'b',    'в' => 'v',    'г' => 'g', 'д' => 'd', 'е' => 'e',  'ё' => 'e',
        'ж' => 'zh', 'з' => 'z',  'и' => 'i',    'й' => 'y',    'к' => 'k', 'л' => 'l', 'м' => 'm',  'н' => 'n',
        'о' => 'o',  'п' => 'p',  'р' => 'r',    'с' => 's',    'т' => 't', 'у' => 'u', 'ф' => 'f',  'х' => 'kh',
        'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh',   'щ' => 'shch', 'ь' => '',  'ы' => 'y', 'ъ' => '',   'э' => 'e',
        'ю' => 'yu', 'я' => 'ya'
    ));

    // transliterate
    if (function_exists('iconv')) {
        \$slug = iconv('utf-8', 'us-ascii//TRANSLIT', \$slug);
    }

    // lowercase
    if (function_exists('mb_strtolower')) {
        \$slug = mb_strtolower(\$slug);
    } else {
        \$slug = strtolower(\$slug);
    }

    // remove accents resulting from OSX's iconv
    \$slug = str_replace(array('\'', '`', '^'), '', \$slug);

    // replace non letter or digits with separator
    \$slug = preg_replace('" . $this->getParameter('replace_pattern') . "', \$replacement, \$slug);

    // trim
    \$slug = trim(\$slug, \$replacement);

    if (empty(\$slug)) {
        return 'n-a';
    }

    return \$slug;
}
";
    }

    public function addLimitSlugSize(&$script)
    {
        $size = $this->getColumnForParameter('slug_column')->getSize();
        $script .= "

/**
 * Make sure the slug is short enough to accommodate the column size
 *
 * @param    string \$slug                   the slug to check
 * @param    int    \$incrementReservedSpace the number of characters to keep empty
 *
 * @return string                            the truncated slug
 */
protected static function limitSlugSize(\$slug, \$incrementReservedSpace = 3)
{
    // check length, as suffix could put it over maximum
    if (strlen(\$slug) > ($size - \$incrementReservedSpace)) {
        \$slug = substr(\$slug, 0, $size - \$incrementReservedSpace);
    }

    return \$slug;
}
";
    }

    public function addMakeSlugUnique(&$script)
    {
        $script .= "

/**
 * Get the slug, ensuring its uniqueness
 *
 * @param    string \$slug            the slug to check
 * @param    string \$separator       the separator used by slug
 * @param    int    \$alreadyExists   false for the first try, true for the second, and take the high count + 1
 * @return   string                   the unique slug
 */
protected function makeSlugUnique(\$slug, \$separator = '" . $this->getParameter('separator') . "', \$alreadyExists = false)
{";
        $getter = $this->getColumnGetter();
        $script .= "
    if (!\$alreadyExists) {
        \$slug2 = \$slug;
    } else {
        \$slug2 = \$slug . \$separator;";

        if (null == $this->getParameter('slug_pattern')) {
            $script .= "

        \$count = " . $this->builder->getStubQueryBuilder()->getClassname() . "::create()
            ->filterBySlug(\$this->$getter())
            ->filterByPrimaryKey(\$this->getPrimaryKey())
        ->count();

        if (1 == \$count) {
            return \$this->$getter();
        }";
        }

        $script .= "
    }

     \$query = " . $this->builder->getStubQueryBuilder()->getClassname() . "::create('q')
    ";
        $platform = $this->getTable()->getDatabase()->getPlatform();
        if ($platform instanceof \PgsqlPlatform) {
            $script .= "->where('q." . $this->getColumnForParameter('slug_column')->getPhpName() . " ' . (\$alreadyExists ? '~*' : '=') . ' ?', \$alreadyExists ? '^' . \$slug2 . '[0-9]+$' : \$slug2)";
        } elseif ($platform instanceof \MssqlPlatform) {
            $script .= "->where('q." . $this->getColumnForParameter('slug_column')->getPhpName() . " ' . (\$alreadyExists ? 'like' : '=') . ' ?', \$alreadyExists ? '^' . \$slug2 . '[0-9]+$' : \$slug2)";
        } elseif ($platform instanceof \OraclePlatform) {
            $script .= "->where((\$alreadyExists ? 'REGEXP_LIKE(' : '') . 'q." . $this->getColumnForParameter('slug_column')->getPhpName() . " ' . (\$alreadyExists ? ',' : '=') . ' ?' . (\$alreadyExists ? ')' : ''), \$alreadyExists ? '^' . \$slug2 . '[0-9]+$' : \$slug2)";
        } else {
            $script .= "->where('q." . $this->getColumnForParameter('slug_column')->getPhpName() . " ' . (\$alreadyExists ? 'REGEXP' : '=') . ' ?', \$alreadyExists ? '^' . \$slug2 . '[0-9]+$' : \$slug2)";
        }

        $script .="->prune(\$this)";

        if ($this->getParameter('scope_column')) {
            $scopeGetter = 'get' . $this->getColumnForParameter('scope_column')->getPhpName();
            $script .= "
            ->filterBy('{$this->getColumnForParameter('scope_column')->getPhpName()}', \$this->{$scopeGetter}())";
        }
        // watch out: some of the columns may be hidden by the soft_delete behavior
        if ($this->table->hasBehavior('soft_delete')) {
            $script .= "
        ->includeDeleted()";
        }
        $script .= "
    ;

    if (!\$alreadyExists) {
        \$count = \$query->count();
        if (\$count > 0) {
            return \$this->makeSlugUnique(\$slug, \$separator, true);
        }

        return \$slug2;
    }

    // Already exists
    \$object = \$query
        ->addDescendingOrderByColumn('LENGTH(" . $this->getColumnForParameter('slug_column')->getName() . ")')
        ->addDescendingOrderByColumn('" . $this->getColumnForParameter('slug_column')->getName() . "')
    ->findOne();

    // First duplicate slug
    if (null == \$object) {
        return \$slug2 . '1';
    }

    \$slugNum = substr(\$object->" . $getter . "(), strlen(\$slug) + 1);
    if ('0' === \$slugNum[0]) {
        \$slugNum[0] = 1;
    }

    return \$slug2 . (\$slugNum + 1);
}
";
    }

    public function queryMethods(\QueryBuilder $builder)
    {
        $this->builder = $builder;
        $script = '';

        if ($this->getParameter('slug_column') != 'slug') {
            $this->addFilterBySlug($script);
            $this->addFindOneBySlug($script);
        }

        return $script;
    }

    protected function addFilterBySlug(&$script)
    {
        $script .= "
/**
 * Filter the query on the slug column
 *
 * @param     string \$slug The value to use as filter.
 *
 * @return    " . $this->builder->getStubQueryBuilder()->getClassname() . " The current query, for fluid interface
 */
public function filterBySlug(\$slug)
{
    return \$this->addUsingAlias(" . $this->builder->getColumnConstant($this->getColumnForParameter('slug_column')) . ", \$slug, Criteria::EQUAL);
}
";
    }

    protected function addFindOneBySlug(&$script)
    {
        $script .= "
/**
 * Find one object based on its slug
 *
 * @param     string \$slug The value to use as filter.
 * @param     PropelPDO \$con The optional connection object
 *
 * @return    " . $this->builder->getStubObjectBuilder()->getClassname() . " the result, formatted by the current formatter
 */
public function findOneBySlug(\$slug, \$con = null)
{
    return \$this->filterBySlug(\$slug)->findOne(\$con);
}
";
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function underscore($string)
    {
        return strtolower(preg_replace(array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), array('\\1_\\2', '\\1_\\2'), strtr($string, '_', '.')));
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
     * Переопределение метода _toString
     *
     * @return string
     * @throws \Exception
     */
    protected function getToStringMethod()
    {
        $primary_string = $this->getParameter('primary_string');
        $i18n_languages = $this->getParameter('i18n_languages');
        $primary_string_column =  $i18n_languages!='false' ? $primary_string : $this->getColumnForParameter('primary_string');
        $get_primary_string = 'get'.($i18n_languages!='false' ? $this->CamelCase($primary_string) : $primary_string_column->getPhpName());

        if(!$primary_string_column) {
            throw new \Exception('<------ERROR------- Not found column "'.$primary_string.'" in table '.$this->getTable()->getName().' ------ERROR------->');
        }

        $toString = '

    /**
     * Отдаём PrimaryString
     *
     * @return string
     */
    public function __toString()
    {';

        //есть языковые версии
        if ($i18n_languages!='false') {
            $toString .= '
        $to_string = "Новая запись";
        $languages = explode(",","'.$i18n_languages.'");
        foreach ($languages as $language) {
            $str = $this->setLocale($language)->'.$get_primary_string.'();
            if ($str) {
                return $str;
            }
        }
        return $to_string;';
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
     * Gets an array of FaqQuestionGroupI18n objects which contain a foreign key that references this object.
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
}