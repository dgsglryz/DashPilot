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
     * @param array<int, array<string, mixed>> $data
     */
    public function __construct(private array $data)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function array(): array
    {
        return $this->data;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return array_keys($this->data[0] ?? []);
    }

    /**
     * Apply styles to the worksheet.
     */
    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
    }
}

