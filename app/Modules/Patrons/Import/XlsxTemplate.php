<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Import;

use RuntimeException;
use ZipArchive;

/** Baut die Excel-Vorlage für die Klassenleitungen: Kopfzeile, Datumsspalte mit Datumsformat, zweites Blatt mit Hinweisen. */
final class XlsxTemplate
{
    /** @return string Inhalt der .xlsx-Datei */
    public function build(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bcx');

        if ($path === false) {
            throw new RuntimeException('Die Vorlage konnte nicht erstellt werden.');
        }

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Klassenliste" sheetId="1" r:id="rId1"/><sheet name="Hinweise" sheetId="2" r:id="rId2"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="DD.MM.YYYY"/></numFmts><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE9DDEA"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="2" width="22" customWidth="1" style="3"/><col min="3" max="3" width="16" customWidth="1" style="2"/><col min="4" max="4" width="34" customWidth="1" style="3"/><col min="5" max="5" width="12" customWidth="1" style="3"/></cols><sheetData><row r="1">'
            .$this->text('A1', 'vorname', 1).$this->text('B1', 'nachname', 1).$this->text('C1', 'geburtsdatum', 1).$this->text('D1', 'email', 1).$this->text('E1', 'klasse', 1)
            .'</row></sheetData></worksheet>');
        $notes = [
            'So füllst du die Liste aus',
            'Ein Schüler oder eine Schülerin je Zeile, ab Zeile 2 auf dem Blatt „Klassenliste“. Die Kopfzeile bitte nicht ändern.',
            'vorname, nachname und geburtsdatum sind Pflicht; die E-Mail-Adresse ist freiwillig.',
            'Das Geburtsdatum als TT.MM.JJJJ eintragen, zum Beispiel 14.03.2014.',
            'Die Spalte klasse ist freiwillig: Die Klasse wird sonst in der Bibliothek beim Hochladen gewählt (eine Datei je Klasse). Steht in der Spalte die Klasse (zum Beispiel 5.1), kann die Bibliothek eine Datei mit mehreren Klassen auf einmal importieren.',
            'Die fertige Datei als .xlsx speichern und an die Bibliothek schicken.',
        ];
        $rows = '';

        foreach ($notes as $index => $note) {
            $rows .= '<row r="'.($index + 1).'">'.$this->text('A'.($index + 1), $note, $index === 0 ? 1 : 0).'</row>';
        }

        $zip->addFromString('xl/worksheets/sheet2.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="1" width="110" customWidth="1"/></cols><sheetData>'.$rows.'</sheetData></worksheet>');
        $zip->close();

        $contents = (string) file_get_contents($path);
        @unlink($path);

        return $contents;
    }

    private function text(string $reference, string $value, int $style): string
    {
        return '<c r="'.$reference.'" t="inlineStr" s="'.$style.'"><is><t>'.htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></is></c>';
    }
}
