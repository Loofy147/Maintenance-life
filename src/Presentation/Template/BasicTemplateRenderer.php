<?php
declare(strict_types=1);

namespace MaintenancePro\Presentation\Template;

/**
 * A basic template renderer that uses native PHP templates.
 *
 * This renderer includes PHP files directly and uses output buffering to capture
 * the rendered content.
 */
class BasicTemplateRenderer implements TemplateRendererInterface
{
    private string $templateDir;

    /**
     * BasicTemplateRenderer constructor.
     *
     * @param string $templateDir The base directory where template files are located.
     */
    public function __construct(string $templateDir)
    {
        $this->templateDir = $templateDir;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception If the template file is not found.
     */
    public function render(string $template, array $data = []): string
    {
        $path = $this->templateDir . '/' . $template;
        if (!$this->exists($template)) {
            throw new \Exception("Template not found: {$template}");
        }
        extract($data);
        ob_start();
        include $path;
        return ob_get_clean();
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $template): bool
    {
        return file_exists($this->templateDir . '/' . $template);
    }
}