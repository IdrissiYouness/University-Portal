<?php
include_once 'config/db.php';
include_once './includes/validation.php';
include_once './includes/send_email.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$conn = getDbConnection();


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $formData = [];
    foreach ($_POST as $key => $value) {
        $formData[$key] = sanitizeInput($value);
    }

    $errors = validateForm($formData, $conn);

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $formData;
        header('Location: register.php');
        exit;
    }
} elseif (isset($_SESSION['form_data'])) {

    $formData = $_SESSION['form_data'];
    $files = $_SESSION['files'] ?? $_FILES;
    unset($_SESSION['form_data']);
    unset($_SESSION['files']);
} else {
    header('Location: register.php');
    exit;
}

$nom = $formData['nom'] ?? '';
$prenom = $formData['prenom'] ?? '';
$cin = $formData['cin'] ?? '';
$massar = $formData['cne'] ?? '';
$email = $formData['email'] ?? '';
$ville_id = $formData['ville-list'] ?? null;
$academie_id = $formData['academie-list'] ?? null;
$mathGrade = floatval($formData['math-grade'] ?? 0);
$phyGrade = floatval($formData['phy-grade'] ?? 0);
$frenchGrade = floatval($formData['french-grade'] ?? 0);

$moyenneNationale = ($mathGrade + $phyGrade) / 2;
$moyenneRegionale = $frenchGrade;

$hashedPassword = password_hash($massar, PASSWORD_DEFAULT);

try {
    $conn->beginTransaction();

    $checkStmt = $conn->prepare("SELECT id FROM etudiant WHERE email = :email OR cin = :cin OR massar = :massar LIMIT 1");
    $checkStmt->execute([
        ':email' => $email,
        ':cin' => $cin,
        ':massar' => $massar
    ]);

    if ($checkStmt->rowCount() > 0) {
        throw new Exception("L'email, le CIN ou le code Massar existe déjà dans notre système.");
    }

    $stmt = $conn->prepare("
        INSERT INTO etudiant (email, password, cin, massar, nom, prenom, ville_id, academie_id, moyenne_nationale, moyenne_regionale)
        VALUES (:email, :password, :cin, :massar, :nom, :prenom, :ville_id, :academie_id, :moyenne_nationale, :moyenne_regionale)
    ");

    $stmt->execute([
        ':email' => $email,
        ':password' => $hashedPassword,
        ':cin' => $cin,
        ':massar' => $massar,
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':ville_id' => $ville_id,
        ':academie_id' => $academie_id,
        ':moyenne_nationale' => $moyenneNationale,
        ':moyenne_regionale' => $moyenneRegionale
    ]);

    $etudiantId = $conn->lastInsertId();

    $notes = [
        1 => $mathGrade,
        2 => $phyGrade,
        3 => $frenchGrade,
    ];

    $stmtNote = $conn->prepare("INSERT INTO note (etudiant_id, matiere_id, valeur) VALUES (:etudiant_id, :matiere_id, :valeur)");

    foreach ($notes as $matiere_id => $valeur) {
        $stmtNote->execute([
            ':etudiant_id' => $etudiantId,
            ':matiere_id' => $matiere_id,
            ':valeur' => $valeur,
        ]);
    }

    $fileDefinitions = [
        'bac' => $files['bac'] ?? null,
        'cin-file' => $files['cin-file'] ?? null,
        'releve' => $files['releve'] ?? null,
    ];

    $studentFolderName = $prenom . '_' . $nom . '_docs';
    $studentFolderPath = 'uploads/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $studentFolderName);

    if (!is_dir($studentFolderPath)) {
        mkdir($studentFolderPath, 0775, true);
    }

    $stmtDoc = $conn->prepare("INSERT INTO docs (etudiant_id, type, fichier_path) VALUES (:etudiant_id, :type, :path)");

    foreach ($fileDefinitions as $type => $file) {
        if ($file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK && isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            $filename = basename($file['name']);
            $uniqueFilename = uniqid($type . '_') . '_' . $filename;
            $filepath = $studentFolderPath . '/' . $uniqueFilename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $docType = ($type === 'cin-file') ? 'cin' : $type; // Map 'cin-file' to 'cin' for DB
                $stmtDoc->execute([
                    ':etudiant_id' => $etudiantId,
                    ':type' => $docType,
                    ':path' => $filepath,
                ]);
            } else {
                error_log("Failed to move uploaded file for $type: " . print_r(error_get_last(), true));
            }
        } else if ($file && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            error_log("File upload error for $type: " . $file['error']);
        }
    }

    $conn->commit();

    $userData = [
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $email,
        'massar' => $massar
    ];

    $emailContent = generateRegistrationEmail($userData);

    $emailResult = sendEmail(
        $email,
        $emailContent['subject'],
        $emailContent['body'],
        $emailContent['plainText']
    );

    if ($emailResult['success']) {
        error_log("Registration confirmation email sent successfully to $email");
    } else {
        error_log("Failed to send registration confirmation email to $email: " . $emailResult['message']);
    }

    $_SESSION['user_id'] = $etudiantId;
    $_SESSION['email'] = $email;
    $_SESSION['nom'] = $nom;
    $_SESSION['prenom'] = $prenom;
    $_SESSION['role'] = 'etudiant';

    $_SESSION['success'] = "Inscription réussie ! Bienvenue sur le portail étudiant.";

    header('Location: ./student/');
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Registration error: " . $e->getMessage());

    $_SESSION['errors'] = ['general' => $e->getMessage()];
    $_SESSION['form_data'] = $formData;
    header('Location: register.php');
    exit;
}
?>