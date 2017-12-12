<?php

namespace CherezWeb\HostingBundle\Form;

use CherezWeb\HostingBundle\Entity\DnsRecord;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DnsRecordEditType extends AbstractType
{

    private $domainBaseName;
    private $recordType;

    public function __construct($domainBaseName, $recordType)
    {
        $this->domainBaseName = $domainBaseName;
        $this->recordType = $recordType;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            $builder->create('host', 'text', array(
                'label' => 'Хост',
                'help_block' => 'Домен или поддомен, для которого задается DNS запись.',
                )
            )->addModelTransformer(
                new DataTransformer\DnsRecordHostTransformer($this->domainBaseName)
            )
        );

        switch ($this->recordType) {
            case DnsRecord::TYPE_A:
                $valueType = new DnsRecordEditValue\AValueType();
                break;
            case DnsRecord::TYPE_SOA:
                $valueType = new DnsRecordEditValue\SoaValueType();
                break;
            case DnsRecord::TYPE_CNAME:
                $valueType = new DnsRecordEditValue\CnameValueType();
                break;
            case DnsRecord::TYPE_TXT:
                $valueType = new DnsRecordEditValue\TxtValueType();
                break;
            case DnsRecord::TYPE_NS:
                $valueType = new DnsRecordEditValue\NsValueType();
                break;
            case DnsRecord::TYPE_MX:
                $valueType = new DnsRecordEditValue\MxValueType();
                break;
        }
        $builder->add('value', $valueType, array(
            'label' => 'Параметры DNS записи:',
        ));

        if (in_array($this->recordType, array(DnsRecord::TYPE_MX))) {
            $builder->add('priority', 'choice', array(
                'label' => 'Приоритет',
                'choices' => array(
                    1 => 1,
                    2 => 2,
                    3 => 3,
                    4 => 4,
                    5 => 5,
                    6 => 6,
                    7 => 7,
                    8 => 8,
                    9 => 9,
                    10 => 10,
                    20 => 20,
                    30 => 30,
                    40 => 40,
                    50 => 50,
                    60 => 60,
                    70 => 70,
                    80 => 80,
                    90 => 90,
                ),
            ));
        }


        $builder->add('save', 'submit', array('label' => 'Сохранить', 'attr' => array('class' => 'btn-primary')));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'validation_groups' => array($this->getName(), "Default"),
            'data_class' => 'CherezWeb\HostingBundle\Entity\DnsRecord',
            'attr' => array(
                'novalidate' => 'novalidate',
                'class' => 'chw-ajax-manager-form',
            ),
            'csrf_message' => 'Страница долго не использовалась и форма устарела. Повторите попытку, в этот раз все должно пройти удачно.',
        ));
    }

    public function getName()
    {
        $reflect = new \ReflectionClass($this);
        return $reflect->getShortName();
    }
}
