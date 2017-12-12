<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="chwh_task")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\TaskRepository")
 */
class Task {
    
    public function __construct() {
        $this->parameters = array();
        $this->created = new \DateTime();
        $this->state = self::STATE_CREATED;
    }
    
    //--------------------------------------------------------------------------
    
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
     * @var Server
     * @ORM\ManyToOne(targetEntity="Server")
     * @ORM\JoinColumn(nullable=false)
     */
    private $server;
    
    /**
     * @return Server
     */
    public function getServer() {
        return $this->server;
    }

    public function setServer(Server $server) {
        $this->server = $server;
    }
    
    //--------------------------------------------------------------------------

    // При добавлении новых типов менять findTaskObject в репозитории.
    const TYPE_ALLOCATION_CREATE = 'allocation_create';
    const TYPE_ALLOCATION_DELETE = 'allocation_delete';
    const TYPE_ALLOCATION_UPDATE_LOCK = 'allocation_update_lock';
    const TYPE_ALLOCATION_UPDATE_PASSWORD = 'allocation_update_password';
    
    const TYPE_DATABASE_CREATE = 'database_create';
    const TYPE_DATABASE_DELETE = 'database_delete';
    const TYPE_DATABASE_UPDATE_PASSWORD = 'database_update_password';
    
    const TYPE_DOMAIN_CREATE = 'domain_create';
    const TYPE_DOMAIN_DELETE = 'domain_delete';
    const TYPE_DOMAIN_UPDATE = 'domain_update';
    
    const TYPE_FTP_CREATE = 'ftp_create';
    const TYPE_FTP_DELETE = 'ftp_delete';
    const TYPE_FTP_UPDATE_DIR_PATH = 'ftp_update_dir_path';
    const TYPE_FTP_UPDATE_PASSWORD = 'ftp_update_password';
    
    const TYPE_JOB_CREATE = 'job_create';
    const TYPE_JOB_DELETE = 'job_delete';
    const TYPE_JOB_UPDATE = 'job_update';

    static function getTypeVariants() {
        $prefix = 'TYPE_';
        $variants = array();
        $refl = new \ReflectionClass(get_called_class());
        foreach ($refl->getConstants() as $name => $value) {
            if (strpos($name, $prefix) === 0) {
                $variants[] = $value;
            }
        }
        return $variants;
    }
    
    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $type;

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        if (!in_array($type, self::getTypeVariants())) {
            throw new \Exception(sprintf('Неправильный формат $type: %s.', $type));
        }
        $this->type = $type;
    }
    
    //--------------------------------------------------------------------------

    /**
     * @ORM\Column(type="array")
     */
    protected $parameters;

    /**
     * @return array
     */
    public function getParameters() {
        return $this->parameters;
    }
    
    public function setParameters(array $parameters) {
        if (!empty($parameters) && $parameters === $this->parameters) {
            reset($parameters);
            $parameters[key($parameters)] = clone current($parameters);
        }
        $this->parameters = $parameters;
    }

    /**
     * Обновляет исходный массив элементами из входного массива.
     * @param array $parameters
     */
    public function addParameters (array $parameters) {
        $this->setParameters(array_replace_recursive($this->parameters, $parameters));
    }
    
    /**
     * Выдает поддерево массива.
     * @param string $path Путь до поддерева в виде строки из ключей разеденых точками.
     * @return mixe Массив или элемент массива.
     */
    public function getParameter($path) {
        $pathArray = explode('.', $path);
        $subTree = $this->parameters;
        foreach ($pathArray as $pathItem) {
            $subTree = $subTree[$pathItem];
        }
        return $subTree;
    }
    
    //--------------------------------------------------------------------------
    
    const STATE_CREATED = 'created';
    const STATE_EXECUTED = 'executed';
    const STATE_COMPLETED = 'completed';

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('created', 'executed','completed')")
     */
    protected $state;

    public function getState() {
        return $this->state;
    }

    public function setState($state) {
        if (!in_array($state, array(
            self::STATE_CREATED,
            self::STATE_EXECUTED,
            self::STATE_COMPLETED,
        ))) {
            throw new \Exception(sprintf('Неправильный формат $state: %s.', $state));
        }
        $this->state = $state;
    }
    
    //--------------------------------------------------------------------------
    
    const RESULT_SUCCESS = 'success';
    const RESULT_FAILURE = 'failure';

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('success', 'failure')")
     */
    protected $result;

    public function getResult() {
        return $this->result;
    }

    public function setResult($result) {
        if (!in_array($result, array(
            self::RESULT_SUCCESS,
            self::RESULT_FAILURE,
        ))) {
            throw new \Exception(sprintf('Неправильный формат $result: %s.', $result));
        }
        $this->result = $result;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $error;

    public function getError() {
        return $this->error;
    }

    public function setError($error) {
        $this->error = $error;
    }
    
    //--------------------------------------------------------------------------

    /**
     * utcdatetime
     * @var \DateTime
     * @ORM\Column(type="utcdatetime")
     */
    protected $created;

	public function setCreated(\DateTime $created) {
		$this->created = $created;
	}

    /**
	 * @return \DateTime utcdatetime
	 */
	public function getCreated() {
        if ($this->created !== NULL) {
            $this->created->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }
		return $this->created;
	}
    
    //--------------------------------------------------------------------------

    /**
     * utcdatetime
     * @var \DateTime
     * @ORM\Column(type="utcdatetime", nullable=true)
     */
    protected $executed;

	public function setExecuted(\DateTime $executed) {
		$this->executed = $executed;
	}

    /**
	 * @return \DateTime utcdatetime
	 */
	public function getExecuted() {
		if ($this->executed !== NULL) {
            $this->executed->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }
		return $this->executed;
	}
    
    //--------------------------------------------------------------------------

    /**
     * utcdatetime
     * @var \DateTime
     * @ORM\Column(type="utcdatetime", nullable=true)
     */
    protected $completed;

	public function setCompleted(\DateTime $completed) {
		$this->completed = $completed;
	}

    /**
	 * @return \DateTime utcdatetime
	 */
	public function getCompleted() {
		if ($this->completed !== NULL) {
            $this->completed->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }
		return $this->completed;
	}

}
