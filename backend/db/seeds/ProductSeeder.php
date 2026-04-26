<?php

use Phinx\Seed\AbstractSeed;

class ProductSeeder extends AbstractSeed
{
    public function run(): void
    {
        $categories = [
            'Hardware',
            'Software',
            'Peripherals',
            'Accessories',
            'Office',
            'Networking',
        ];

        $adjectives = [
            'Enterprise',
            'Smart',
            'Pro',
            'Ultra',
            'Compact',
            'Advanced',
            'Portable',
            'Essential',
            'Modern',
            'Secure',
        ];

        $items = [
            'Laptop',
            'Monitor',
            'Keyboard',
            'Mouse',
            'Router',
            'Printer',
            'SSD',
            'Webcam',
            'Headset',
            'Docking Station',
        ];

        $products = [];

        for ($i = 1; $i <= 25; $i++) {
            $adjective = $adjectives[array_rand($adjectives)];
            $item = $items[array_rand($items)];
            $category = $categories[array_rand($categories)];

            $price = number_format(mt_rand(5000, 500000) / 100, 2, '.', '');

            $products[] = [
                'sku' => sprintf('PRD-%04d-%d', $i, mt_rand(10, 99)),
                'name' => sprintf('%s %s', $adjective, $item),
                'category' => $category,
                'price' => $price,
            ];
        }

        $this->table('products')->insert($products)->saveData();
    }
}
