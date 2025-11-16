<?php
declare(strict_types=1);

namespace App\Modules\Sites\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * SitesExport handles Excel export for sites data.
 */
class SitesExport implements FromArray, WithHeadings, WithStyles
{
    /**
     * Initialize the export with data array.
     *
     * @param array<int, array<string, mixed>> $data
     */
    public function __construct(private array $data)
    {
    }

    /**
     * Get the array representation of the export data.
     *
     * @return array<int, array<string, mixed>>
     */
    public function array(): array
    {
        return $this->data;
    }

    /**
     * Get the column headings for the export.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return array_keys($this->data[0] ?? []);
    }

    /**
     * Apply styles to the worksheet headers.
     *
     * @param Worksheet $sheet
     *
     * @return void
     */
    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
    }
}

