<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class ProjectTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            [
                'name' => 'Sample Project Alpha',
                'create_warehouse' => 'YES'
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'name',
            'create_warehouse'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Define the options for the 'create_warehouse' column
                $options = ['YES', 'NO'];
                
                // Set the validation for the 'create_warehouse' column (Column B) from row 2 to 1000
                $validation = $sheet->getCell('B2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please choose YES or NO.');
                $validation->setFormula1('"' . implode(',', $options) . '"');

                // Apply to more rows
                for ($i = 3; $i <= 1000; $i++) {
                    $sheet->getCell("B{$i}")->setDataValidation(clone $validation);
                }
            },
        ];
    }
}
