<?php
declare(strict_types=1);

namespace MaintenancePro\Presentation\Template;

/**
 * Defines the contract for a template renderer.
 *
 * A template renderer is responsible for rendering templates and making data
 * available to them.
 */
interface TemplateRendererInterface
{
    /**
     * Renders a template with the given data.
     *
     * @param string               $template The name of the template file to render.
     * @param array<string, mixed> $data     An array of data to make available to the template.
     * @return string The rendered output.
     */
    public function render(string $template, array $data = []): string;

    /**
     * Checks if a template file exists.
     *
     * @param string $template The name of the template file to check.
     * @return bool True if the template exists, false otherwise.
     */
    public function exists(string $template): bool;
}