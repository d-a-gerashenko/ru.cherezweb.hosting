<?php

namespace CherezWeb\HostingBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ObjectManager;


use CherezWeb\HostingBundle\Entity\User;
use CherezWeb\HostingBundle\Entity\Quota;
use CherezWeb\HostingBundle\Entity\Plan;
use CherezWeb\HostingBundle\Entity\Server;
use CherezWeb\HostingBundle\Entity\Allocation;
use CherezWeb\HostingBundle\Entity\Tutorial;

class LoadTestData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface {

    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }

    public function getOrder() {
        return 1;
    }

    public function load(ObjectManager $manager) {
        $factory = $this->container->get('security.encoder_factory');
		
        //----------------------------------------------------------------------
        //Пользователи
        //----------------------------------------------------------------------
        $user = $users[] = new User();
		$user->setEmail("support@cherezweb.ru");
        $encoder = $factory->getEncoder($user);
		$password = $encoder->encodePassword('12345678', $user->getSalt());
		$user->setPassword($password);
		$user->setIsVerified(TRUE);
        $billing = $this->container->get('cherez_web.billing.billing');
        /* @var $billing \CherezWeb\BillingBundle\Service\Billing */
        $billing->makeTransaction($user->getWallet(), 999999000, 'Пополнение.');
        $billing->makeTransaction($user->getWallet(), 1000, 'Пополнение.');
        $billing->makeTransaction($user->getWallet(), 1000, 'Пополнение.');
        $billing->makeTransaction($user->getWallet(), 1000, 'Пополнение.');
        $billing->makeTransaction($user->getWallet(), 1000, 'Пополнение.');
        $billing->makeTransaction($user->getWallet(), -100, 'Списание.');
        $billing->makeTransaction($user->getWallet(), 1000, 'Пополнение.');
        $billing->makeTransaction($user->getWallet(), 1000, 'Пополнение.');
        $billing->makeTransaction($user->getWallet(), 1000, 'Пополнение.');
        $billing->makeTransaction($user->getWallet(), 1000, 'Пополнение.');
        $billing->makeTransaction($user->getWallet(), -300, 'Списание.');
        $billing->makeTransaction($user->getWallet(), -400, 'Списание.');
        $billing->makeTransaction($user->getWallet(), -1, 'Списание.');
        //----------------------------------------------------------------------
        foreach ($users as $user) {
            $manager->persist($user);
        }
        //----------------------------------------------------------------------
        //Серверы
        //----------------------------------------------------------------------
        $server = $servers[] = new Server();
		$server->setAccessKey('asdasfasfasf');
		$server->setIpAddress('192.168.0.234');
        //----------------------------------------------------------------------
        foreach ($servers as $server) {
            $manager->persist($server);
        }
        //----------------------------------------------------------------------
        //Тарифы
        //----------------------------------------------------------------------
        $plan = $plans[] = new Plan();
		$plan->setTitle('Стартовый');
		$plan->setDescription('<p>Описание тарифа в формате <b>HTML</b></p>');
		$plan->setDiskQuota(500 * 1073741824);
		$plan->setPrice(5000);
        //----------------------------------------------------------------------
        foreach ($plans as $plan) {
            $manager->persist($plan);
        }
        //----------------------------------------------------------------------
        //Квоты
        //----------------------------------------------------------------------
        $quota1 = $quotas[] = new Quota();
		$quota1->setPlan($plan);
		$quota1->setServer($server);
		$quota1->setSize(50);
        
        $quota2 = $quotas[] = new Quota();
		$quota2->setPlan($plan);
		$quota2->setServer($server);
		$quota2->setSize(0);
        //----------------------------------------------------------------------
        foreach ($quotas as $quota) {
            $manager->persist($quota);
        }
        //----------------------------------------------------------------------
        //Размещения
        //----------------------------------------------------------------------
        $allocation = $allocations[] = new Allocation();
		$allocation->setPaidTill(new \DateTime('2015-09-01'));
		$allocation->setQuota($quota1);
		$allocation->setUser($user);
        
        $allocation = $allocations[] = new Allocation();
        $allocation->setPaidTill(new \DateTime('2015-09-01'));
		$allocation->setQuota($quota1);
		$allocation->setUser($user);
        
        $allocation = $allocations[] = new Allocation();
        $allocation->setPaidTill(new \DateTime('2015-09-01'));
		$allocation->setQuota($quota1);
		$allocation->setUser($user);
        
        $allocation = $allocations[] = new Allocation();
        $allocation->setPaidTill(new \DateTime('2015-09-01'));
		$allocation->setQuota($quota2);
		$allocation->setUser($user);
        
        //----------------------------------------------------------------------
        foreach ($allocations as $allocation) {
            $manager->persist($allocation);
        }
        
        //----------------------------------------------------------------------
        //Статьи
        //----------------------------------------------------------------------
        $tutorial = $tutorials[] = new Tutorial();
        $tutorial->setTitle('Что такое "площадка" на хостинге ЧерезВеб?"');
        $tutorial->setDescription('Площадка - это абстрактное понятие, обозначающее выделенное на сервере место и ресурсы. Каждая площадка имеет один закрепленный за ней ssh доступ к серверу, на котором она находится."');
		$tutorial->setContent('<h2>Площадка</h2>
<p>Площадка - это абстрактное понятие, обозначающее выделенное на сервере место и ресурсы.</p>
<p>Каждая площадка имеет один закрепленный за ней ssh доступ к серверу, на котором она находится.</p>
<p>На один аккаунт можно подключить несколько площадок.</p>
<p>На площадке можно создавать: базы данных, ftp доступы, задания по расписанию. Также к площадкам можно привязывать домены сайтов.</p>
<p>При удалении площадки все данные на площадке безвозвратно удаляются, а деньги за неиспользованные оплаченные дни возвращаются на счет аккаунта.</p>
<p>Изменение тарифа площадки невозможно. Если сайт вырос, и требуется новый более мощный тариф - необходимо создать еще одну площадку с нужным тарифом, перенести на неё сайт, после чего удалть старую площадку.</p>
<h2>Задание по расписанию</h2>
<p>Если необходимо выполнять по расписанию расположенный на площадке php скрипт, можно воспользоваться редактором заданий на площадке.</p>
<p>Если же вы привыкли работать с утилитой crontab, вы всегда можете получить к ней доступ через ssh.</p>
<h2>База данных</h2>
<p>На хостинге используется СУБД MySql, кодировка по умолчанию - utf-8.</p>
<h2>SHH доступ</h2>
<p>Для подключения по ssh на сервере используется стандартный 22 порт.</p>
<p>Доступна утилита unzip для разархивации zip архивов.</p>
<h2>FTP доступ</h2>
<p>Для подключения используется стандартный 21 порт.</p>
<p>Рекомендуется передавать данные только в бинарном режиме, эту опцию можно выставить настройках FTP клиента.</p>
<h2>Домен</h2>
<p>Если вы пользуетесь регистратором, не предоставляющим бесплатный DNS редактор домена, вы можете воспользоваться бесплатным сервисом Яндекса: <a href="http://help.yandex.ru/pdd/hosting.xml" target="_blank">http://help.yandex.ru/pdd/hosting.xml</a>.</p>
<p>При привязке доменов к площадке (например: <a href="http://www.cherezweb.ru/" target="_blank">www.cherezweb.ru</a> и <a href="http://hosting.cherezweb.ru/" target="_blank">hosting.cherezweb.ru</a>), базовый домен второго уровня (в нашем случае: <a href="http://cherezweb.ru/" target="_blank">cherezweb.ru</a>) будет автоматически добавлен в список подтвержденных доменов аккаунта.</p>
<p>Если попыться привязать к площадке домен, базовый домен которого уже используется на другом аккаунте, будет запрошено подтверждение прав на домен.</p>
<p>При удалении базового домена с аккаунта, автоматически удаляются все привязки этого домена и его поддоменов к площадкам данного аккаунта.</p>
<p>Если вам принадлежат два аккаунта, и вы хотите перенести домен с одного аккаунта на другой - достаточно удалть базовый домен с одного аккаунта и добавить на другой. В этом случае подтверждение прав на домен не потребуется.</p>');
		
        //----------------------------------------------------------------------
        foreach ($tutorials as $tutorial) {
            $manager->persist($tutorial);
        }
        
        $manager->flush();
    }

}
