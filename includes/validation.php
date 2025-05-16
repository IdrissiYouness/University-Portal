<?php

function validateRequired($value) {
    return !empty(trim($value));
}


function validateCIN($cin) {
    return preg_match('/^[A-Za-z]?[0-9]{6,}[A-Za-z]?$/', $cin);
}


function validateMassar($massar) {
    return preg_match('/^[A-Za-z][0-9]{9,10}$/', $massar);
}


function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


function validateGrade($grade) {
    if (!is_numeric($grade)) {
        return false;
    }

    $grade = (float) $grade;

    if ($grade < 0 || $grade > 20) {
        return false;
    }

    $decimalPart = $grade - floor($grade);
    $decimalPlaces = strlen(rtrim(substr($decimalPart, 2), '0'));

    return $decimalPlaces <= 1;
}


function cinExists($conn, $cin) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM etudiant WHERE cin = :cin");
    $stmt->execute([':cin' => $cin]);
    return (int) $stmt->fetchColumn() > 0;
}


function massarExists($conn, $massar) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM etudiant WHERE massar = :massar");
    $stmt->execute([':massar' => $massar]);
    return (int) $stmt->fetchColumn() > 0;
}


function emailExists($conn, $email) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM etudiant WHERE email = :email");
    $stmt->execute([':email' => $email]);
    return (int) $stmt->fetchColumn() > 0;
}


function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


function validateForm($formData, $conn) {
    $errors = [];

    $requiredFields = ['nom', 'prenom', 'cin', 'cne', 'email', 'ville-list', 'academie-list',
                      'math-grade', 'phy-grade', 'french-grade'];

    foreach ($requiredFields as $field) {
        if (!validateRequired($formData[$field] ?? '')) {
            $errors[$field] = "Ce champ est obligatoire";
        }
    }


    if (!empty($formData['cin']) && !validateCIN($formData['cin'])) {
        $errors['cin'] = "Format CIN invalide (minimum 6 chiffres + lettre)";
    }

    if (!empty($formData['cin']) && !isset($errors['cin']) && cinExists($conn, $formData['cin'])) {
        $errors['cin'] = "Ce CIN existe déjà dans notre système";
    }

    if (!empty($formData['cne']) && !validateMassar($formData['cne'])) {
        $errors['cne'] = "Format Massar invalide (Ex: E123456789)";
    }

    if (!empty($formData['cne']) && !isset($errors['cne']) && massarExists($conn, $formData['cne'])) {
        $errors['cne'] = "Ce code Massar existe déjà dans notre système";
    }

    if (!empty($formData['email']) && !validateEmail($formData['email'])) {
        $errors['email'] = "Format email invalide";
    }

    if (!empty($formData['email']) && !isset($errors['email']) && emailExists($conn, $formData['email'])) {
        $errors['email'] = "Cet email existe déjà dans notre système";
    }

    $gradeFields = ['math-grade', 'phy-grade', 'french-grade'];
    foreach ($gradeFields as $field) {
        if (!empty($formData[$field])) {
            if (!is_numeric($formData[$field])) {
                $errors[$field] = "La note doit être un nombre valide";
            } elseif (!validateGrade($formData[$field])) {
                $errors[$field] = "La note doit être entre 0 et 20 (max 1 décimale)";
            }
        }
    }

    return $errors;
}