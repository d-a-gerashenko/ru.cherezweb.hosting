<?php

namespace CherezWeb\HostingBundle\Command;

use CherezWeb\DefaultBundle\Command\LockingCommandAbstract;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CherezWeb\HostingBundle\Entity\DomainBase;
use CherezWeb\HostingBundle\Entity\Domain;
use CherezWeb\HostingBundle\Entity\Task;

/**
 * Запускать раз в минуту.
 * Последовательность такая:
 * Ждем выполнения тасков на привязанных доменах.
 * Удаляем привязанные домены.
 * Удаляем старый базовый домен.
 * Активируем новый базовый домен.
 */
class DomainActivationCommand extends LockingCommandAbstract {

    protected function configure() {
        $this
            ->setName('cherez_web:hosting:domain_activation')
            ->setDescription('Process domain activation.');
    }

    protected function executeWithLocking(InputInterface $input, OutputInterface $output) {
        $output->writeln('Start' . PHP_EOL);
        $this->deleteDomains($input, $output);
        $this->deactivateDomains($input, $output);
        $this->activateDomains($input, $output);
        $output->writeln('End' . PHP_EOL);
    }
    
    /**
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager () {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }


    private function deleteDomains(InputInterface $input, OutputInterface $output) {
        $domainsToDelete = $this->getEntityManager()->getRepository('CherezWebHostingBundle:Domain')
            ->createQueryBuilder('d')
            ->select('d, a, q, s')
            ->join('d.domainBase', 'db')
            ->join('d.allocation', 'a')
            ->join('a.quota', 'q')
            ->join('q.server', 's')
            ->where('d.task IS NULL')
            ->andWhere('db.state = :state')
            ->setParameter('state', DomainBase::STATE_DEACTIVATION)
            ->getQuery()
            ->setMaxResults(50)
            ->getResult();
    
        foreach ($domainsToDelete as $domainToDelete) {
            /* @var $domainToDelete Domain */
            $this->getEntityManager()->getConnection()->beginTransaction();
            try {

                $domainToDelete->getAllocation()->getQuota()->getServer()->setVersionIncFlag(TRUE);

                $task = new Task();
                $this->getEntityManager()->persist($task);
                $task->setServer($domainToDelete->getAllocation()->getQuota()->getServer());
                $task->setType(Task::TYPE_DOMAIN_DELETE);

                $domainToDelete->setTask($task);

                $this->getEntityManager()->flush(array($domainToDelete->getAllocation()->getQuota()->getServer(), $task, $domainToDelete));

                $task->setParameters(array('domainId' => $domainToDelete->getId(), 'comment' => 'Deleting by DomainActivationCommand.'));

                $this->getEntityManager()->flush(array($task));
                $this->getEntityManager()->getConnection()->commit();
            } catch(\Doctrine\ORM\OptimisticLockException $e) {
                $output->writeln(sprintf('Rollback transaction on OptimisticLockException (Domain #%s).' . PHP_EOL, $domainToDelete->getId()));
            } catch (\Exception $e) {
                $this->getEntityManager()->getConnection()->rollback();
                throw new \Exception(sprintf('Rollback transaction on error (Domain #%s). Return.' . PHP_EOL, $domainToDelete->getId()), null, $e);
            }
        }
        $output->writeln('Deleting completed...' . PHP_EOL);
    }

    private function deactivateDomains(InputInterface $input, OutputInterface $output) {
        // Список хостов для перевода в неактивное.
        // Пока не удалятся все домены, новые домены не могу появиться, так
        // как активированных базовых доменов нет, а они не могу появиться пока
        // не удалятся все домены. Выбирам все имена базовых доменов, которым
        // нужно сделать переход состояния (т.е. они в состоянии деактивации, и
        // на них не ссылаются домены).
        $transitionStateHosts = $this->getEntityManager()->getRepository('CherezWebHostingBundle:DomainBase')
            ->createQueryBuilder('db')
            ->select('db.name, count(d.id) AS HIDDEN domains')
            ->leftJoin('CherezWebHostingBundle:Domain', 'd', 'WITH','d.domainBase = db.id')
            ->where('db.state = :state')
            ->setParameter('state', DomainBase::STATE_DEACTIVATION)
            ->groupBy('db.name')
            ->orderBy('db.id', 'ASC')
            ->having('domains = 0')
            ->getQuery()
            ->setMaxResults(20)
            ->getResult();
        
        // Перевод в неактивное состояние.
        // У деактивированных доменов путь только один - стать неактивными,
        // следовательно, совместный доступ и версии здесь не играют никакой
        // роли, следовательно, можно обновлять через UPDATE.
        $this->getEntityManager()->getRepository('CherezWebHostingBundle:DomainBase')
            ->createQueryBuilder('db')
            ->update()
            ->set('db.state', ':newState')
            ->setParameter('newState', DomainBase::STATE_INACTIVE)
            ->where("db.name IN (:names)")
            ->setParameter('names', $transitionStateHosts)
            ->andWhere('db.state = :state')
            ->setParameter('state', DomainBase::STATE_DEACTIVATION)
            ->getQuery()
            ->execute();
        $output->writeln('Deactivation completed...' . PHP_EOL);
    }
    
    
    /**
     * Активация доменом
     */
    private function activateDomains(InputInterface $input, OutputInterface $output) {
        // Активирующиеся домены могут успеть удалить, потому придется работать
        // с ними как с Entity. Выбираем базовые домены в состоянии активации,
        // для которых не осталось доменов в состоянии деактивации.
        $domainBasesToActivate = $this->getEntityManager()->getRepository('CherezWebHostingBundle:DomainBase')
            ->createQueryBuilder('db')
            ->select('db, count(db.id) AS HIDDEN dbcount')
            ->where('db.state IN (:states)')
            ->setParameter('states', array(DomainBase::STATE_ACTIVATION, DomainBase::STATE_DEACTIVATION))
            ->groupBy('db.name')
            ->orderBy('db.id', 'ASC')
            ->having('dbcount = 1')
            ->andHaving('db.state = :state')
            ->setParameter('state', DomainBase::STATE_ACTIVATION)
            ->getQuery()
            ->setMaxResults(20)
            ->getResult();

        foreach ($domainBasesToActivate as $domainBaseToActivate) {
            /* @var $domainBaseToActivate DomainBase */
            try {
                $domainBaseToActivate->setState(DomainBase::STATE_ACTIVE);
                $this->getEntityManager()->flush($domainBaseToActivate);
            } catch(\Doctrine\ORM\OptimisticLockException $e) {
                $output->writeln(sprintf('Rollback transaction on OptimisticLockException (DomainBase #%s).' . PHP_EOL, $domainBaseToActivate->getId()));
                return;
            } catch (\Exception $e) {
                $this->getEntityManager()->getConnection()->rollback();
                throw new \Exception(sprintf('Rollback transaction on error (DomainBase #%s). Return.' . PHP_EOL, $domainBaseToActivate->getId()), null, $e);
            }
        }
        $output->writeln('Activation completed...' . PHP_EOL);
    }
}
