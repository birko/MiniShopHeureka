<?php

namespace Core\HeurekaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Core\HeurekaBundle\Entity\HeurekaHelper;

class CategoryCategoryType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('heurekaCategory', 'choice', array(
                'choices' =>  HeurekaHelper::getCategories($options['locale']),
                'required'    => true,
                'label'       => 'Heureka',  
                'empty_value' => 'Choose category',
                'empty_data'  => null)
            )
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Core\HeurekaBundle\Entity\CategoryCategory',
            'locale' => 'sk'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'core_heurekabundle_categorycategory';
    }
}
