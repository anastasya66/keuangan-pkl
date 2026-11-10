<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'SUP-001',
                'name' => 'Distributor Sembako Utama',
                'phone' => '081234567890',
                'email' => 'sembakoutama@supplier.test',
                'address' => 'Jl. Pasar Besar No. 12, Surabaya',
                'contact_person' => 'Bpk. Ahmad Fauzi',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-002',
                'name' => 'PT Mitra Pangan Mandiri',
                'phone' => '082198765432',
                'email' => 'mitrapangan@supplier.test',
                'address' => 'Kawasan Pergudangan Margomulyo Blok B3',
                'contact_person' => 'Ibu Ratna Susanti',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-003',
                'name' => 'CV Sumber Rezeki Grosir',
                'phone' => '085712345678',
                'email' => 'sumberrezeki@supplier.test',
                'address' => 'Jl. Niaga Raya No. 45, Sidoarjo',
                'contact_person' => 'Bpk. Hendra Wijaya',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::firstOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}

