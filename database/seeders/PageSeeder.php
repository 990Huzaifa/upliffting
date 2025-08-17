<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $list = [
            [
                'title' => 'about',
                'content' => 'This is the content for About Us page.',
                'role' => 'rider'
            ],
            [
                'title' => 'pnp',
                'content' => 'This is the content for Privacy Policy page.',
                'role' => 'rider'
            ],
            [
                'title' => 'tnc',
                'content' => 'This is the content for Terms and Conditions page.',
                'role' => 'rider'
            ],
            [
                'title' => 'about',
                'content' => 'This is the content for About Us page.',
                'role' => 'customer'
            ],
            [
                'title' => 'pnp',
                'content' => 'This is the content for Privacy Policy page.',
                'role' => 'customer'
            ],
            [
                'title' => 'tnc',
                'content' => 'This is the content for Terms and Conditions page.',
                'role' => 'customer'
            ]
        ];
        
        foreach ($list as $item) {
            Page::updateOrCreate(
                ['title' => $item['title'], 'role' => $item['role']],
                ['content' => $item['content']]
            );
        }
    }
}
