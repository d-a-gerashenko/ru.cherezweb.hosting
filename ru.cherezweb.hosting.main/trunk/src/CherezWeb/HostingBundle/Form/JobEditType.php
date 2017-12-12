<?php

namespace CherezWeb\HostingBundle\Form;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JobEditType extends AbstractType {
    
    private $submitName;

    public function __construct($submitName) {
        $this->submitName = $submitName;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('scriptPath', 'text', array(
            'label' => 'Запускаемый php файл',
            'help_block' => 'Путь может состоять из букв латинского алфавита, цифр и символов: "/", ".", "_", "-". Путь должен начинаться и не может заканчиваться символом "/". Имена директорий и файлов не могут начинаться с точки.',
            'attr' => array(
                'placeholder' => 'Пример: /cherezweb.ru/www/cron.php',
            ),
        ));
        $builder->add('schedule', 'text', array(
            'label' => 'Расписание в формате Cron',
            'attr' => array(
                'class' => 'cron-schedule'
            ),
        ));
        
        $builder->add('save', 'submit', array('label' => $this->submitName, 'attr' => array('class' => 'btn-primary')));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'validation_groups' => array($this->getName(), "Default"),
            'data_class' => 'CherezWeb\HostingBundle\Entity\Job',
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
