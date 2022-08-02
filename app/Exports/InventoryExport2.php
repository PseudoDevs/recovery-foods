<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryExport2 implements WithHeadings, FromArray, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $columns;
    public function __construct($data, $columns)
    {
        $this->data = $data;
        $this->columns = $columns;
    }

    //Export as array
    function array(): array
    {
        return $this->data;
    }

    //With headers
    public function headings(): array
    {
        return $this->columns;
    }
    
    //Styles for cells
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->mergeCells('C1:C2');
        $sheet->mergeCells('D1:D2');
        $sheet->mergeCells('E1:F1');
        $sheet->mergeCells('G1:H1');
        $sheet->mergeCells('I1:J1');
        $sheet->mergeCells('K1:L1');
        $sheet->mergeCells('M1:N1');
        $sheet->mergeCells('O1:P1');
        $sheet->mergeCells('Q1:Q2');
        $sheet->mergeCells('R1:R2');
        $sheet->mergeCells('S1:S2');
        $sheet->mergeCells('T1:T2');
        $sheet->mergeCells('U1:U2');
        $sheet->mergeCells('V1:W1');
        $sheet->mergeCells('X1:Y1');
        $sheet->mergeCells('Z1:AA1');
        $sheet->mergeCells('AB1:AC1');
        $sheet->getStyle(1)->getAlignment()->applyFromArray(
            array('horizontal' => 'center')
        );
        $sheet->getStyle(1)->getAlignment()->applyFromArray(
            array('vertical' => 'center')
        );
        $sheet->getStyle(2)->getAlignment()->applyFromArray(
            array('horizontal' => 'center')
        );
        $sheet->getStyle(2)->getAlignment()->applyFromArray(
            array('vertical' => 'center')
        );
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
        ];
    }
}
