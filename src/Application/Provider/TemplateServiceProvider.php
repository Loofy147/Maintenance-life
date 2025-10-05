<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Presentation\Template\BasicTemplateRenderer;
use MaintenancePro\Presentation\Template\TemplateRendererInterface;

class TemplateServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container): void
    {
        $container->singleton(TemplateRendererInterface::class, function($c) {
            $paths = $c->get('paths');
            return new BasicTemplateRenderer($paths['templates']);
        });
    }
}