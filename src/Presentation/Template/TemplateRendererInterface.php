<?php
declare(strict_types=1);

namespace MaintenancePro\Presentation\Template;

interface TemplateRendererInterface
{
    public function render(string $template, array $data = []): string;
    public function exists(string $template): bool;
}