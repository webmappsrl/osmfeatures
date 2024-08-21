<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class XLSController extends Controller
{
    public function export(Request $request)
    {
        // Valida i parametri della richiesta
        $request->validate([
            'models' => ['required', function ($attribute, $value, $fail) {
                if (!is_string($value) && !is_array($value)) {
                    $fail('The ' . $attribute . ' must be a string or an array.');
                }
            }],
            'file_name' => 'required|string',
            'type' => 'required|string',
        ]);

        // Converti 'models' da JSON string a array
        $modelsJson = $request->input('models');
        $models = is_string($modelsJson) ? json_decode($modelsJson, true) : $modelsJson;

        // Verifica che la decodifica JSON sia riuscita
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON format for models parameter.'], 400);
        }

        $fileName = $request->input('file_name');
        $type = $request->input('type');

        // Prepara i dati per l'Excel
        $data = collect($models)->map(function ($model) {
            return [
                'osmfeatures_id' => $model['osmfeatures_id'],
                'osmfeatures_url' => URL::to("/resources/" . strtolower($model['type']) . "/{$model['nova_id']}"),
                'osm_url' => $model['osm_url'],
            ];
        });

        // Genera e restituisci il file Excel
        return Excel::download(new class($data) implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function collection()
            {
                return collect($this->data);
            }

            public function headings(): array
            {
                return [
                    'osmfeatures_id',
                    'osmfeatures_url',
                    'osm_url',
                ];
            }

            public function map($row): array
            {
                return [
                    $row['osmfeatures_id'],
                    $row['osmfeatures_url'],
                    $row['osm_url'],
                ];
            }

            public function registerEvents(): array
            {
                return [
                    AfterSheet::class => function (AfterSheet $event) {
                        $sheet = $event->sheet;
                        $highestRow = $sheet->getHighestRow();
                        $columns = ['B', 'C'];

                        foreach ($columns as $column) {
                            for ($row = 2; $row <= $highestRow; $row++) {
                                $cellCoordinate = $column . $row;
                                $cellValue = $sheet->getCell($cellCoordinate)->getValue();
                                if (filter_var($cellValue, FILTER_VALIDATE_URL)) {
                                    $sheet->getCell($cellCoordinate)->setHyperlink(new Hyperlink($cellValue));
                                    $sheet->getStyle($cellCoordinate)->applyFromArray([
                                        'font' => [
                                            'color' => ['rgb' => '0000FF'], // Blu tipico dei collegamenti ipertestuali
                                            'underline' => 'single'
                                        ]
                                    ]);
                                }
                            }
                        }
                    },
                ];
            }
        }, $fileName, \Maatwebsite\Excel\Excel::XLS);
    }
}
