<?php
namespace Payum\Bundle\PayumBundle\DependencyInjection\Factory\Gateway;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Gateway;
use Payum\Core\GatewayFactoryInterface as PayumGatewayFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

class GatewayFactory implements GatewayFactoryInterface, PrependExtensionInterface
{
    /**
     * @var PayumGatewayFactoryInterface
     */
    protected $gatewayFactory;

    /**
     * @var ArrayObject
     */
    protected $gatewayConfig;

    /**
     * @var string
     */
    protected $gatewayName;

    /**
     * @param string $name
     * @param PayumGatewayFactoryInterface $gatewayFactory
     */
    public function __construct($name, PayumGatewayFactoryInterface $gatewayFactory)
    {
        $this->gatewayFactory = $gatewayFactory;
        $this->gatewayConfig = ArrayObject::ensureArrayObject($gatewayFactory->createConfig());
    }

    /**
     * {@inheritDoc}
     */
    public function create(ContainerBuilder $container, $gatewayName, array $config)
    {
        $config['payum.gateway_name'] = $gatewayName;

        $gateway = $this->createGateway($container, $gatewayName, $config);
        $gatewayId = "payum.{$this->getName()}.{$gatewayName}.gateway";
        $container->setDefinition($gatewayId, $gateway);

        foreach (array_reverse($config['apis']) as $apiId) {
            $gateway->addMethodCall(
                'addApi',
                array(new Reference($apiId), $forcePrepend = true)
            );
        }

        foreach (array_reverse($config['actions']) as $actionId) {
            $gateway->addMethodCall(
                'addAction',
                array(new Reference($actionId), $forcePrepend = true)
            );
        }

        foreach (array_reverse($config['extensions']) as $extensionId) {
            $gateway->addMethodCall(
                'addExtension',
                array(new Reference($extensionId), $forcePrepend = true)
            );
        }

        return $gatewayId;
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['TwigBundle']) && isset($this->gatewayConfig['twig.paths'])) {
            $container->prependExtensionConfig('twig', [
                'paths' => array_flip($this->gatewayConfig['twig.paths'])
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $defaultConfig = [];
        $defaultConfig['payum.factory_name'] = $this->getName();
        $defaultConfig['payum.http_client'] = new Reference('payum.http_client');
        $defaultConfig['twig.env'] = new Reference('twig');
        $defaultConfig['payum.iso4217'] = new Reference('payum.iso4217');

        foreach ($this->gatewayConfig as $name => $value) {
            if (0 === strpos($name, 'payum.template')) {
                $parameter = "payum.{$this->getName()}.template.{$name}";

                $container->setParameter($parameter, $value);

                $defaultConfig[$name] = new Parameter($parameter);
            }
        }

        $factory = new Definition(get_class($this->gatewayFactory), array(
            $defaultConfig,
            new Reference('payum.gateway_factory'),
        ));
        $factory->addTag('payum.gateway_factory', array(
            'factory_name' => $this->getName(),
            'human_name' => $this->gatewayConfig['payum.factory_title'] ?: $this->getName(),
        ));

        $factoryId = sprintf('payum.%s.factory', $this->getName());

        $container->setDefinition($factoryId, $factory);
    }
    
    /**
     * {@inheritDoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        $gatewayConfig = $this->gatewayConfig;

        foreach ($gatewayConfig['payum.default_options'] as $name => $value) {
            if (is_bool($value)) {
                $builder->children()
                    ->booleanNode($name)->defaultValue($value)->end()
                ;
            } else if (in_array($name, $gatewayConfig['payum.required_options'])) {
                $builder->children()
                    ->scalarNode($name)->defaultValue($value)->isRequired()->cannotBeEmpty()->end()
                ;
            } else {
                $builder->children()
                    ->scalarNode($name)->defaultValue($value)->end()
                ;
            }
        }

        $builder
            ->children()
                ->arrayNode('actions')
                    ->useAttributeAsKey('key')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('apis')
                    ->useAttributeAsKey('key')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('extensions')
                    ->useAttributeAsKey('key')
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->gatewayName;
    }

    /**
     * @param ContainerBuilder $container
     * @param string $gatewayName
     * @param array $config
     *
     * @return Definition
     */
    protected function createGateway(ContainerBuilder $container, $gatewayName, array $config)
    {
        $gateway = new Definition(Gateway::class, array($config));
        $gateway->setFactory(array(
            new Reference(sprintf('payum.%s.factory', $this->getName())),
            'create'
        ));

        $gateway->setPublic(true);

        return $gateway;
    }
}