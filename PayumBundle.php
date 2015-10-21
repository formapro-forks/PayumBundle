<?php
namespace Payum\Bundle\PayumBundle;

use Payum\Bundle\PayumBundle\DependencyInjection\Compiler\BuildGatewayFactoryPass;
use Payum\Bundle\PayumBundle\DependencyInjection\Compiler\BuildRegistryPass;
use Payum\Bundle\PayumBundle\DependencyInjection\Factory\Gateway\GatewayFactory;
use Payum\Bundle\PayumBundle\DependencyInjection\Factory\Storage\CustomStorageFactory;
use Payum\Bundle\PayumBundle\DependencyInjection\Factory\Storage\Propel1StorageFactory;
use Payum\Bundle\PayumBundle\DependencyInjection\Factory\Storage\Propel2StorageFactory;
use Payum\Bundle\PayumBundle\DependencyInjection\Factory\Storage\DoctrineStorageFactory;
use Payum\Bundle\PayumBundle\DependencyInjection\Factory\Storage\FilesystemStorageFactory;
use Payum\Bundle\PayumBundle\DependencyInjection\PayumExtension;
use Payum\Core\PayumBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PayumBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var $extension PayumExtension */
        $extension = $container->getExtension('payum');

        $payum = (new PayumBuilder())->addDefaultStorages()->getPayum();
        foreach ($payum->getGatewayFactories() as $name => $gatewayFactory) {
            $extension->addGatewayFactory(new GatewayFactory($name, $gatewayFactory));
        }

        $extension->addStorageFactory(new FilesystemStorageFactory);
        $extension->addStorageFactory(new DoctrineStorageFactory);
        $extension->addStorageFactory(new CustomStorageFactory);
        $extension->addStorageFactory(new Propel1StorageFactory);
        $extension->addStorageFactory(new Propel2StorageFactory);

        $container->addCompilerPass(new BuildRegistryPass());
        $container->addCompilerPass(new BuildGatewayFactoryPass);
    }
}
