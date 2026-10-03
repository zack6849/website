<?php

declare(strict_types=1);

namespace App\Services\Backgrounds;

use App\Support\BackgroundCssValue;
use Illuminate\Support\Str;

class BackgroundNormalizer
{
    public const string DEFAULT_IMAGE = 'img/bg/pier_night.jpg';
    public const float DEFAULT_OVERLAY = 0.68;
    public const string DEFAULT_SIZE = 'cover';

    private const array RESPONSIVE_VARIANT_KEYS = ['base', 'sm', 'lg'];

    public function normalize(array $background, array $defaults, string $key): array
    {
        $background = array_replace_recursive($defaults, $background);
        $position = $background['position'] ?? [];

        $background['key'] = $key;
        $background['image'] = is_string($background['image'] ?? null)
            ? $background['image']
            : self::DEFAULT_IMAGE;
        $background['url'] = $this->resolveAssetUrl($background['image']);
        $background['overlay'] = max(0.0, min(1.0, (float) ($background['overlay'] ?? self::DEFAULT_OVERLAY)));
        $background['size'] = $this->normalizeBackgroundSize($background['size'] ?? self::DEFAULT_SIZE);
        $background['position'] = $this->normalizePosition($position, ['x' => '50%', 'y' => '50%']);
        $background['variants'] = $this->normalizeResponsiveVariants($background, $defaults);

        return $background;
    }

    public function resolveAssetUrl(string $image): string
    {
        if (Str::startsWith($image, ['http://', 'https://', '//'])) {
            return $image;
        }

        return asset($image);
    }

    /**
     * Each breakpoint cascades from the previous one (base -> sm -> lg), matching
     * the cascade used by the admin form's live preview (BackgroundFormData::previewStyle())
     * and the public home-banner component, so an override left unset at `lg`
     * inherits from `sm` rather than jumping straight back to `base`.
     */
    private function normalizeResponsiveVariants(array $background, array $defaults): array
    {
        $defaultVariants = is_array($defaults['variants'] ?? null) ? $defaults['variants'] : [];
        $configuredVariants = is_array($background['variants'] ?? null) ? $background['variants'] : [];
        $baseVariant = $this->normalizeVariant([
            'image' => $background['image'],
            'position' => $background['position'],
            'size' => $background['size'],
        ]);

        $variants = [];
        $previous = $baseVariant;

        foreach (self::RESPONSIVE_VARIANT_KEYS as $variantKey) {
            $variantConfig = array_replace_recursive(
                is_array($defaultVariants[$variantKey] ?? null) ? $defaultVariants[$variantKey] : [],
                is_array($configuredVariants[$variantKey] ?? null) ? $configuredVariants[$variantKey] : [],
            );

            $variants[$variantKey] = $this->normalizeVariant(
                array_replace_recursive($previous, $variantConfig),
                $previous,
            );

            $previous = $variants[$variantKey];
        }

        return $variants;
    }

    private function normalizeVariant(array $variant, array $fallback = []): array
    {
        $variant = array_replace_recursive($fallback, $variant);
        $position = $variant['position'] ?? [];
        $fallbackPosition = is_array($fallback['position'] ?? null) ? $fallback['position'] : [];
        $image = is_string($variant['image'] ?? null) ? $variant['image'] : self::DEFAULT_IMAGE;

        return [
            'image' => $image,
            'url' => $this->resolveAssetUrl($image),
            'size' => $this->normalizeBackgroundSize($variant['size'] ?? ($fallback['size'] ?? self::DEFAULT_SIZE)),
            'position' => $this->normalizePosition($position, array_replace(['x' => '50%', 'y' => '50%'], $fallbackPosition)),
        ];
    }

    private function normalizePosition(mixed $position, array $fallback): array
    {
        $position = is_array($position) ? $position : [];

        return [
            'x' => BackgroundCssValue::normalizePositionToken($position['x'] ?? null, $fallback['x'] ?? '50%'),
            'y' => BackgroundCssValue::normalizePositionToken($position['y'] ?? null, $fallback['y'] ?? '50%'),
        ];
    }

    private function normalizeBackgroundSize(mixed $size): string
    {
        return BackgroundCssValue::normalizeSize($size, self::DEFAULT_SIZE);
    }
}
