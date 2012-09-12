<?php

namespace QBT\TranslationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use QBT\TranslationBundle\DependencyInjection\Compiler\TranslatorPass;

class QBTTranslationBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TranslatorPass());
    }
}
