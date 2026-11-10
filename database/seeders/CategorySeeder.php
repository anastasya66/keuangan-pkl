<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Sembako',
                'description' => 'Bahan pokok kebutuhan pangan sehari-hari seperti beras, gula, dan minyak.',
                'is_active' => true,
            ],
            [
                'name' => 'Minuman',
                'description' => 'Aneka minuman kemasan, kopi, teh, dan air mineral.',
                'is_active' => true,
            ],
            [
                'name' => 'Makanan Ringan',
                'description' => 'Snack, biskuit, wafer, cokelat, dan aneka keripik.',
                'is_active' => true,
            ],
            [
                'name' => 'Bumbu & Dapur',
                'description' => 'Bumbu dapur, garam, kecap, saus, dan penyedap rasa.',
                'is_active' => true,
            ],
            [
                'name' => 'Perawatan Diri & Kebersihan',
                'description' => 'Sabun mandi, sampo, pasta gigi, detergen, dan pembersih lantai.',
                'is_active' => true,
            ],
            [
                'name' => 'Alat Tulis Kantor',
                'description' => 'Perlengkapan alat tulis sekolah dan kantor.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['name' => $cat['name']], $cat);
        }
    }
}

