<?php

namespace services;
require './vendor/tcpdf/tcpdf.php';

use TCPDF;

/**
 * Classe utilitaire pour la génération de notes de frais au format PDF.
 *
 * Utilise la bibliothèque TCPDF pour créer un document PDF contenant
 * les informations de la note de frais, les tickets associés, les totaux,
 * ainsi que les justificatifs en image.
 *
 * @package services
 */
class _Pdf
{
    /**
     * Génère et télécharge un PDF de note de frais.
     *
     * @param array $noteDeFrais Données de la note de frais (nom, etc.).
     * @param array $tickets Liste des tickets de frais à inclure.
     * @param string $matricule Matricule de l'utilisateur.
     * @param string $periode Période concernée par la note de frais.
     * @param array|null $typesDepense (Optionnel) Liste des types de dépenses.
     *
     * @return void
     */
    public static function creerPDF($noteDeFrais, $tickets, $matricule, $periode, $typesDepense = null)
    {
        ob_end_clean();
        $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Subterra SAS');
        $pdf->SetTitle('Note de Frais Subterra SAS');
        $pdf->SetSubject('Tableau de remboursement de frais');
        $pdf->SetKeywords('TCPDF, PDF, frais, remboursement, note de frais');

        $pdf->SetMargins(10, 15, 10);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Get expense types if not provided
        if ($typesDepense === null) {
            $typeDepenseService = new TypeDepense();
            $typesDepense = $typeDepenseService->getTypesDepenses();
        }

        // Extract type labels for columns and truncate if too long
        $types = array_map(function ($type) {
            $libelle = $type['Libelle'];
            // Truncate long labels and add abbreviations
            if (strlen($libelle) > 12) {
                $libelle = substr($libelle, 0, 10) . '..';
            }
            return $libelle;
        }, $typesDepense);

        $pdf->AddPage();

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'NOTE DE FRAIS', 0, 1, 'C');
        $pdf->Ln(8);

        // Info table
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(220, 220, 220);

        $largeur_col1 = 60;
        $largeur_col2 = 80;

        $pdf->Cell($largeur_col1, 8, 'Nom du demandeur :', 1, 0, 'L', true);
        $pdf->Cell($largeur_col2, 8, $noteDeFrais['LibelleUtilisateur'] ?? '', 1, 1, 'L', true);

        $pdf->Cell($largeur_col1, 8, 'Matricule :', 1, 0, 'L', true);
        $pdf->Cell($largeur_col2, 8, $matricule ?? '', 1, 1, 'L', true);

        $pdf->Cell($largeur_col1, 8, 'Période due :', 1, 0, 'L', true);
        $pdf->Cell($largeur_col2, 8, $periode ?? '', 1, 1, 'L', true);

        $pdf->Ln(10);

        // Calculate available width and dynamic column sizes
        $pageWidth = $pdf->getPageWidth() - 20; // 10pt margin on each side
        $fixedColumnsWidth = 14 + 11 + 12 + 16 + 20; // Date + N°piece + N°affaire + Montant + Comments
        $tvaWidth = 10;
        $typeColumnsWidth = $pageWidth - $fixedColumnsWidth - $tvaWidth;
        $typeColumnWidth = count($types) > 0 ? max(12, floor($typeColumnsWidth / count($types))) : 12;

        // Define standard columns
        $colonnes = [
            'Date',
            "N°\npièce",
            "N°\naffaire",
            "Montant\nTTC",
            "Commentaires"
        ];
        $largeurs = [14, 11, 12, 16, 20];

        // Add dynamic type columns
        foreach ($types as $type) {
            $colonnes[] = $type;
            $largeurs[] = $typeColumnWidth;
        }

        // Add TVA column
        $colonnes[] = 'TVA';
        $largeurs[] = $tvaWidth;

        // Prepare totals
        $typeTotals = array_fill_keys($types, 0);
        $totalTTC = 0;
        $totalTVA = 0;

