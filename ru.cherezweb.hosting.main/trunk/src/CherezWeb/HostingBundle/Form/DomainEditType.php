<?php

namespace CherezWeb\HostingBundle\Form;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DomainEditType extends AbstractType {
    
    private $submitName;

    public function __construct($submitName) {
        $this->submitName = $submitName;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('name', 'text', array(
            'label' => 'Домен',
            'attr' => array(
                'class' => 'domain-name-field',
                'placeholder' => 'Пример: "hosting.cherezweb.ru".',
            ),
        ));
        $builder->add('dirPath', 'text', array(
            'label' => 'Путь',
            'help_block' => 'Пример: "/dir/sub_dir/target_folder". Путь может состоять из букв латинского алфавита, цифр и символов: "/", ".", "_", "-". Путь должен начинаться и не может заканчиваться символом "/". Имена директорий не могут начинаться с точки. Несуществующая директория будет создана.',
            'attr' => array(
                'class' => 'domain-path-field',
                'placeholder' => 'Корневая папка обозначается символом "/".',
            ),
        ));
        
        $builder->add('save', 'submit', array('label' => $this->submitName, 'attr' => array('class' => 'btn-primary')));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'validation_groups' => array($this->getName(), "Default"),
            'data_class' => 'CherezWeb\HostingBundle\Entity\Domain',
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
