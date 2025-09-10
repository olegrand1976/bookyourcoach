<?php

namespace Tests\Unit\Models;

use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_product_category()
    {
        $category = ProductCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test category description',
            'icon' => 'test-icon',
            'color' => '#ff0000',
            'is_active' => true
        ]);

        $this->assertInstanceOf(ProductCategory::class, $category);
        $this->assertEquals('Test Category', $category->name);
        $this->assertEquals('test-category', $category->slug);
        $this->assertEquals('test-icon', $category->icon);
        $this->assertEquals('#ff0000', $category->color);
        $this->assertTrue($category->is_active);
    }

    public function test_has_many_products()
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id
        ]);

        $this->assertTrue($category->products->contains($product));
    }

    public function test_scope_active()
    {
        ProductCategory::factory()->create(['is_active' => true]);
        ProductCategory::factory()->create(['is_active' => false]);

        $activeCategories = ProductCategory::active()->get();

        $this->assertCount(1, $activeCategories);
        $this->assertTrue($activeCategories->first()->is_active);
    }

    public function test_scope_by_slug()
    {
        ProductCategory::factory()->create(['slug' => 'test-slug']);
        ProductCategory::factory()->create(['slug' => 'other-slug']);

        $categories = ProductCategory::bySlug('test-slug')->get();

        $this->assertCount(1, $categories);
        $this->assertEquals('test-slug', $categories->first()->slug);
    }

    public function test_get_icon_attribute_with_null()
    {
        $category = ProductCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'icon' => null,
            'is_active' => true
        ]);

        $this->assertEquals('📦', $category->getIconAttribute(null));
    }

    public function test_get_color_attribute_with_null()
    {
        $category = ProductCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'color' => null,
            'is_active' => true
        ]);

        $this->assertEquals('#6B7280', $category->getColorAttribute(null));
    }

    public function test_is_active_casting()
    {
        $category = ProductCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true
        ]);

        $this->assertIsBool($category->is_active);
        $this->assertTrue($category->is_active);
    }

    public function test_fillable_attributes()
    {
        $category = new ProductCategory();
        $fillable = $category->getFillable();

        $expectedFillable = [
            'name',
            'slug',
            'description',
            'icon',
            'color',
            'is_active'
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_casts()
    {
        $category = new ProductCategory();
        $casts = $category->getCasts();

        $this->assertArrayHasKey('is_active', $casts);
    }
}
