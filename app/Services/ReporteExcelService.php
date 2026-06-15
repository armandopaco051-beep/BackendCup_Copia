<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class ReporteExcelService
{
    public function crear(array $reporte): array
    {
        $ruta = $this->rutaTemporal();
        $columnas = $reporte['columnas'];
        $campos = array_keys($columnas);
        $ultimaColumna = Coordinate::stringFromColumnIndex(max(count($columnas), 1));

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('CUP UAGRM')
            ->setTitle($reporte['titulo'])
            ->setSubject('Reporte administrativo generado por el sistema CUP');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte');
        $sheet->setCellValue('A1', $reporte['titulo']);
        $sheet->mergeCells("A1:{$ultimaColumna}1");
        $sheet->setCellValue('A2', 'Generado: '.$reporte['generado_en']);
        $sheet->mergeCells("A2:{$ultimaColumna}2");
        $sheet->setCellValue('A3', 'Filtros: '.$this->resumenFiltros($reporte['filtros']));
        $sheet->mergeCells("A3:{$ultimaColumna}3");

        foreach (array_values($columnas) as $indice => $titulo) {
            $columna = Coordinate::stringFromColumnIndex($indice + 1);
            $sheet->setCellValue("{$columna}5", $titulo);
        }

        $fila = 6;
        foreach ($reporte['datos'] as $registro) {
            foreach ($campos as $indice => $campo) {
                $columna = Coordinate::stringFromColumnIndex($indice + 1);
                $sheet->setCellValue("{$columna}{$fila}", $registro[$campo] ?? '');
            }
            $fila++;
        }

        $ultimaFila = max($fila - 1, 5);
        $sheet->freezePane('A6');
        $sheet->setAutoFilter("A5:{$ultimaColumna}{$ultimaFila}");
        $sheet->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '08285C'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
        ]);
        $sheet->getStyle("A2:{$ultimaColumna}3")->applyFromArray([
            'font' => [
                'size' => 10,
                'color' => ['rgb' => '5D6B80'],
            ],
        ]);
        $sheet->getStyle("A5:{$ultimaColumna}5")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '08285C'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("A5:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D9E1EE'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(5)->setRowHeight(22);

        for ($indice = 1; $indice <= count($columnas); $indice++) {
            $columna = Coordinate::stringFromColumnIndex($indice);
            $sheet->getColumnDimension($columna)->setAutoSize(true);
        }

        try {
            (new Xlsx($spreadsheet))->save($ruta);
        } catch (\Throwable $exception) {
            @unlink($ruta);

            throw new RuntimeException(
                'No se pudo generar el archivo Excel: '.$exception->getMessage(),
                previous: $exception,
            );
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        return [
            'ruta' => $ruta,
            'extension' => 'xlsx',
            'content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

    private function resumenFiltros(array $filtros): string
    {
        $activos = collect($filtros)
            ->except(['limite'])
            ->filter(fn ($valor): bool => $valor !== null && $valor !== '')
            ->map(fn ($valor, string $campo): string => "{$campo}: {$valor}")
            ->values()
            ->join(' | ');

        return $activos ?: 'Sin filtros adicionales';
    }

    private function rutaTemporal(): string
    {
        $directorio = is_writable(storage_path('app'))
            ? storage_path('app')
            : sys_get_temp_dir();
        $ruta = tempnam($directorio, 'reporte_');

        if ($ruta === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal del reporte.');
        }

        return $ruta;
    }
}
