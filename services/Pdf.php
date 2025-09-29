<?php

namespace services;
require './vendor/tcpdf/tcpdf.php';

use TCPDF;

class Pdf
{
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

        if ($typesDepense === null) {
            $typeDepenseService = new TypeDepense();
            $typesDepense = $typeDepenseService->getTypesDepenses();
        }

        // Récupérer uniquement les types de dépenses utilisés dans les tickets
        $typesUtilises = [];
        foreach ($tickets as $ticket) {
            $typeTicket = $ticket['TypeDepense'] ?? 'Divers';
            if (!in_array($typeTicket, $typesUtilises)) {
                $typesUtilises[] = $typeTicket;
            }
        }

        // Filtrer et formater les types de dépenses utilisés
        $typesAffiches = [];
        foreach ($typesDepense as $type) {
            if (in_array($type['Libelle'], $typesUtilises)) {
                $libelle = $type['Libelle'];
                $typesAffiches[$type['Libelle']] = $libelle;
            }
        }

        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'NOTE DE FRAIS', 0, 1, 'C');
        $pdf->Ln(8);

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

        // Calcul dynamique des largeurs basé sur les types utilisés
        $pageWidth = $pdf->getPageWidth() - 20;
        $fixedColumnsWidth = 14 + 11 + 12 + 16 + 20; // Date, N°pièce, N°affaire, MontantTTC, Commentaires
        $tvaWidth = 10;
        $typeColumnsWidth = $pageWidth - $fixedColumnsWidth - $tvaWidth;
        $typeColumnWidth = count($typesAffiches) > 0 ? max(12, floor($typeColumnsWidth / count($typesAffiches))) : 12;

        $colonnes = [
            'Date',
            "N°\npièce",
            "N°\naffaire",
            "Montant\nTTC",
            "Commentaires"
        ];
        $largeurs = [15, 11, 12, 16, 20];

        // Ajouter uniquement les colonnes des types utilisés
        foreach ($typesAffiches as $typeOriginal => $typeAffiche) {
            $colonnes[] = $typeAffiche;
            $largeurs[] = $typeColumnWidth;
        }

        $colonnes[] = 'TVA';
        $largeurs[] = $tvaWidth;

        // Initialiser les totaux uniquement pour les types utilisés
        $typeTotals = array_fill_keys(array_keys($typesAffiches), 0);
        $totalTTC = 0;
        $totalTVA = 0;

        $drawHeader = function() use ($pdf, $colonnes, $largeurs) {
            $pdf->SetFillColor(70, 130, 180);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 7);

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $maxHeight = 0;
            $cellHeights = [];

            foreach ($colonnes as $i => $col) {
                $nbLines = ceil(strlen($col) / 10);
                $height = max(12, $nbLines * 5);
                $cellHeights[] = $height;
                $maxHeight = max($maxHeight, $height);
            }

            foreach ($colonnes as $i => $col) {
                $pdf->MultiCell($largeurs[$i], $maxHeight, $col, 1, 'C', true, 0, '', '', true, 0, false, true, $maxHeight, 'M');
            }
            $pdf->Ln();
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(255, 255, 255);
        };

        $drawHeader();
        $pdf->SetFont('helvetica', '', 7);

        $rowNum = 0;
        $pieceNumber = 1;

        foreach ($tickets as $ticket) {
            $fill = ($rowNum++ % 2 == 0);
            $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);

            $commentText = $ticket['Commentaires'] ?? '';
            $numAffaire = $ticket['NumeroAffaire'] ?? '';

            $commentLines = ceil(strlen($commentText) / 25);
            $affaireLines = ceil(strlen($numAffaire) / 10);
            $maxLines = max(1, $commentLines, $affaireLines);
            $rowHeight = $maxLines * 6 + 2;

            if ($pdf->GetY() + $rowHeight > $pdf->getPageHeight() - 25) {
                $pdf->AddPage();
                $drawHeader();
                $pdf->SetFont('helvetica', '', 7);
            }

            $startY = $pdf->GetY();
            $startX = $pdf->GetX();

            $pdf->MultiCell($largeurs[0], $rowHeight, strftime('%d-%m-%Y', strtotime($ticket['DateJustificatif'] ?? '')), 1, 'C', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell($largeurs[1], $rowHeight, $pieceNumber, 1, 'C', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell($largeurs[2], $rowHeight, $numAffaire, 1, 'C', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');

            $ttc = $ticket['TotalTTC'] ?? 0;
            $tva = $ticket['TotalTVA'] ?? 0;
            $ht = $ttc - $tva;
            $pdf->MultiCell($largeurs[3], $rowHeight, number_format($ttc, 2, ',', ' '), 1, 'R', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell($largeurs[4], $rowHeight, $commentText, 1, 'L', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');

            $currentType = $ticket['TypeDepense'] ?? 'Divers';
            $valueToShow = ($tva > 0) ? $ht : $ttc;

            // Parcourir uniquement les types utilisés
            $colIndex = 5; // Commence après les 5 premières colonnes fixes
            foreach ($typesAffiches as $typeOriginal => $typeAffiche) {
                if ($typeOriginal === $currentType) {
                    $pdf->MultiCell($largeurs[$colIndex], $rowHeight, number_format($valueToShow, 2, ',', ' '), 1, 'R', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                    $typeTotals[$typeOriginal] += $valueToShow;
                } else {
                    $pdf->MultiCell($largeurs[$colIndex], $rowHeight, '', 1, 'R', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                }
                $colIndex++;
            }

            $pdf->MultiCell($largeurs[count($largeurs) - 1], $rowHeight, number_format($tva, 2, ',', ' '), 1, 'R', $fill, 1, '', '', true, 0, false, true, $rowHeight, 'M');

            $totalTTC += $ttc;
            $totalTVA += $tva;

            $pieceNumber++;
        }

        if ($pdf->GetY() + 20 > $pdf->getPageHeight() - 25) {
            $pdf->AddPage();
            $drawHeader();
            $pdf->SetFont('helvetica', '', 7);
        }

        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(array_sum(array_slice($largeurs, 0, 3)), 8, 'TOTAL GÉNÉRAL', 1, 0, 'C', true);
        $pdf->Cell($largeurs[3], 8, number_format($totalTTC, 2, ',', ' '), 1, 0, 'R', true);
        $pdf->Cell($largeurs[4], 8, "", 1, 0, 'C', true);

        // Afficher les totaux uniquement pour les types utilisés
        $colIndex = 5;
        foreach ($typesAffiches as $typeOriginal => $typeAffiche) {
            $pdf->Cell($largeurs[$colIndex], 8, number_format($typeTotals[$typeOriginal], 2, ',', ' '), 1, 0, 'R', true);
            $colIndex++;
        }

        $pdf->Cell($largeurs[count($largeurs) - 1], 8, number_format($totalTVA, 2, ',', ' '), 1, 1, 'R', true);

        if ($pdf->GetY() + 40 > $pdf->getPageHeight() - 25) {
            $pdf->AddPage();
        }

        $pdf->Ln(15);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $largeur_sig_col1 = 140;
        $largeur_sig_col2 = 140;
        $pdf->Cell($largeur_sig_col1, 25, 'Date et signature certifié sincère, l\'intéressé', 1, 0, 'C', false, '', 0, false, 'T', 'T');
        $pdf->Cell($largeur_sig_col2, 25, 'Signature du responsable', 1, 1, 'C', false, '', 0, true, 'T', 'T');

        foreach ($tickets as $ticket) {
            $chemin = $ticket['CheminJustificatif'] ?? '';
            if ($chemin && preg_match('/\.jpg$/i', $chemin) && file_exists($chemin)) {
                $pdf->AddPage();
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 10, 'Justificatif pour le ticket du ' . ($ticket['DateJustificatif'] ?? ''), 0, 1, 'C');
                $pdf->Ln(5);

                $currentY = $pdf->GetY();
                $pageHeight = $pdf->getPageHeight();
                $bottomMargin = 15;
                $availableHeight = $pageHeight - $currentY - $bottomMargin;

                $pdf->Image($chemin, 30, $currentY, 230, $availableHeight, 'JPG');
            }
        }

        $pdf->Output('note_de_frais.pdf', 'D');
    }
}