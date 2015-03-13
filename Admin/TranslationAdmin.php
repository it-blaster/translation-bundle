<?php

namespace ItBlaster\TranslationBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Bridge\Propel1\Form\Type\TranslationCollectionType;
use Symfony\Bridge\Propel1\Form\Type\TranslationType;

class TranslationAdmin extends Admin
{
    protected $datagridValues = array(
        '_page'       => 1,
        '_per_page'   => 1000,
    );
    protected $perPageOptions = array(1000, 2000);
    protected $maxPerPage = 1000;
    protected $maxPageLinks = 1000;

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper->add('alias', null, array(
            'sortable' => false
        ));
        foreach ($this->getConfigurationPool()->getContainer()->getParameter('it_blaster_translation.locales') as $locale) {
            $listMapper->add('getTitle'.$locale, null, array(
                'label'     =>  $locale,
                'sortable'  => false
            ));
        }

        $listMapper->add('_action', 'actions', array(
            'label'     => 'Редактирование',
            'actions'   => array(
                'edit'      => array(),
                'delete'    => array(),
            )
        ))
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('Alias', null, array(
                'attr' => array(
                    'maxlength' => 255
                )
            ))
            ->add('TranslationI18ns', new TranslationCollectionType(), array(
                'label'     => FALSE,
                'required'  => FALSE,
                'type'      => new TranslationType(),
                'languages' => $this->getConfigurationPool()->getContainer()->getParameter('locales'),
                'options'   => array(
                    'label'      => FALSE,
                    'data_class' => 'ItBlaster\TranslationBundle\Model\TranslationI18n',
                    'columns'    => array(
                        'title' => array(
                            'label'     => "Заголовок",
                            'type'      => 'text',
                            'required'  => TRUE,
                            'options'   => array(
                                'attr' => array(
                                    'maxlength' => 255
                                )
                            )
                        ),
                    ),
                    'attr' => array(
                        'class' => 'block_form'
                    )
                )
            ))
        ;
    }

    /**
     * @param RouteCollection $collection
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection
            ->remove('export')
        ;
    }

    /**
     * @param mixed $object
     * @return mixed|void
     */
    public function postPersist($object)
    {
        $this->ClearCache();
    }

    /**
     * @param mixed $object
     * @return mixed|void
     */
    public function postUpdate($object)
    {
        $this->ClearCache();
    }

    /**
     * Чистим кэш
     */
    protected function ClearCache()
    {
        $app_dir = $this->getConfigurationPool()->getContainer()->get('kernel')->getRootDir();
        $cache_dirs = array(
            'dev' => $app_dir.'/cache/dev/translations/',
            'prod' => $app_dir.'/cache/prod/translations/',
        );
        foreach($cache_dirs as $cache_dir) {
            if (is_dir($cache_dir)) {
                $files = glob($cache_dir."*.*");
                if (count($files)) {
                    foreach ($files as $file) {
                        unlink($file);
                    }
                }
            }
        }
    }

}
