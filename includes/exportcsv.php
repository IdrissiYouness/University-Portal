<?php

require_once '../config/db.php';
require_once 'utils.php'; // here u find the function exportToCSV


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}




$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';


$pdo = getDbConnection();

//dynamically create headers
$subjectsQuery = "SELECT id, nom FROM matiere ORDER BY id";
$stmtSubjects = $pdo->query($subjectsQuery);
$subjects = $stmtSubjects->fetchAll(PDO::FETCH_ASSOC);

// Build the base query with joins to get city, academy names, and student data
$query = "SELECT e.id, e.email, e.cin, e.massar, e.nom, e.prenom,
          v.nom as ville_nom, a.nom as academie_nom,
          e.moyenne_nationale, e.moyenne_regionale,
          e.date_inscription, e.status";


foreach ($subjects as $subject) {
    $subjectId = $subject['id'];
    $query .= ", (SELECT n.valeur FROM note n WHERE n.etudiant_id = e.id AND n.matiere_id = $subjectId) AS note_" . $subjectId;
}

$query .= " FROM etudiant e
          LEFT JOIN ville v ON e.ville_id = v.id
          LEFT JOIN academie a ON e.academie_id = a.id";

$params = [];
$conditions = [];


if (!empty($search)) {
    $conditions[] = "(e.nom LIKE :search OR e.prenom LIKE :search OR e.email LIKE :search OR e.cin LIKE :search OR e.massar LIKE :search)";
    $params[':search'] = "%$search%";
}


if ($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected'])) {
    $conditions[] = "e.status = :status";
    $params[':status'] = $status;
}


if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}


$query .= " ORDER BY e.date_inscription DESC";


$headers = [
    'ID',
    'Email',
    'CIN',
    'Massar',
    'Nom',
    'Prénom',
    'Ville',
    'Académie'
];


foreach ($subjects as $subject) {
    $headers[] = 'Note ' . $subject['nom'];
}


$headers = array_merge($headers, [
    'Moyenne Nationale',
    'Moyenne Régionale',
    'Date Inscription',
    'Statut'
]);

// Define column mappings (database fields to CSV columns)
$mappings = [
    'id',
    'email',
    'cin',
    'massar',
    'nom',
    'prenom',
    'ville_nom',
    'academie_nom'
];


foreach ($subjects as $subject) {
    $mappings[] = 'note_' . $subject['id'];
}


$mappings = array_merge($mappings, [
    'moyenne_nationale',
    'moyenne_regionale',
    'date_inscription',
    function($row) {

        $statuses = [
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté'
        ];
        return $statuses[$row['status']] ?? $row['status'];
    }
]);


exportToCSV($pdo, $query, $params, $headers, $mappings, 'inscriptions_etudiants');
?>