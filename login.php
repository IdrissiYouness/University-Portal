<?php

session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    //simple post retreive

    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Email or Password Required";
        header("Location: ./");
        exit();
    }

    $conn = getDbConnection();


    $stmt = $conn->prepare("SELECT id, email, password FROM admin WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && $password === $admin['password']) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['email'] = $admin['email'];
        $_SESSION['role'] = 'admin';

        // Optional: Update last login time
        /*
        $update = $conn->prepare("UPDATE admin SET last_login = NOW() WHERE id = :id");
        $update->execute(['id' => $admin['id']]);
        */

        header("Location: admin/");
        exit();
    }

    if ($admin && $password !== $admin['password']) {
        $_SESSION['error'] = "Email or Password Incorrect";
        header("Location: ./");
        exit();
    }

    // Check if the user is a student
    try {
        $stmt = $conn->prepare("SELECT id, nom, prenom, email, password FROM etudiant WHERE email = ?");
        $stmt->execute([$email]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student && password_verify($password, $student['password'])) {
            $_SESSION['user_id'] = $student['id'];
            $_SESSION['email'] = $student['email'];
            $_SESSION['nom'] = $student['nom'];
            $_SESSION['prenom'] = $student['prenom'];
            $_SESSION['role'] = 'etudiant';
            header("Location: ./student/");
            exit();
        } else {
            $_SESSION['error'] = "Email ou mot de passe incorrect.";
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de connexion à la base de données.";
        header("Location: login.php");
        exit();
    }

}

header("Location: login.php");
exit();
?>