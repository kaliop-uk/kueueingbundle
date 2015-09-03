<?php

namespace Kaliop\QueueingBundle\Adapter;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A factory for drivers, with a few extra capabilities.
 * Drivers get registered by a compiler pass.
 */
class DriverManager
{
    protected $aliases = array();
    protected $defaultDriver;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $alias
     * @param string $serviceName
     */
    public function registerDriver($alias, $serviceName)
    {
        $this->aliases[$alias] = $serviceName;
    }

    public function setDefaultDriver($alias)
    {
        $this->defaultDriver = $alias;
    }

    /**
     * @param string $alias null when asking for the default driver
     * @return \Kaliop\QueueingBundle\Adapter\DriverInterface
     * @throws \Exception if driver is not registered
     */
    public function getDriver($alias = null)
    {
        if ($alias == null) {
            $alias = $this->defaultDriver;
        }

        if (!isset($this->aliases[$alias])) {
            throw new \InvalidArgumentException(sprintf('No driver defined with the alias "%s".', $alias));
        }

        /// @todo shall we check that the good interface is declared?
        return $this->container->get($this->aliases[$alias]);
    }

    public function listActions($driverName='')
    {
        return array('list');
    }

    public function executeAction($action, $driverName='')
    {
        switch ($action) {
            case 'list':
                return $this->listDrivers();

            default:
                throw new InvalidArgumentException("Action $action not supported");
        }
    }

    /**
     * Lists all registered drivers (aliases)
     *
     * @return string[]
     */
    public function listDrivers()
    {
        return array_keys($this->aliases);
    }

    /**
     * returns all drivers
     * @return array key is alias, value is the service
     */
    public function getDrivers()
    {
        $drivers = array();
        foreach($this->aliases as $alias => $service) {
            $drivers[$alias] = $this->container->get($service);
        }
        return $drivers;
    }
}