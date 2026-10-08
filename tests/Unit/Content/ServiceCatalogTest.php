<?php

namespace App\Tests\Unit\Content;

use App\Content\ServiceCatalog;
use PHPUnit\Framework\TestCase;

final class ServiceCatalogTest extends TestCase
{
    private const array REQUIRED_KEYS = ['slug', 'title', 'icon', 'summary', 'lead', 'offers', 'examples', 'technologies'];

    private ServiceCatalog $catalog;

    protected function setUp(): void
    {
        $this->catalog = new ServiceCatalog();
    }

    public function testFindAllReturnsTheSixServices(): void
    {
        $this->assertCount(6, $this->catalog->findAll());
    }

    public function testEachServiceHasAllItsInformation(): void
    {
        foreach ($this->catalog->findAll() as $service) {
            foreach (self::REQUIRED_KEYS as $key) {
                $this->assertArrayHasKey($key, $service, \sprintf('Le service « %s » n\'a pas de « %s ».', $service['slug'] ?? '?', $key));
            }

            $this->assertNotEmpty($service['offers']);
            $this->assertNotEmpty($service['technologies']);
        }
    }

    public function testEachOfferHasATitleAndAText(): void
    {
        foreach ($this->catalog->findAll() as $service) {
            foreach ($service['offers'] as $offer) {
                $this->assertCount(2, $offer, \sprintf('Une offre du service « %s » doit être [titre, texte].', $service['slug']));
            }
        }
    }

    public function testSlugsAreUnique(): void
    {
        $slugs = array_column($this->catalog->findAll(), 'slug');

        $this->assertSame($slugs, array_unique($slugs));
    }

    /**
     * Un slug est utilisé dans l'URL : /services/{slug}
     */
    public function testSlugsAreUrlFriendly(): void
    {
        foreach ($this->catalog->findAll() as $service) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]+(-[a-z0-9]+)*$/', $service['slug']);
        }
    }

    public function testEachIconExists(): void
    {
        $iconDirectory = \dirname(__DIR__, 3).'/public/images/icones';

        foreach ($this->catalog->findAll() as $service) {
            $this->assertFileExists($iconDirectory.'/'.$service['icon']);
        }
    }

    public function testFindBySlugReturnsTheService(): void
    {
        $service = $this->catalog->findBySlug('cybersecurite');

        $this->assertNotNull($service);
        $this->assertSame('cybersecurite', $service['slug']);
    }

    public function testFindBySlugReturnsNullForAnUnknownSlug(): void
    {
        $this->assertNull($this->catalog->findBySlug('service-inconnu'));
    }
}
