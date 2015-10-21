<?php
namespace Payum\Bundle\PayumBundle\DependencyInjection\Factory\Gateway;

use Payum\Core\GatewayFactory as PayumGatewayFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class CustomGatewayFactory extends GatewayFactory
{
    public function __construct()
    {
        parent::__construct('custom', new PayumGatewayFactory());
    }

    /**
     * {@inheritDoc}
     */
    public function createGateway(ContainerBuilder $container, $gatewayName, array $config)
    {
        if (isset($config['service'])) {
            return new DefinitionDecorator($config['service']);
        }

        return parent::createGateway($container, $gatewayName, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'custom';
    }

    /**
     * {@inheritDoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        parent::addConfiguration($builder);

        $builder->children()
            ->scalarNode('service')->defaultValue(null)->end()
        ->end();
    }
}