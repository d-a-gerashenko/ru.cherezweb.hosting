<?php

namespace CherezWeb\HostingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Запускать раз в 10 минут.
 */
class ProlongationNotificationCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
            ->setName('cherez_web:hosting:prolongation_notification')
            ->setDescription('Allocation days left notification.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Start' . PHP_EOL);
        
        // Интервалы уведомления в порядке возрастания.
        $notificationIntervals = array (
            new \DateInterval('P1D'),
            new \DateInterval('P5D'),
            new \DateInterval('P10D'),
            new \DateInterval('P25D'),
        );
        $maxAllocationNum = 10;
        
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $em \Doctrine\ORM\EntityManager */
        
        $mailer = $this->getContainer()->get('cherez_web.default.mailer');
        /* @var $mailer \CherezWeb\DefaultBundle\Service\Mailer */
        
        $now = new \DateTime;
        foreach ($notificationIntervals as $notificationInterval) {
            // Получаем дату в текущем дне.
            $paidTill = clone $now;
            $paidTill->add($notificationInterval);
            $daysLeft = $now->diff($paidTill)->format('%a');
            
            $allocations = $em->getRepository('CherezWebHostingBundle:Allocation')
                ->createQueryBuilder('a')
                ->where('a.paidTill < :paidTill')
                ->setParameter('paidTill', $paidTill, 'utcdatetime')
                ->andWhere('(
                        a.daysLeftNotified IS NULL
                    OR
                        a.daysLeftNotified > :daysLeft
                )')
                ->setParameter('daysLeft', $daysLeft)
                ->getQuery()
                ->setMaxResults($maxAllocationNum)
                ->getResult();
            foreach ($allocations as $allocation) {
                /* @var $allocation \CherezWeb\HostingBundle\Entity\Allocation */
                $allocationDaysLeft = $now->diff($allocation->getPaidTill())->format('%R%a');

                $mailer->sendMail(
                    sprintf(
                        'Необходимо продлить площадку "%s" до %s',
                        $allocation->getName(),
                        $allocation->getPaidTill()->format('d.m.Y')
                    ),
                    $allocation->getUser()->getEmail(),
                    'CherezWebHostingBundle:Email:allocation_days_left',
                    array(
                        'allocation' => $allocation,
                        'allocationDaysLeft' => $allocationDaysLeft,
                    )
                );
                    
                $allocation->setDaysLeftNotified($allocationDaysLeft);
                $em->flush();
            }
        }
        
        
        $output->writeln('End' . PHP_EOL);
    }

}
