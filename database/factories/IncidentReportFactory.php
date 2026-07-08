<?php

namespace Database\Factories;

use App\Enums\JenisInsiden;
use App\Enums\StatusInsiden;
use App\Models\BorrowingDetail;
use App\Models\IncidentReport;
use App\Models\ProductUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentReport>
 */
class IncidentReportFactory extends Factory
{
    protected $model = IncidentReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'borrowing_detail_id' => BorrowingDetail::factory(),
            'product_unit_id' => ProductUnit::factory(),
            'reported_by' => User::factory(),
            'jenis' => JenisInsiden::RusakRingan,
            'kronologi' => $this->faker->paragraph,
            'foto_bukti' => null,
            'status' => StatusInsiden::MenungguVerifikasiStaff,
            'verified_by' => null,
            'verified_at' => null,
            'batas_investigasi' => null,
            'finalized_by' => null,
            'finalized_at' => null,
            'status_ganti_rugi' => null,
            'catatan' => null,
        ];
    }
}
