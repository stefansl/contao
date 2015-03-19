<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CommentsBundle;

use Contao\CoreBundle\DependencyInjection\Compiler\AddContaoResourcesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Configures the Contao comments bundle.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoCommentsBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new AddContaoResourcesPass(
                $this->getPath() . '/../contao',
                [$this->getPath() . '/../contao/assets']
            )
        );
    }
}
