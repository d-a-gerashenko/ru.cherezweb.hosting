<?php

namespace CherezWeb\HostingBundle\Form\DnsRecordEditValue;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;

class SoaValueType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('email', 'text', array(
                'label' => 'ADMIN MAIL',
                'help_block' => 'Email администратора домена. Обратите внимание на то, что вместо "@" используется точка, а в конце адреса ставится еще одна точка. Например, адрес "email@host.ru" дожен иметь вид "email.host.ru.".',
                'constraints' => array(
                    new \Symfony\Component\Validator\Constraints\NotBlank(array('message' => 'Поле не может быть пустым.')),
                    new \Symfony\Component\Validator\Constraints\Length(array('max' => 50, 'maxMessage' => 'Длина поля не должна быть больше {{ limit }} символов.')),
                    new \Symfony\Component\Validator\Constraints\Regex(array('pattern' => '/^(?:(?:(?:(?:[A-Za-z0-9]+[-\.A-Za-z0-9]*[A-Za-z0-9]+)|[A-Za-z0-9]+)\.)(?:(?:(?:[A-Za-z0-9]+[-A-Za-z0-9]*[A-Za-z0-9]+)|[A-Za-z0-9]+)\.)(?:(?:(?:[A-Za-z0-9]+[-A-Za-z0-9]*[A-Za-z0-9]+)|[A-Za-z0-9]+)){2,}\.)$/', 'message' => 'Неправильный формат.')),
                ),
        ));
        $builder->add('refresh', 'integer', array(
                'label' => 'REFRESH',
                'help_block' => 'Время (в секундах) ожидания вторичного DNS перед запросом SOA-записи с первичного. По истечении данного времени, вторичный DNS обращается к первичному, для получения копии текущей SOA-записи.',
                'constraints' => array(
                    new \Symfony\Component\Validator\Constraints\NotBlank(array('message' => 'Поле не может быть пустым.')),
                    new \Symfony\Component\Validator\Constraints\Range(array('min' => 1200, 'max' => 43200, 'minMessage' => 'Значение не должно быть меньше {{ limit }}.', 'maxMessage' => 'Значение не должно быть больше {{ limit }}.')),
                ),
        ));
        $builder->add('retry', 'integer', array(
                'label' => 'RETRY',
                'help_block' => 'Время (в секундах), вступает в действие тогда, когда первичный DNS-сервер недоступен. Интервал времени, по истечении которого вторичный DNS должен повторить попытку синхронизировать описание зоны с первичным.',
                'constraints' => array(
                    new \Symfony\Component\Validator\Constraints\NotBlank(array('message' => 'Поле не может быть пустым.')),
                    new \Symfony\Component\Validator\Constraints\Range(array('min' => 180, 'max' => 900, 'minMessage' => 'Значение не должно быть меньше {{ limit }}.', 'maxMessage' => 'Значение не должно быть больше {{ limit }}.')),
                ),
        ));
        $builder->add('expire', 'integer', array(
                'label' => 'EXPIRE',
                'help_block' => 'Время (в секундах), в течение которого вторичный DNS будет пытаться завершить синхронизацию зоны с первичным. Если это время истечет до того, как синхронизация осуществится, зона на вторичном DNS-сервере истечет, и он перестанет обслуживать запросы в этой зоне.',
                'constraints' => array(
                    new \Symfony\Component\Validator\Constraints\NotBlank(array('message' => 'Поле не может быть пустым.')),
                    new \Symfony\Component\Validator\Constraints\Range(array('min' => 1209600, 'max' => 2419200, 'minMessage' => 'Значение не должно быть меньше {{ limit }}.', 'maxMessage' => 'Значение не должно быть больше {{ limit }}.')),
                ),
        ));
        $builder->add('ttl', 'integer', array(
                'label' => 'TTL',
                'help_block' => 'Минимальное время жизни (в секундах), применяемое ко всем записям зоны. Это значение применяется в ответах на запросы с целью проинформировать остальные серверы, сколько времени они могут хранить данные в кэше.',
                'constraints' => array(
                    new \Symfony\Component\Validator\Constraints\NotBlank(array('message' => 'Поле не может быть пустым.')),
                    new \Symfony\Component\Validator\Constraints\Range(array('min' => 10800, 'max' => 259200, 'minMessage' => 'Значение не должно быть меньше {{ limit }}.', 'maxMessage' => 'Значение не должно быть больше {{ limit }}.')),
                ),
        ));
    }

    public function getName() {
        $reflect = new \ReflectionClass($this);
        return $reflect->getShortName();
    }

}
