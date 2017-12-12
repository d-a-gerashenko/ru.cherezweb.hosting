<?php

namespace CherezWeb\HostingBundle\Form;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SupportType extends AbstractType {
    
    protected $authenticated;

    public function __construct($authenticated = FALSE) {
        $this->authenticated = $authenticated;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('name', 'text', array(
            'label' => 'Как к вам обращаться',
            'attr' => array(
                'placeholder' => "Укажите ваше имя или ФИО",
            ),
            'constraints' => array(
                new \Symfony\Component\Validator\Constraints\NotBlank(array('message' => 'Укажите ваше имя.')),
                new \Symfony\Component\Validator\Constraints\Length(array('max' => 50, 'maxMessage' => 'Длина поля не должна быть больше {{ limit }} символов.')),
            ),
        ));
        if (!$this->authenticated) {
            $builder->add('email', 'email', array(
                'label' => 'Ваша электронная почта',
                'attr' => array(
                    'placeholder' => "На этот адрес мы вам ответим",
                ),
                'constraints' => array(
                    new \Symfony\Component\Validator\Constraints\NotBlank(array('message' => 'Укажите адрес вашей электронной почты.')),
                    new \Symfony\Component\Validator\Constraints\Email(array('message' => 'В адресе электронной почты допущена ошибка.')),
                ),
            ));
        }
        $builder->add('message', 'textarea', array(
            'label' => 'Текст сообщения',
            'attr' => array(
                'placeholder' => "Укажите текст сообщения",
                'maxlength' => '5000',
                'rows' => '5',
            ),
            'constraints' => array(
                new \Symfony\Component\Validator\Constraints\NotBlank(array('message' => 'Укажите текст сообщения.')),
                new \Symfony\Component\Validator\Constraints\Length(array('max' => 5000, 'maxMessage' => 'Длина поля не должна быть больше {{ limit }} символов.')),
            ),
        ));
        $builder->add('save', 'submit', array('label' => 'Отправить', 'attr' => array('class' => 'btn-primary')));
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
