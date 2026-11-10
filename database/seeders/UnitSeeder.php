<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'description' => 'Satuan per butir / barang eceran.',
                'is_active' => true,
            ],
            [
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'description' => 'Satuan berat kilogram (1000 gram).',
                'is_active' => true,
            ],
            [
                'name' => 'Gram',
                'symbol' => 'gr',
                'description' => 'Satuan berat gram.',
                'is_active' => true,
            ],
            [
                'name' => 'Liter',
                'symbol' => 'L',
                'description' => 'Satuan volume cairan liter.',
                'is_active' => true,
            ],
            [
                'name' => 'Milliliter',
                'symbol' => 'ml',
                'description' => 'Satuan volume mililiter.',
                'is_active' => true,
            ],
            [
                'name' => 'Box',
                'symbol' => 'box',
                'description' => 'Kemasan kotak / kardus kecil.',
                'is_active' => true,
            ],
            [
                'name' => 'Pack',
                'symbol' => 'pack',
                'description' => 'Kemasan bungkus / pak.',
                'is_active' => true,
            ],
            [
                'name' => 'Dus',
                'symbol' => 'dus',
                'description' => 'Karton besar isi banyak.',
                'is_active' => true,
            ],
            [
                'name' => 'Botol',
                'symbol' => 'btl',
                'description' => 'Kemasan botol cairan.',
                'is_active' => true,
            ],
            [
                'name' => 'Gross',
                'symbol' => 'grs',
                'description' => 'Satuan 144 pcs.',
                'is_active' => true,
            ],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(['name' => $unit['name']], $unit);
        }
    }
}

