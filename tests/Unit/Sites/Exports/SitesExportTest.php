<?php
declare(strict_types=1);

namespace Tests\Unit\Sites\Exports;

use App\Modules\Sites\Exports\SitesExport;
use Tests\TestCase;

class SitesExportTest extends TestCase
{
    public function test_sites_export_returns_data_array(): void
    {
        $data = [
            ['ID' => 1, 'Name' => 'Test Site'],
            ['ID' => 2, 'Name' => 'Another Site'],
        ];

        $export = new SitesExport($data);

        $this->assertEquals($data, $export->array());
    }

    public function test_sites_export_returns_headings(): void
    {
        $data = [
            ['ID' => 1, 'Name' => 'Test Site'],
        ];

        $export = new SitesExport($data);

        $this->assertEquals(['ID', 'Name'], $export->headings());
    }

    public function test_sites_export_handles_empty_data(): void
    {
        $export = new SitesExport([]);

        $this->assertEquals([], $export->array());
        $this->assertEquals([], $export->headings());
    }
}

