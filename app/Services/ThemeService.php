<?php

namespace App\Services;

use Illuminate\Support\Facades\View;

class ThemeService
{
    protected string $theme = 'default';

    /**
     * Set the active theme
     */
    public function setTheme(string $theme): self
    {
        if ($this->themeExists($theme)) {
            $this->theme = $theme;
        }

        return $this;
    }

    /**
     * Get the active theme
     */
    public function getTheme(): string
    {
        return $this->theme;
    }

    /**
     * Check if a theme exists
     */
    public function themeExists(string $theme): bool
    {
        return array_key_exists($theme, config('themes.themes', []));
    }

    /**
     * Get all available themes
     */
    public function getAvailableThemes(): array
    {
        return config('themes.themes', []);
    }

    /**
     * Get theme configuration
     */
    public function getThemeConfig(string $theme = null): array
    {
        $theme = $theme ?? $this->theme;
        return config("themes.themes.{$theme}", []);
    }

    /**
     * Resolve a view path for the current theme
     */
    public function view(string $view, array $data = []): \Illuminate\Contracts\View\View
    {
        // Try theme-specific view first
        $themeView = "themes.{$this->theme}.{$view}";

        if (View::exists($themeView)) {
            return view($themeView, $data);
        }

        // Fall back to default theme
        $defaultView = "themes.default.{$view}";

        if (View::exists($defaultView)) {
            return view($defaultView, $data);
        }

        // Fall back to storefront views
        return view("storefront.{$view}", $data);
    }

    /**
     * Get theme colors as CSS variables
     */
    public function getCssVariables(): string
    {
        $colors = $this->getThemeConfig()['colors'] ?? [];
        $css = ':root {';

        foreach ($colors as $name => $value) {
            $css .= "--color-{$name}: {$value};";
        }

        $css .= '}';

        return $css;
    }
}
