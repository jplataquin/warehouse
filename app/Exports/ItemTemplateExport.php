<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class ItemTemplateExport implements FromCollection, WithEvents, WithHeadings
{
    public function collection()
    {
        return collect([
            [
                'type' => 'CONSUMABLE',
                'name' => 'Sample Item',
                'specification' => 'Sample Spec',
                'unit' => 'PCS',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'type',
            'name',
            'specification',
            'unit',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Define the options for the dropdown
                $options = ['CONSUMABLE', 'ASSET', 'RECOVERABLE'];

                // Set the validation for the 'type' column (Column A) from row 2 to 1000
                $validation = $sheet->getCell('A2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please choose a value from the dropdown list.');
                $validation->setFormula1('"'.implode(',', $options).'"');

                // Apply to more rows
                for ($i = 3; $i <= 1000; $i++) {
                    $sheet->getCell("A{$i}")->setDataValidation(clone $validation);
                }
            },
        ];
    }
}
