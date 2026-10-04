<?php

declare(strict_types=1);

namespace Tests\Unit\View\Components\Backgrounds;

use App\View\Components\Backgrounds\HomeBanner;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomeBannerTest extends TestCase
{
    public function refreshDatabase(): void
    {
        // This test only exercises the component's array normalization and doesn't need a database.
    }

    #[Test]
    public function fullBleedUsesTheSelectedBackgroundsResolvedVariants(): void
    {
        $banner = $this->makeBanner([
            'image' => 'img/bg/pier_night.jpg',
            'position' => ['x' => '12%', 'y' => '34%'],
            'size' => 'cover',
            'variants' => [
                'lg' => ['position' => ['x' => '77%', 'y' => '61%']],
            ],
        ]);

        $this->assertSame(asset('img/bg/pier_night.jpg'), $banner->baseVariant['url']);
        $this->assertSame('12%', $banner->baseVariant['position']['x']);
        $this->assertSame('77%', $banner->lgVariant['position']['x']);
        $this->assertSame('61%', $banner->lgVariant['position']['y']);
        $this->assertSame('top-banner-frame top-banner-frame-full', $banner->frameClasses);
        $this->assertSame('contact', $banner->contactId);
    }

    #[Test]
    public function lgInheritsFromSmWhenLgHasNoOverrideOfItsOwn(): void
    {
        $banner = $this->makeBanner([
            'image' => 'img/bg/pier_night.jpg',
            'position' => ['x' => '50%', 'y' => '50%'],
            'size' => 'cover',
            'variants' => [
                'sm' => [
                    'position' => ['x' => '18%', 'y' => '22%'],
                    'size' => '110% auto',
                ],
            ],
        ]);

        $this->assertSame('18%', $banner->smVariant['position']['x']);
        $this->assertSame('18%', $banner->lgVariant['position']['x']);
        $this->assertSame('22%', $banner->lgVariant['position']['y']);
        $this->assertSame('110% auto', $banner->lgVariant['size']);
    }

    #[Test]
    public function aPreResolvedUrlIsUsedAsIsInsteadOfBeingResolvedAsAnAssetPath(): void
    {
        $blobUrl = 'blob:http://localhost/temporary-upload-preview';

        $banner = $this->makeBanner([
            'url' => $blobUrl,
            'position' => ['x' => '50%', 'y' => '50%'],
            'size' => 'cover',
        ], fullBleed: false);

        $this->assertSame($blobUrl, $banner->baseVariant['url']);
        $this->assertSame($blobUrl, $banner->smVariant['url']);
        $this->assertSame($blobUrl, $banner->lgVariant['url']);
    }

    #[Test]
    public function anInvalidPreviewBreakpointFallsBackToLg(): void
    {
        $banner = $this->makeBanner(['image' => 'img/bg/pier_night.jpg'], fullBleed: false, previewBreakpoint: 'not-a-real-breakpoint');

        $this->assertSame('lg', $banner->previewBreakpoint);
        $this->assertSame('top-banner-frame top-banner-frame-preview top-banner-preview-lg', $banner->frameClasses);
    }

    #[Test]
    public function missingBackgroundEmitsTheAssetResolvedFallbackAtEveryBreakpoint(): void
    {
        $banner = $this->makeBanner([]);
        $url = asset('img/bg/pier_night.jpg');

        $this->assertSame($url, $banner->baseVariant['url']);
        $this->assertSame($url, $banner->smVariant['url']);
        $this->assertSame($url, $banner->lgVariant['url']);
        $html = preg_replace('/\s+/', ' ', (string) $this->blade('<x-backgrounds.home-banner />'));

        foreach (['', '-sm', '-lg'] as $breakpoint) {
            $this->assertStringContainsString("--top-banner-bg-url{$breakpoint}: url('{$url}')", $html);
        }
    }

    #[Test]
    public function emptyPreviewExplicitlyDisablesTheImageInsteadOfShowingTheFallback(): void
    {
        $html = preg_replace('/\s+/', ' ', (string) $this->blade(
            '<x-backgrounds.home-banner :background="[\'url\' => \'\']" :full-bleed="false" />',
        ));

        foreach (['', '-sm', '-lg'] as $breakpoint) {
            $this->assertStringContainsString("--top-banner-bg-url{$breakpoint}: none", $html);
        }
    }

    /**
     * @param  array<string, mixed>  $background
     */
    private function makeBanner(array $background, bool $fullBleed = true, string $previewBreakpoint = 'lg'): HomeBanner
    {
        return resolve(HomeBanner::class, [
            'background' => $background,
            'fullBleed' => $fullBleed,
            'previewBreakpoint' => $previewBreakpoint,
        ]);
    }
}
