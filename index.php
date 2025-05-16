<?php
    session_start();
    include_once 'config/db.php';
    $conn = getDbConnection();

    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {

        if ($_SESSION['role'] === 'admin') {
            header("Location: admin/");
            exit();
        } elseif ($_SESSION['role'] === 'etudiant') {
            header("Location: student/");
            exit();
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/main.css">
    <title>Portal</title>
</head>
<body>
    <main class="portal-container">
        <div class="container register-container">
            <h1>Welcome</h1>
            <h2>Register for student account here</h2>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Tempore
              repellendus id, itaque facilis, sint nobis placeat, consequuntur
              modi cum vero eius ipsa facere distinctio aspernatur beatae velit
              quos nemo optio.
            </p>
            <a  class="btn register-btn" href="./register.php">Register</a>
        </div>
        <div class="container login-container">
            <h1>Univ Portal</h1>
            <?php
                $error = '';
                if (isset($_SESSION['error'])) {
                    $error = $_SESSION['error'];
                    unset($_SESSION['error']);
                }
            ?>
            <form  class="login-form" method="post" action="login.php">
                <div class="input-container">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email">
                </div>
                <div class="input-container">
                    <label for="passwd">Password</label>
                    <input id="passwd" type="password"  name="password">
                </div>
                <?php if (!empty($error)):
                                echo "<span class='error' style='color: ; font-size:12px;'>* $error</span>";
                          endif; ?>
                <div class="input-container">
                    <button  class="btn login-btn" type="submit">Login</button>
                </div>
            </form>
        </div>
    </main>



</body>
</html>