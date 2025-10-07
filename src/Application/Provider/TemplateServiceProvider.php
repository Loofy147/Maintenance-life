<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Provider;

use MaintenancePro\Application\ServiceContainer;
use MaintenancePro\Presentation\Template\BasicTemplateRenderer;
use MaintenancePro\Presentation\Template\TemplateRendererInterface;

/**
 * Registers the application's template rendering service.
 *
 * This provider binds the TemplateRendererInterface to the BasicTemplateRenderer
 * implementation, providing a simple way to render PHP-based templates.
 */
class TemplateServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the template renderer service in the service container.
     *
     * @param ServiceContainer $container The service container.
     */
    public function register(ServiceContainer $container): void
    {
        $container->singleton(TemplateRendererInterface::class, function ($c) {
            $paths = $c->get('paths');
            return new BasicTemplateRenderer($paths['templates']);
        });
    }
}