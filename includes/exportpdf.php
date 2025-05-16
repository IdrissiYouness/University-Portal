<?php

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: ../");
    exit();
}

require_once '../config/db.php';

$student_id = $_SESSION['user_id'];

$pdo = getDbConnection();


$query = "SELECT e.*, v.nom as ville_nom, a.nom as academie_nom
          FROM etudiant e
          LEFT JOIN ville v ON e.ville_id = v.id
          LEFT JOIN academie a ON e.academie_id = a.id
          WHERE e.id = :student_id";

$stmt = $pdo->prepare($query);
$stmt->execute(['student_id' => $student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT n.valeur, m.nom as matiere_nom
          FROM note n
          JOIN matiere m ON n.matiere_id = m.id
          WHERE n.etudiant_id = :student_id
          ORDER BY m.nom";

$stmt = $pdo->prepare($query);
$stmt->execute(['student_id' => $student_id]);
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);


$total_grade = 0;
$grade_count = count($grades);
foreach ($grades as $grade) {
    $total_grade += $grade['valeur'];
}
$average_grade = $grade_count > 0 ? round($total_grade / $grade_count, 2) : 0;


$query = "SELECT type, fichier_path, uploaded_at
          FROM docs
          WHERE etudiant_id = :student_id";

$stmt = $pdo->prepare($query);
$stmt->execute(['student_id' => $student_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);


$document_types = [
    'bac' => 'Baccalauréat',
    'releve' => 'Relevé de notes',
    'cin' => 'Carte d\'identité nationale'
];


$status_labels = [
    'pending' => 'En attente',
    'approved' => 'Approuvé',
    'rejected' => 'Rejeté'
];
$status = $status_labels[$student['status']] ?? $status_labels['pending'];


require_once './dompdf/vendor/autoload.php';


use Dompdf\Dompdf;
use Dompdf\Options;


$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');


$options->set('debugKeepTemp', false);
$options->set('debugCss', false);

$dompdf = new Dompdf($options);


$status_colors = [
    'pending' => '#f39c12', // warning
    'approved' => '#2ecc71', // success
    'rejected' => '#e74c3c'  // danger
];
$status_color = $status_colors[$student['status']] ?? $status_colors['pending'];

// Generate HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Informations Étudiant</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            line-height: 1.6;
            color: #333;
            font-size: 12px;
            background-color: #f5f7fa;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #cc0c0c;
        }
        .header h1 {
            color: #cc0c0c;
            margin: 0;
            padding: 0;
            font-size: 22px;
        }
        h2 {
            color: white;
            background-color: #cc0c0c;
            font-size: 16px;
            margin-top: 20px;
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 4px 4px 0 0;
        }
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .section-content {
            padding: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        table, th, td {
            border: 1px solid #ecf0f1;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px solid #ecf0f1;
            padding-bottom: 8px;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .info-label {
            width: 40%;
            font-weight: 500;
            color: #34495e;
        }
        .info-value {
            width: 60%;
        }
        .passing {
            background-color: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }
        .failing {
            background-color: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #777;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            position: absolute;
            bottom: 0;
            width: 100%;
        }
        @page {
            margin: 2cm 1.5cm;
        }
        .page-break {
            page-break-before: always;
        }
        .status-info {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: ' . $status_color . ';
            color: white;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .status-info p {
            margin-top: 8px;
            opacity: 0.9;
            font-weight: normal;
        }
        .docs-table {
            width: 100%;
        }
        .docs-row {
            border-bottom: 1px solid #ecf0f1;
        }
        .docs-row:last-child {
            border-bottom: none;
        }
        .docs-cell {
            padding: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Student Portal - Dossier Étudiant</h1>
        </div>

        <div class="status-info">
            <strong>Statut de la demande: ' . htmlspecialchars($status) . '</strong>
            ' . ($student['status'] === 'pending' ?
                '<p>Votre dossier est en cours d\'examen. Vous recevrez une notification dès qu\'une décision sera prise.</p>' :
                ($student['status'] === 'approved' ?
                    '<p>Félicitations ! Votre candidature a été acceptée. Vous recevrez bientôt plus d\'informations concernant les prochaines étapes.</p>' :
                    '<p>Nous regrettons de vous informer que votre candidature n\'a pas été retenue pour cette année.</p>')) . '
        </div>

        <div class="section">
            <h2>Informations Personnelles</h2>
            <div class="section-content">
                <div class="info-row">
                    <div class="info-label">Nom complet:</div>
                    <div class="info-value">' . htmlspecialchars($student['prenom'] . ' ' . $student['nom']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value">' . htmlspecialchars($student['email']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">CIN:</div>
                    <div class="info-value">' . htmlspecialchars($student['cin']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Code Massar:</div>
                    <div class="info-value">' . htmlspecialchars($student['massar']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Ville:</div>
                    <div class="info-value">' . htmlspecialchars($student['ville_nom']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Académie:</div>
                    <div class="info-value">' . htmlspecialchars($student['academie_nom']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date d\'inscription:</div>
                    <div class="info-value">' . date('d/m/Y H:i', strtotime($student['date_inscription'])) . '</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Notes Examens</h2>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Matière</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>';

                    foreach ($grades as $grade) {
                        $class = ($grade['valeur'] >= 10) ? 'passing' : 'failing';
                        $html .= '
                        <tr>
                            <td>' . htmlspecialchars($grade['matiere_nom']) . '</td>
                            <td><span class="' . $class . '">' . htmlspecialchars($grade['valeur']) . '/20</span></td>
                        </tr>';
                    }

                    //$average_class = ($average_grade >= 10) ? 'passing' : 'failing';
                    $html .= '
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <h2>Moyennes du Baccalauréat</h2>
            <div class="section-content">
                <div class="info-row">
                    <div class="info-label">Moyenne Nationale:</div>
                    <div class="info-value">
                        <span class="' . (($student['moyenne_nationale'] >= 10) ? 'passing' : 'failing') . '">
                            ' . htmlspecialchars($student['moyenne_nationale']) . '/20
                        </span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Moyenne Régionale:</div>
                    <div class="info-value">
                        <span class="' . (($student['moyenne_regionale'] >= 10) ? 'passing' : 'failing') . '">
                            ' . htmlspecialchars($student['moyenne_regionale']) . '/20
                        </span>
                    </div>
                </div>
            </div>
        </div>';

        if (count($documents) > 0) {
            $html .= '
            <div class="section">
                <h2>Documents soumis</h2>
                <div class="section-content">
                    <div class="docs-table">';

            foreach ($documents as $doc) {
                $html .= '
                        <div class="docs-row">
                            <div class="docs-cell">' . htmlspecialchars($document_types[$doc['type']] ?? $doc['type']) . '</div>
                            <div class="docs-cell">' . date('d/m/Y', strtotime($doc['uploaded_at'])) . '</div>
                        </div>';
            }

            $html .= '
                    </div>
                </div>
            </div>';
        }

        $html .= '
        <div class="footer">
            Student Portal - Document généré le ' . date('d/m/Y') . ' à ' . date('H:i') . '.
        </div>
    </div>
</body>
</html>';


$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

ob_end_clean();

$filename = 'informations_etudiant_' . $student['id'] . '.pdf';
$dompdf->stream($filename, [
    'Attachment' => true, // true forces download, false opens in browser
    'compress' => 1
]);


exit(0);
?>