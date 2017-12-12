<?php

namespace CherezWeb\HostingBundle\Form\DnsRecordEditValue;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;

class TxtValueType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('txt', 'text', array(
                'label' => 'Значение',
                'constraints' => array(
                    new \Symfony\Component\Validator\Constraints\NotBlank(array('message' => 'Значение не может быть пустым.')),
                    new \Symfony\Component\Validator\Constraints\Length(array('max' => 255, 'maxMessage' => 'Длина поля не должна быть больше {{ limit }} символов.')),
                ),
        ));
    }

    public function getName() {
        $reflect = new \ReflectionClass($this);
        return $reflect->getShortName();
    }

}
