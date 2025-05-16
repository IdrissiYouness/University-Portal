<?php

// Function to export data to CSV
function exportToCSV($pdo, $query, $params = [], $headers = [], $mappings = [], $filename = 'export', $debug = false) {
    try {
        $filename = $filename . '_' . date('Y-m-d') . '.csv';

        if ($debug) {
            echo "<h2>Debug Mode</h2>";
            echo "<p><strong>Query:</strong> " . $query . "</p>";
            echo "<p><strong>Parameters:</strong></p>";
            echo "<pre>" . print_r($params, true) . "</pre>";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<p><strong>Row Count:</strong> " . count($results) . "</p>";
            echo "<p><strong>Sample Data (first 5 rows):</strong></p>";
            echo "<pre>" . print_r(array_slice($results, 0, 5), true) . "</pre>";
            exit;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        $rowCount = $stmt->rowCount();

        if ($rowCount == 0) {
            header('Location: index.php?error=No records found matching your criteria');
            exit;
        }


        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');


        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));


        if (!empty($headers)) {
            fputcsv($output, $headers);
        }


        $stmt->execute($params);


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($mappings)) {
                $csvRow = [];
                foreach ($mappings as $mapping) {
                    if (is_string($mapping) && isset($row[$mapping])) {
                        $csvRow[] = $row[$mapping];
                    } elseif (is_callable($mapping)) {
                        $csvRow[] = $mapping($row);
                    } else {
                        $csvRow[] = '';
                    }
                }
                fputcsv($output, $csvRow);
            } else {
                fputcsv($output, $row);
            }
        }

        fclose($output);
        exit;

    } catch (Exception $e) {
        error_log('CSV Export Error: ' . $e->getMessage());

        // Redirect back with error message
        //header('Location: index.php?error=Export failed: ' . urlencode($e->getMessage()));
        exit;
    }
}

// Function to generate the registration email
function generateRegistrationEmail($userData) {
    $subject = "Confirmation d'inscription - FSO";
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .header {
                background-color: #cc0c0c;
                color: white;
                padding: 10px 20px;
                text-align: center;
                border-radius: 5px 5px 0 0;
            }
            .content {
                padding: 20px;
            }
            .footer {
                background-color: #f4f4f4;
                padding: 10px 20px;
                text-align: center;
                font-size: 12px;
                border-radius: 0 0 5px 5px;
            }
            .info {
                background-color: #f9f9f9;
                padding: 15px;
                margin: 15px 0;
                border-left: 4px solid #cc0c0c;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Confirmation d'Inscription</h2>
            </div>
            <div class='content'>
                <p>Bonjour <strong>{$userData['prenom']} {$userData['nom']}</strong>,</p>

                <p>Nous sommes ravis de vous confirmer que votre inscription a été traitée avec succès. Votre compte étudiant est maintenant activé.</p>

                <div class='info'>
                    <p><strong>Informations de votre compte:</strong></p>
                    <ul>
                        <li>Nom: {$userData['prenom']} {$userData['nom']}</li>
                        <li>Email: {$userData['email']}</li>
                        <li>Code Massar: {$userData['massar']}</li>
                    </ul>
                    <p><strong>Pour vous connecter:</strong> Utilisez votre email et votre code Massar comme mot de passe (à votre première connexion).</p>
                </div>

                <p>Nous vous recommandons de changer votre mot de passe dès votre première connexion.</p>

                <p>Pour accéder à votre espace étudiant, veuillez vous rendre sur notre portail: <a href='localhost/registration-system/'>Espace Étudiant</a></p>

                <p>Si vous avez des questions, n'hésitez pas à contacter notre service d'assistance.</p>

                <p>Cordialement,<br>
                L'équipe administrative</p>
            </div>
            <div class='footer'>
                <p>Ce message est généré automatiquement, merci de ne pas y répondre.</p>
                <p>&copy; " . date('Y') . " - FSO</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $plainText = "
    Confirmation d'Inscription

    Bonjour {$userData['prenom']} {$userData['nom']},

    Nous sommes ravis de vous confirmer que votre inscription a été traitée avec succès. Votre compte étudiant est maintenant activé.

    Informations de votre compte:
    - Nom: {$userData['prenom']} {$userData['nom']}
    - Email: {$userData['email']}
    - Code Massar: {$userData['massar']}

    Pour vous connecter: Utilisez votre email et votre code Massar comme mot de passe (à votre première connexion).

    Nous vous recommandons de changer votre mot de passe dès votre première connexion.

    Pour accéder à votre espace étudiant, veuillez vous rendre sur notre portail: https://localhost/registration-system/

    Si vous avez des questions, n'hésitez pas à contacter notre service d'assistance.

    Cordialement,
    L'équipe administrative

    Ce message est généré automatiquement, merci de ne pas y répondre.
    © " . date('Y') . " - FSO
    ";

    return [
        'subject' => $subject,
        'body' => $body,
        'plainText' => $plainText
    ];
}