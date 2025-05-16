
<?php

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
    'pending' => ['label' => 'En attente', 'color' => 'warning'],
    'approved' => ['label' => 'Approuvé', 'color' => 'success'],
    'rejected' => ['label' => 'Rejeté', 'color' => 'danger']
];
$status = $status_labels[$student['status']] ?? $status_labels['pending'];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Étudiant</title>
    <link rel="stylesheet" href="../assets/css/student.css">
</head>
<body>
    <div class="student-dashboard">
        <header class="dashboard-header">
            <div class="logo">
                <h1>Student Portal</h1>
            </div>
            <div class="user-info">
                <span>Bienvenue, <?php echo htmlspecialchars($student['prenom'] . ' ' . $student['nom']); ?></span>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </header>

        <div class="dashboard-container">
            <div class="status-banner status-<?php echo $status['color']; ?>">
                Statut de votre demande: <strong><?php echo $status['label']; ?></strong>
                <?php if ($student['status'] === 'pending'): ?>
                <p>Votre dossier est en cours d'examen. Vous recevrez une notification dès qu'une décision sera prise.</p>
                <?php elseif ($student['status'] === 'approved'): ?>
                <p>Félicitations ! Votre candidature a été acceptée. Vous recevrez bientôt plus d'informations concernant les prochaines étapes.</p>
                <?php elseif ($student['status'] === 'rejected'): ?>
                <p>Nous regrettons de vous informer que votre candidature n'a pas été retenue pour cette année.</p>
                <?php endif; ?>
            </div>

            <div class="dashboard-grid">
                <!-- Personal Information Card -->
                <div class="card">
                    <div class="card-header">
                        <h2>Informations personnelles</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <div class="info-label">Nom complet:</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['prenom'] . ' ' . $student['nom']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['email']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">CIN:</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['cin']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Code Massar:</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['massar']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Ville:</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['ville_nom']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Académie:</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['academie_nom']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Date d'inscription:</div>
                            <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($student['date_inscription'])); ?></div>
                        </div>
                        <div class="btn-group">
                            <a href="../includes/exportpdf.php" class="btn-download">Export PDF</a>
                        </div>
                    </div>
                </div>

                <!-- Grades Card -->
                <div class="card">
                    <div class="card-header">
                        <h2>Notes Examens</h2>
                    </div>
                    <div class="card-body">
                        <div class="grades-container">
                            <div class="grades-table">
                                <div class="table-header">
                                    <div class="table-cell">Matière</div>
                                    <div class="table-cell">Note</div>
                                </div>
                                <?php foreach ($grades as $grade): ?>
                                <div class="table-row">
                                    <div class="table-cell"><?php echo htmlspecialchars($grade['matiere_nom']); ?></div>
                                    <div class="table-cell">
                                        <span class="grade <?php echo ($grade['valeur'] >= 10) ? 'passing' : 'failing'; ?>">
                                            <?php echo htmlspecialchars($grade['valeur']); ?>/20
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Averages Card -->
                <div class="card">
                    <div class="card-header">
                        <h2>Moyennes du Baccalauréat</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <div class="info-label">Moyenne Nationale:</div>
                            <div class="info-value">
                                <span class="grade <?php echo ($student['moyenne_nationale'] >= 10) ? 'passing' : 'failing'; ?>">
                                    <?php echo htmlspecialchars($student['moyenne_nationale']); ?>/20
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Moyenne Régionale:</div>
                            <div class="info-value">
                                <span class="grade <?php echo ($student['moyenne_regionale'] >= 10) ? 'passing' : 'failing'; ?>">
                                    <?php echo htmlspecialchars($student['moyenne_regionale']); ?>/20
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documents Card -->
                <div class="card">
                    <div class="card-header">
                        <h2>Documents soumis</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($documents) > 0): ?>
                            <div class="docs-container">
                                <div class="docs-table">
                                    <div class="table-header">
                                        <div class="table-cell">Type</div>
                                        <div class="table-cell">Date</div>
                                        <div class="table-cell">Action</div>
                                    </div>
                                    <?php foreach ($documents as $doc): ?>
                                    <div class="table-row">
                                        <div class="table-cell"><?php echo htmlspecialchars($document_types[$doc['type']] ?? $doc['type']); ?></div>
                                        <div class="table-cell"><?php echo date('d/m/Y', strtotime($doc['uploaded_at'])); ?></div>
                                        <div class="table-cell">
                                            <a href="<?php echo htmlspecialchars($doc['fichier_path']); ?>" class="btn-view" target="_blank">Voir</a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="no-data">Aucun document n'a été soumis pour l'instant.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
