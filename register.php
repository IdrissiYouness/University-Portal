<?php
    include_once 'config/db.php';
    include_once './includes/validation.php';


    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $conn = getDbConnection();
    $villes = $conn->query("SELECT id, nom FROM ville ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
    $academies = $conn->query("SELECT id, nom, ville_id FROM academie ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);


    $errors = [];
    $formData = [];
    $formSubmitted = false;


    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $formSubmitted = true;


        foreach ($_POST as $key => $value) {
            $formData[$key] = sanitizeInput($value);
        }


        $errors = validateForm($formData, $conn);


        $requiredFiles = ['bac', 'cin-file', 'releve'];
        foreach ($requiredFiles as $fileField) {
            if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) {
                $errors[$fileField] = "Fichier requis";
            } elseif ($_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) {
                $errors[$fileField] = "Erreur de téléchargement: " . $_FILES[$fileField]['error'];
            }
        }


        if (empty($errors)) {

            $_SESSION['form_data'] = $formData;


            if (!empty($_FILES)) {
                $_SESSION['files'] = $_FILES;
            }


            header('Location: process-register.php');
            exit;
        }
    } else {

        if (isset($_SESSION['errors'])) {
            $errors = $_SESSION['errors'];
            unset($_SESSION['errors']);
            $formSubmitted = true;
        }

        if (isset($_SESSION['form_data'])) {
            $formData = $_SESSION['form_data'];
            unset($_SESSION['form_data']);
        }
    }


    $generalError = $errors['general'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/register.css">
    <title>Registration</title>
</head>
<body>
        <?php if (!empty($generalError)): ?>
            <div class="alert alert-danger"><?php echo $generalError; ?></div>
        <?php endif; ?>

        <form class="register-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
            <div class="upper-form-grid">
                <div class="academic-details-section">
                    <h3>Academic Details</h3>
                    <div id="names">
                        <div class="input-container">
                            <label for="nom">Nom <span class="star-span">*</span></label>
                            <input type="text" name="nom" value="<?php echo $formData['nom'] ?? ''; ?>">
                            <?php if ($formSubmitted && isset($errors['nom'])): ?>
                                <span class="error-message"><?php echo $errors['nom']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="input-container">
                            <label for="prenom">Prenom <span class="star-span">*</span></label>
                            <input type="text" name="prenom" value="<?php echo $formData['prenom'] ?? ''; ?>">
                            <?php if ($formSubmitted && isset($errors['prenom'])): ?>
                                <span class="error-message"><?php echo $errors['prenom']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="input-container">
                            <label for="cin">CIN <span class="star-span">*</span></label>
                            <input type="text" name="cin" value="<?php echo $formData['cin'] ?? ''; ?>">
                            <?php if ($formSubmitted && isset($errors['cin'])): ?>
                                <span class="error-message"><?php echo $errors['cin']; ?></span>
                            <?php endif; ?>
                    </div>
                    <div class="input-container">
                            <label for="cne">CNE <span class="star-span">*</span></label>
                            <input type="text" name="cne" value="<?php echo $formData['cne'] ?? ''; ?>">
                            <?php if ($formSubmitted && isset($errors['cne'])): ?>
                                <span class="error-message"><?php echo $errors['cne']; ?></span>
                            <?php endif; ?>
                    </div>
                    <div class="input-container">
                            <label for="email">Email <span class="star-span">*</span></label>
                            <input type="text" name="email" value="<?php echo $formData['email'] ?? ''; ?>">
                            <?php if ($formSubmitted && isset($errors['email'])): ?>
                                <span class="error-message"><?php echo $errors['email']; ?></span>
                            <?php endif; ?>
                    </div>
                    <div class="input-container">
                            <label for="ville">Ville <span class="star-span">*</span></label>
                            <select name="ville-list" class="select-list" >
                                <option value="">--Ville--</option>
                                <?php foreach ($villes as $ville): ?>
                                    <option value="<?= $ville['id'] ?>" <?php echo (isset($formData['ville-list']) && $formData['ville-list'] == $ville['id']) ? 'selected' : ''; ?>><?= htmlspecialchars($ville['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($formSubmitted && isset($errors['ville-list'])): ?>
                                <span class="error-message"><?php echo $errors['ville-list']; ?></span>
                            <?php endif; ?>
                    </div>
                    <div class="input-container">
                            <label for="ville">Academie<span class="star-span">*</span></label>
                            <select name="academie-list" class="select-list" >
                                <option value="">--Academie--</option>
                                <?php foreach ($academies as $a): ?>
                                    <option value="<?= $a['id'] ?>" data-ville="<?= $a['ville_id'] ?>" <?php echo (isset($formData['academie-list']) && $formData['academie-list'] == $a['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($a['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($formSubmitted && isset($errors['academie-list'])): ?>
                                <span class="error-message"><?php echo $errors['academie-list']; ?></span>
                            <?php endif; ?>
                    </div>
                </div>

                <div class="grade-section">
                    <h3>Notes</h3>
                    <h5>National</h5>
                    <div>
                        <div class="input-container">
                                <label for="math-grade">Math <span class="star-span">*</span></label>
                                <input type="text" name="math-grade" value="<?php echo $formData['math-grade'] ?? ''; ?>" >
                                <?php if ($formSubmitted && isset($errors['math-grade'])): ?>
                                    <span class="error-message"><?php echo $errors['math-grade']; ?></span>
                                <?php endif; ?>
                        </div>
                        <div class="input-container">
                                <label for="phy-grade">Physique <span class="star-span">*</span></label>
                                <input type="text" name="phy-grade" value="<?php echo $formData['phy-grade'] ?? ''; ?>" >
                                <?php if ($formSubmitted && isset($errors['phy-grade'])): ?>
                                    <span class="error-message"><?php echo $errors['phy-grade']; ?></span>
                                <?php endif; ?>
                        </div>
                    </div>
                    <h5>Regional</h5>
                    <div>
                        <div class="input-container">
                                <label for="french-grade">Francais <span class="star-span">*</span></label>
                                <input type="text" name="french-grade" value="<?php echo $formData['french-grade'] ?? ''; ?>">
                                <?php if ($formSubmitted && isset($errors['french-grade'])): ?>
                                    <span class="error-message"><?php echo $errors['french-grade']; ?></span>
                                <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="docs-section">
                <h3>Documents</h3>
                <div class="files-container">
                    <div class="file-input-group input-container">
                        <label>BAC <span class="star-span">*</span></label>
                        <label id="label-bac" class="custom-file-upload" for="file-bac">Choose BAC File</label>
                        <input type="file" name="bac" id="file-bac" onchange="updateLabel('file-bac', 'label-bac')" >
                        <?php if ($formSubmitted && isset($errors['bac'])): ?>
                            <span class="error-message"><?php echo $errors['bac']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="file-input-group input-container">
                        <label>CIN <span class="star-span">*</span></label>
                        <label id="label-cin" class="custom-file-upload" for="file-cin">Choose CIN File</label>
                        <input type="file" name="cin-file" id="file-cin" onchange="updateLabel('file-cin', 'label-cin')" >
                        <?php if ($formSubmitted && isset($errors['cin-file'])): ?>
                            <span class="error-message"><?php echo $errors['cin-file']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="file-input-group input-container">
                        <label>Releve <span class="star-span">*</span></label>
                        <label id="label-releve" class="custom-file-upload" for="file-releve">Choose Relevé File</label>
                        <input type="file" name="releve" id="file-releve" onchange="updateLabel('file-releve', 'label-releve')" >
                        <?php if ($formSubmitted && isset($errors['releve'])): ?>
                            <span class="error-message"><?php echo $errors['releve']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="actions-section">
                <button type="submit" class="btn">Submit</button>
                <a href="./">Cancel</a>
            </div>

        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const villeSelect = document.querySelector('select[name="ville-list"]');
                const academieSelect = document.querySelector('select[name="academie-list"]');

                villeSelect.addEventListener('change', function() {
                    const selectedVilleId = this.value;


                    academieSelect.value = '';


                    Array.from(academieSelect.options).forEach(option => {
                        if (option.value === '') return;

                        const villeId = option.getAttribute('data-ville');
                        if (selectedVilleId === '' || villeId === selectedVilleId) {
                            option.style.display = '';
                        } else {
                            option.style.display = 'none';
                        }
                    });
                });


                const event = new Event('change');
                villeSelect.dispatchEvent(event);
            });

            function updateLabel(fileInputId, labelId) {
                const input = document.getElementById(fileInputId);
                const label = document.getElementById(labelId);
                if (input.files.length > 0) {
                    label.textContent = input.files[0].name;
                } else {
                    label.textContent = "Choose File";
                }
            }
        </script>
</body>
</html>