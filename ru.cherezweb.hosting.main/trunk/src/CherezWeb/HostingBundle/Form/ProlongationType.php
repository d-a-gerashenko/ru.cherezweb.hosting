<?php

namespace CherezWeb\HostingBundle\Form;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProlongationType extends AbstractType {
    
    private $price;

    public function __construct($price) {
        $this->price = $price;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('period', 'choice', array(
            'label' => 'Период',
            'choices'   => array(
                '1'   => '1 месяц ('.number_format(1 * $this->price / 100, 2, ',', ' ').' р.)',
                '2'   => '2 месяца ('.number_format(2 * $this->price / 100, 2, ',', ' ').' р.)',
                '3'   => '3 месяца ('.number_format(3 * $this->price / 100, 2, ',', ' ').' р.)',
                '6'   => '6 месяцев ('.number_format(8 * $this->price / 100, 2, ',', ' ').' р.)',
                '9'   => '9 месяцев ('.number_format(9 * $this->price / 100, 2, ',', ' ').' р.)',
                '12'   => '1 год ('.number_format(12 * $this->price / 100, 2, ',', ' ').' р.)',
            ),
            'constraints' => array(
                new \Symfony\Component\Validator\Constraints\NotBlank(),
            ),
        ));
        $builder->add('save', 'submit', array('label' => 'Продлить', 'attr' => array('class' => 'btn-primary')));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'validation_groups' => array($this->getName(), "Default"),
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
