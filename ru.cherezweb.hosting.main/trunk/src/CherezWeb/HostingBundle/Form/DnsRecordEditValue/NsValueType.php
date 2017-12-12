<?php

namespace CherezWeb\HostingBundle\Form\DnsRecordEditValue;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;

class NsValueType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('nsHost', 'text', array(
                'label' => 'Хост name-сервера',
                'help_block' => 'В конце хоста должна стоять точка, например: "ns1.cherezweb.ru.".',
                'constraints' => array(
                    new \Symfony\Component\Validator\Constraints\NotBlank(array('message' => 'Значение не может быть пустым.')),
                    new \Symfony\Component\Validator\Constraints\Length(array('max' => 100, 'maxMessage' => 'Длина поля не должна быть больше {{ limit }} символов.')),
                    new \Symfony\Component\Validator\Constraints\Regex(array('pattern' => '/^(?:(?:(?:(?:[A-Za-z0-9]+[-A-Za-z0-9]*[A-Za-z0-9]+)|[A-Za-z0-9]+)\.)+(?:(?:(?:[A-Za-z0-9]+[-A-Za-z0-9]*[A-Za-z0-9]+)|[A-Za-z0-9]+)){2,}\.)$/', 'message' => 'Неправильный формат значения.')),
                ),
        ));
    }

    public function getName() {
        $reflect = new \ReflectionClass($this);
        return $reflect->getShortName();
    }

}
