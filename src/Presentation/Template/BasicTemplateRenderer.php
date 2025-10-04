<?php
declare(strict_types=1);

namespace MaintenancePro\Presentation\Template;

class BasicTemplateRenderer implements TemplateRendererInterface
{
    private string $templateDir;

    public function __construct(string $templateDir)
    {
        $this->templateDir = $templateDir;
    }

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

    public function exists(string $template): bool
    {
        return file_exists($this->templateDir . '/' . $template);
    }
}