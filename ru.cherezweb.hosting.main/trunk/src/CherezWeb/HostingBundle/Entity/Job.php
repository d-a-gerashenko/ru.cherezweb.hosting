<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="chwh_job")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\JobRepository")
 */
class Job extends AllocationObjectAbstract {
    
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @var Task
     * @ORM\OneToOne(targetEntity="Task")
     */
    protected $task;
    
    //--------------------------------------------------------------------------
    
    /**
     * @var Allocation
     * @ORM\ManyToOne(targetEntity="Allocation")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $allocation;
    
    //--------------------------------------------------------------------------
    
    /**
     * @var string Путь до скрипта от корня Allocation, начинается со слеша.
     * @ORM\Column(type="string", length=150)
     */
    private $scriptPath;

    /**
     * @param string $scriptPath Путь до скрипта от корня Allocation, начинается со слеша.
     */
    public function setScriptPath($scriptPath) {
        $this->scriptPath = $scriptPath;
    }

    /**
     * Путь до скрипта от корня Allocation, начинается НЕ со слеша.
     * @return string 
     */
    public function getScriptPath() {
        return $this->scriptPath;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @var string Расписание в формате cron.
     * @ORM\Column(type="string", length=50)
     */
    private $schedule;

    /**
     * @param string $schedule Расписание в формате cron.
     */
    public function setSchedule($schedule) {
        $this->schedule = $schedule;
    }
    
//    /**
//     * @author Jordi Salvat i Alabart - with thanks to <a href="www.salir.com">Salir.com</a>.
//     */
//    function buildRegexp() {
//        $numbers = array(
//            'min' => '[0-5]?\d',
//            'hour' => '[01]?\d|2[0-3]',
//            'day' => '0?[1-9]|[12]\d|3[01]',
//            'month' => '[1-9]|1[012]',
//            'dow' => '[0-6]'
//        );
//
//        foreach ($numbers as $field => $number) {
//            $range = "(?:$number)(?:-(?:$number)(?:\/\d+)?)?";
//            $field_re[$field] = "\*(?:\/\d+)?|$range(?:,$range)*";
//        }
//
//        $field_re['month'].='|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';
//        $field_re['dow'].='|mon|tue|wed|thu|fri|sat|sun';
//
//        $fields_re = '(' . join(')\s+(', $field_re) . ')';
//
//        $replacements = '@yearly|@annually|@monthly|@weekly|@daily|@midnight|@hourly';
//
//        return  '^' .
//                '(' .
//                "$fields_re" .
//                "|($replacements)" .
//                ')' .
//                '$';
//    }

    /**
     * Расписание в формате cron.
     * @return string 
     */
    public function getSchedule() {
        return $this->schedule;
    }
    
}
