<?php

namespace Database\Seeders;

use App\Enums\Products\ProductStatusEnum;
use App\Enums\Products\ProductVariationTypeEnum;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductSeeder extends Seeder
{
    /**
     * Products and images come from data/products.json and data/products/,
     * a curated snapshot of https://dummyjson.com so seeding works offline.
     */
    private const DATA_PATH = __DIR__.'/data';

    /**
     * Which seeded vendor sells each group of products in the manifest.
     */
    private const VENDOR_EMAILS = [
        'electronics' => 'vendor1@tradely.com',
        'home' => 'vendor1@tradely.com',
        'fashion' => 'vendor2@tradely.com',
        'beauty' => 'vendor2@tradely.com',
        'sports' => 'vendor2@tradely.com',
        'grocery' => 'vendor2@tradely.com',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()->instance(FileManipulator::class, new class extends FileManipulator
        {
            public function createDerivedFiles(Media $media, array $onlyConversionNames = [], bool $onlyMissing = false, bool $withResponsiveImages = false, bool $queueAll = false): void {}
        });

        $products = json_decode(file_get_contents(self::DATA_PATH.'/products.json'), true);

        $currencyId = Currency::where('slug', 'us-dollar')->value('id');
        $vendorIds = User::whereIn('email', self::VENDOR_EMAILS)->pluck('id', 'email');
        $categories = Category::whereIn('name', Arr::pluck($products, 'category'))->get()->keyBy('name');

        mt_srand(2025);

        foreach ($products as $data) {
            $category = $categories[$data['category']];
            $vendorId = $vendorIds[self::VENDOR_EMAILS[$data['vendor']]];
            $listedAt = now()->subMinutes(mt_rand(60, 60 * 24 * 90));

            $product = Product::create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'price' => $data['price'],
                'quantity' => $data['quantity'],
                'status' => ProductStatusEnum::PUBLISHED->value,
                'is_featured' => $data['featured'],
                'department_id' => $category->department_id,
                'category_id' => $category->id,
                'currency_id' => $currencyId,
                'created_by' => $vendorId,
                'updated_by' => $vendorId,
                'created_at' => $listedAt,
                'updated_at' => $listedAt,
            ]);

            foreach ($data['images'] as $image) {
                $media = $product->addMedia(self::DATA_PATH.'/products/'.$image)
                    ->preservingOriginal()
                    ->toMediaCollection('images');

                $this->copyConversions($media, dirname($image));
            }

            if ($data['variation']) {
                $this->seedVariation($product, $data['variation']);
            }
        }
    }

    private function copyConversions(Media $media, string $productDir): void
    {
        foreach (ConversionCollection::createForMedia($media) as $conversion) {
            $target = $media->getPath($conversion->getName());
            $source = self::DATA_PATH.'/products/'.$productDir.'/conversions/'.basename($target);

            File::ensureDirectoryExists(dirname($target));
            File::copy($source, $target);

            $media->markAsConversionGenerated($conversion->getName());
        }
    }

    private function seedVariation(Product $product, array $variation): void
    {
        $type = $product->variationTypes()->create([
            'name' => $variation['name'],
            'type' => ProductVariationTypeEnum::RADIO->value,
        ]);

        $quantityPerOption = max(1, intdiv($product->quantity, count($variation['options'])));

        foreach ($variation['options'] as $optionData) {
            $option = $type->options()->create(['name' => $optionData['name']]);

            $product->variations()->create([
                'variation_type_option_ids' => [$option->id],
                'quantity' => $quantityPerOption,
                'price' => $product->price + $optionData['price_delta'],
            ]);
        }
    }
}
