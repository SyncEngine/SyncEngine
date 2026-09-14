<?php

namespace SyncEngine\Framework\Translation\Extractor;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Extractor\AbstractFileExtractor;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * JsExtractor extracts translation messages from JavaScript/JSX files.
 */
final class JsExtractor extends AbstractFileExtractor implements ExtractorInterface
{
    private string $defaultDomain = 'messages';
    private string $prefix = '';

    public function setDefaultDomain( string $domain ): void
    {
        $this->defaultDomain = $domain;
    }

    public function extract(iterable|string $resource, MessageCatalogue $catalogue): void
    {
        foreach ($this->extractFiles($resource) as $file) {
            $content = file_get_contents($file);
            if (false === $content) {
                continue;
            }

            // Match t('key'), trans('key'), t( 'key' ), etc.
            // Also captures optional second parameter object for context
            // \b ensures we only match standalone 't(' / 'trans(' not 'it(', 'test(', etc.
            $pattern = '/\b(?:trans|t)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*\{[^}]*\})?\s*\)/';

            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $key) {
                    // Skip obvious false positives (single chars, paths, selectors)
                    if (strlen($key) < 3) continue;
                    if ($key === './' || $key === '/' || $key === '://') continue;
                    if (strpos($key, '../') === 0 || strpos($key, './') === 0) continue;
                    if (preg_match('/^\./', $key)) continue;

                    $domain = $this->detectDomain($content, $file);
                    $catalogue->set(
                        trim($key),
                        $this->prefix . trim($key),
                        $domain,
                    );
                }
            }

            // Also detect domain from trans() calls or import statements
            $this->detectDomainFromImports($content, $file);
        }
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    protected function canBeExtracted(string $file): bool
    {
        return in_array(
            pathinfo($file, \PATHINFO_EXTENSION),
            ['js', 'jsx'],
            true,
        ) && $this->isFile($file) && preg_match('/\bt\(|trans\(/', file_get_contents($file));
    }

    protected function extractFromDirectory(array|string $resource): iterable|Finder
    {
        if (!class_exists(Finder::class)) {
            throw new \LogicException(
                \sprintf('You cannot use "%s" as the "symfony/finder" package is not installed. Try running "composer require symfony/finder".', static::class)
            );
        }

        $finder = new Finder();

        return $finder->files()
            ->name(['*.js', '*.jsx', '*.ts', '*.tsx'])
            ->in($resource);
    }

    /**
     * Detect the domain from the file context.
     */
    private function detectDomain(string $content, \SplFileInfo $file): string
    {
        // Check for explicit domain parameter in t() calls: t('key', params, 'domain')
        if (preg_match("/t\s*\(\s*['\"][^'\"]+['\"]\s*,\s*\{[^}]*\}\s*,\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
            return $matches[1];
        }

        // Check for import of useTranslator with domain configuration
        if (preg_match('/useTranslator.*domain\s*[:=]\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            return $matches[1];
        }

        // Default to the configured defaultDomain
        return $this->defaultDomain;
    }

    /**
     * Detect domain from import statements.
     */
    private function detectDomainFromImports(string $content, \SplFileInfo $file): void
    {
        // Could add more sophisticated domain detection here
        // For now, rely on the default domain or explicit t() parameters
    }
}
