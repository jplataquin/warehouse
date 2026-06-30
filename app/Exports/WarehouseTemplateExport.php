<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class WarehouseTemplateExport implements FromCollection, WithEvents, WithHeadings
{
    public function collection()
    {
        return collect([
            [
                'name' => 'Sample SITE Warehouse',
                'type' => 'SITE',
            ],
            [
                'name' => 'Sample CENTRAL Warehouse',
                'type' => 'CENTRAL',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'name',
            'type',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Type Validation (SITE, CENTRAL)
                $typeValidation = $sheet->getCell('B2')->getDataValidation();
                $typeValidation->setType(DataValidation::TYPE_LIST);
                $typeValidation->setFormula1('"SITE,CENTRAL"');
                $typeValidation->setAllowBlank(false);
                $typeValidation->setShowDropDown(true);

                // Apply to more rows
                for ($i = 3; $i <= 1000; $i++) {
                    $sheet->getCell("B{$i}")->setDataValidation(clone $typeValidation);
                }
            },
        ];
    }
}