        // Draw header using MultiCell for automatic line breaks
        $pdf->SetFillColor(70, 130, 180);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 7);

        // Calculate header height needed
        $headerHeight = 12; // Fixed height for header

        // Save current position
        $currentX = $pdf->GetX();
        $currentY = $pdf->GetY();

        // Draw header cells
        foreach ($colonnes as $i => $col) {
            // Position for each cell
            $cellX = $currentX + array_sum(array_slice($largeurs, 0, $i));
            $pdf->SetXY($cellX, $currentY);

            // Use MultiCell for automatic text wrapping
            $pdf->MultiCell($largeurs[$i], $headerHeight, $col, 1, 'C', true, 0, '', '', true, 0, false, true, $headerHeight, 'M');
        }

        // Move to next line
        $pdf->Ln($headerHeight);
        $pdf->SetTextColor(0, 0, 0);

        // Draw data rows
        $pdf->SetFont('helvetica', '', 7);
        $rowNum = 0;
        $pieceNumber = 1;

        foreach ($tickets as $ticket) {
            $fill = ($rowNum++ % 2 == 0);
            $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);

            // Calculate row height needed for comments
            $commentText = $ticket['Commentaires'] ?? '';
            $rowHeight = 8;

            // If comment is long, increase row height
            if (strlen($commentText) > 20) {
                $rowHeight = 12;
            }

            // Save position for row
            $rowX = $pdf->GetX();
            $rowY = $pdf->GetY();

            // Date
            $pdf->SetXY($rowX, $rowY);
            $pdf->MultiCell($largeurs[0], $rowHeight, $ticket['DateJustificatif'] ?? '', 1, 'C', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');

            // N° pièce
            $pdf->SetXY($rowX + $largeurs[0], $rowY);
            $pdf->MultiCell($largeurs[1], $rowHeight, $pieceNumber, 1, 'C', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');

            // N° affaire
            $pdf->SetXY($rowX + $largeurs[0] + $largeurs[1], $rowY);
            $pdf->MultiCell($largeurs[2], $rowHeight, $ticket['NumeroAffaire'] ?? '', 1, 'C', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');

            // Montant TTC
            $ttc = $ticket['TotalTTC'] ?? 0;
            $tva = $ticket['TotalTVA'] ?? 0;
            $ht = $ttc - $tva;
            $pdf->SetXY($rowX + array_sum(array_slice($largeurs, 0, 3)), $rowY);
            $pdf->MultiCell($largeurs[3], $rowHeight, number_format($ttc, 2, ',', ' '), 1, 'R', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');

            // Comments with text wrapping
            $pdf->SetXY($rowX + array_sum(array_slice($largeurs, 0, 4)), $rowY);
            $pdf->MultiCell($largeurs[4], $rowHeight, $commentText, 1, 'L', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');

            // Display value in appropriate expense type column
            $currentType = $ticket['TypeDepense'] ?? 'Divers';
            $valueToShow = ($tva > 0) ? $ht : $ttc;
            $originalTypes = array_map(function ($type) {
                return $type['Libelle'];
            }, $typesDepense);

            foreach ($types as $idx => $type) {
                $pdf->SetXY($rowX + array_sum(array_slice($largeurs, 0, 5 + $idx)), $rowY);
                $originalType = $originalTypes[$idx] ?? '';

                if ($originalType === $currentType) {
                    $pdf->MultiCell($largeurs[5 + $idx], $rowHeight, number_format($valueToShow, 2, ',', ' '), 1, 'R', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                    $typeTotals[$type] += $valueToShow;
                } else {
                    $pdf->MultiCell($largeurs[5 + $idx], $rowHeight, '', 1, 'R', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                }
            }

            // TVA column
            $pdf->SetXY($rowX + array_sum(array_slice($largeurs, 0, count($largeurs) - 1)), $rowY);
            $pdf->MultiCell($largeurs[count($largeurs) - 1], $rowHeight, number_format($tva, 2, ',', ' '), 1, 'R', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');

            // Move to next row
            $pdf->SetXY($rowX, $rowY + $rowHeight);

            // Accumulate totals
            $totalTTC += $ttc;
            $totalTVA += $tva;

            $pieceNumber++;
        }

        // Total row
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(array_sum(array_slice($largeurs, 0, 3)), 8, 'TOTAL GÉNÉRAL', 1, 0, 'C', true);
        $pdf->Cell($largeurs[3], 8, number_format($totalTTC, 2, ',', ' '), 1, 0, 'R', true);
        $pdf->Cell($largeurs[4], 8, "", 1, 0, 'C', true);

        // Totals for each type
        foreach ($types as $i => $type) {
            $pdf->Cell($largeurs[5 + $i], 8, number_format($typeTotals[$type], 2, ',', ' '), 1, 0, 'R', true);
        }

        // TVA total
        $pdf->Cell($largeurs[count($largeurs) - 1], 8, number_format($totalTVA, 2, ',', ' '), 1, 0, 'R', true);

        // Signature table
        $pdf->Ln(15);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $largeur_sig_col1 = 140;
        $largeur_sig_col2 = 140;
        $pdf->Cell($largeur_sig_col1, 12, 'Date et signature certifié sincère, l\'intéressé', 1, 0, 'C', true);
        $pdf->Cell($largeur_sig_col2, 12, 'Signature du responsable', 1, 1, 'C', true);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($largeur_sig_col1, 25, '', 1, 0, 'C', false);
        $pdf->Cell($largeur_sig_col2, 25, '', 1, 1, 'C', false);

        // Add justification images
        foreach ($tickets as $ticket) {
            $chemin = $ticket['CheminJustificatif'] ?? '';
            if ($chemin && preg_match('/\.jpg$/i', $chemin) && file_exists($chemin)) {
                $pdf->AddPage();
                // Add title
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 10, 'Justificatif pour le ticket du ' . ($ticket['DateJustificatif'] ?? ''), 0, 1, 'C');
                $pdf->Ln(5); // Add some space after title

                // Get current Y position after the title
                $currentY = $pdf->GetY();

                // Calculate available height for image (page height - margins - current position)
                $pageHeight = $pdf->getPageHeight();
                $bottomMargin = 15; // Same as auto page break margin
                $availableHeight = $pageHeight - $currentY - $bottomMargin;

                // Insert the image starting from current position with reduced size
                $pdf->Image($chemin, 30, $currentY, 230, $availableHeight, 'JPG');
            }
        }

        $pdf->Output('note_de_frais.pdf', 'D');
    }
}