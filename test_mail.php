<?php

include_once './includes/send_email.php';

function displayResult($result) {
    echo '<div style="margin: 20px; padding: 15px; border: 1px solid ' .
         ($result['success'] ? 'green' : 'red') . '; background-color: ' .
         ($result['success'] ? '#e8f5e9' : '#ffebee') . ';">';
    echo '<h3>' . ($result['success'] ? 'Success!' : 'Error!') . '</h3>';
    echo '<p>' . $result['message'] . '</p>';
    echo '</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testEmail = $_POST['test_email'] ?? '';

    if (empty($testEmail)) {
        $result = [
            'success' => false,
            'message' => 'Please enter a valid email address'
        ];
    } else {
        $userData = [
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => $testEmail,
            'massar' => 'M12345678'
        ];

        $emailContent = generateRegistrationEmail($userData);

        $result = sendEmail(
            $testEmail,
            $emailContent['subject'],
            $emailContent['body'],
            $emailContent['plainText']
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
        }
        h1 {
            color: #333;
        }
        form {
            margin: 20px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="email"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .note {
            background-color: #fff9c4;
            padding: 10px;
            border-left: 4px solid #ffd600;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PHPMailer Test</h1>

        <div class="note">
            <p><strong>Important:</strong> Before using this test, make sure you have:</p>
            <ol>
                <li>Updated the email credentials in <code>includes/email.php</code></li>
                <li>Set up an App Password in your Gmail account if you have 2FA enabled</li>
                <li>Made sure PHPMailer files are in the correct directory structure</li>
            </ol>
        </div>

        <?php if (isset($result)) displayResult($result); ?>

        <form method="post">
            <label for="test_email">Enter your email to receive a test message:</label>
            <input type="email" id="test_email" name="test_email" required
                   placeholder="your@email.com" value="<?php echo $_POST['test_email'] ?? ''; ?>">

            <button type="submit">Send Test Email</button>
        </form>

        <p>This test will send a sample registration confirmation email to the address you provide.</p>
    </div>
</body>
</html>