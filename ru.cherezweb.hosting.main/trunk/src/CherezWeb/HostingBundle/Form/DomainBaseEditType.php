<?php

namespace CherezWeb\HostingBundle\Form;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DomainBaseEditType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('name', 'text', array(
            'label' => 'Домен',
            'help_block' => 'Добавлять можно домены только 2-го уровня, например: cherezweb.ru.',
            'attr' => array(
                'placeholder' => 'Пример: "cherezweb.ru".',
            ),
        ));
        
        $builder->add('save', 'submit', array('label' => 'Сохранить', 'attr' => array('class' => 'btn-primary')));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'validation_groups' => array($this->getName(), "Default"),
            'data_class' => 'CherezWeb\HostingBundle\Entity\DomainBase',
            'attr' => array(
                'novalidate' => 'novalidate',
                'class' => 'chw-ajax-manager-form',
            ),
            'csrf_message' => 'Страница долго не использовалась и форма устарела. Повторите попытку, в этот раз все должно пройти удачно.',
        ));
    }

    public function getName() {
        $reflect = new \ReflectionClass($this);
        return $reflect->getShortName();
    }

}
