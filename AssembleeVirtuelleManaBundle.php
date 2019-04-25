<?php

namespace AssembleeVirtuelle\ManaBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Sensio\Bundle\FrameworkExtraBundle\DependencyInjection\Compiler\AddExpressionLanguageProvidersPass;
use Sensio\Bundle\FrameworkExtraBundle\DependencyInjection\Compiler\AddParamConverterPass;
use Sensio\Bundle\FrameworkExtraBundle\DependencyInjection\Compiler\OptimizerPass;

/**
 * @author Michel Cadennes <michel.cadennes@assemblee-virtuelle.org>
 */
class AssembleeVirtuelleManaBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddParamConverterPass());
        $container->addCompilerPass(new OptimizerPass());
        $container->addCompilerPass(new AddExpressionLanguageProvidersPass());
    }
}
