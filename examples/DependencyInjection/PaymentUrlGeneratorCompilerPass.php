<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace AppBundle\DependencyInjection\Compiler;

use AppBundle\Ecommerce\UrlGenerator\MyPaymentUrlGenerator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class PaymentUrlGeneratorCompilerPass
 * @package AppBundle\DependencyInjection\Compiler
 */
class PaymentUrlGeneratorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        if ($container->hasDefinition('PayoneBundle\Model\IPaymentURLGenerator')) {
            $definition = $container->getDefinition('PayoneBundle\Model\IPaymentURLGenerator')->setPublic(true);
            $definition->setClass('AppBundle\Ecommerce\UrlGenerator\MyPaymentUrlGenerator');
        } else {
            //with the reference to router
            $definition = new Definition(MyPaymentUrlGenerator::class, [ new Reference('router')]);
            $container->setDefinition('PayoneBundle\Model\IPaymentURLGenerator', $definition);
        }

    }
}
