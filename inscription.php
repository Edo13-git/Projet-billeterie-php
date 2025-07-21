<?php
// inscription.php ou inscription_client.php
session_start(); // Ajoutez session_start() si vous en avez besoin pour d'autres fonctionnalités, sinon vous pouvez l'omettre ici

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $dateNaissance = trim($_POST['date_naissance'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $confirmerMotDePasse = $_POST['confirmer_mot_de_passe'] ?? '';

    // Chemin où les photos seront stockées
    $uploadDir = 'uploads/'; // Assurez-vous que ce dossier existe et est inscriptible !
    $photoFileName = ''; // Initialise le nom du fichier photo

    // 1. Vérification des champs obligatoires
    if (empty($nom) || empty($prenom) || empty($dateNaissance) || empty($telephone) || empty($email) || empty($motDePasse) || empty($confirmerMotDePasse)) {
        $message = "❌ Veuillez remplir tous les champs obligatoires.";
    } elseif ($motDePasse !== $confirmerMotDePasse) {
        $message = "❌ Les mots de passe ne correspondent pas.";
    } else {
        // 2. Traitement de l'upload de photo
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['photo']['tmp_name'];
            $fileName = $_FILES['photo']['name'];
            $fileSize = $_FILES['photo']['size'];
            $fileType = $_FILES['photo']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = ['jpg', 'gif', 'png', 'jpeg'];

            if (in_array($fileExtension, $allowedfileExtensions)) {
                // Générer un nom de fichier unique pour éviter les collisions
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $photoFileName = $destPath; // Chemin relatif de la photo à stocker dans la DB
                } else {
                    $message = "❌ Erreur lors du déplacement du fichier photo.";
                }
            } else {
                $message = "❌ Type de fichier photo non autorisé. Seuls JPG, JPEG, PNG, GIF sont acceptés.";
            }
        } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Gérer les autres erreurs d'upload (taille max dépassée, etc.)
            $message = "❌ Erreur lors du téléchargement de la photo (code: " . $_FILES['photo']['error'] . ").";
        }
        // Si aucune photo n'est uploadée, $photoFileName reste vide, ce qui est correct si le champ est facultatif.

        // Si aucun message d'erreur n'a été généré par l'upload ou la validation de mot de passe
        if (empty($message)) {
            // Connexion avec MySQLi (Gardée telle quelle, mais PDO est recommandé)
            $conn = new mysqli('localhost', 'root', '', 'gestiondebillet');

            if ($conn->connect_error) {
                die("Erreur de connexion : " . $conn->connect_error);
            }

            $conn->set_charset("utf8mb4");

            // Hachage du mot de passe
            $motDePasseHashed = password_hash($motDePasse, PASSWORD_DEFAULT);

            // Préparation de la requête
            $stmt = $conn->prepare("INSERT INTO utilisateur (Nom, Prenom, DateNaissance, Photo, Telephone, Email, MotDePasse, Type_utilisateur) VALUES (?, ?, ?, ?, ?, ?, ?, 'client')");

            // 'sssssss' signifie 7 chaînes de caractères pour les 7 ?
            // L'ordre des paramètres dans bind_param doit correspondre à l'ordre des ? dans la requête SQL
            $stmt->bind_param("sssssss", $nom, $prenom, $dateNaissance, $photoFileName, $telephone, $email, $motDePasseHashed); 

            if ($stmt->execute()) {
                $message = "✅ Inscription réussie ! Vous pouvez vous connecter.";
                // Optionnel : Redirection vers la page de connexion après l'inscription
                // header('Location: connexion.php?inscription=success');
                // exit();
            } else {
                $message = "❌ Erreur lors de l’inscription : " . $stmt->error;
            }

            $stmt->close();
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Client</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script> <!-- Added Lucide for icons -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
            background-image: url('https://placehold.co/1920x1080/E0F2FE/3B82F6?text=Background');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: 0;
        }

        .signup-container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 380px;
            box-sizing: border-box;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            text-align: center;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.8em;
            font-weight: 700;
            letter-spacing: 1px;
            border-bottom: 2px solid #e0e5e9;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 12px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 0.95em;
        }

        input[type="text"],
        input[type="date"],
        input[type="tel"],
        input[type="email"],
        input[type="password"] {
            width: calc(100% - 16px);
            padding: 8px;
            border: 1px solid #c9d2da;
            border-radius: 8px;
            font-size: 0.9em;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background-color: #fcfcfc;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        input[type="tel"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
            outline: none;
        }

        /* Profile image upload styles */
        .profile-image-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px; /* Adjusted margin */
        }

        .profile-image-circle {
            width: 90px; /* Smaller circle */
            height: 90px; /* Smaller circle */
            border-radius: 50%;
            background-color: #e0e5e9;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            overflow: hidden;
            border: 3px solid #007bff; /* Blue border */
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .profile-image-circle:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: #0056b3;
        }

        .profile-image-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none; /* Hidden by default */
        }

        .profile-image-circle .icon-plus {
            color: #007bff;
            font-size: 2.5em; /* Adjusted icon size */
            transition: color 0.3s ease;
        }

        .profile-image-circle:hover .icon-plus {
            color: #0056b3;
        }

        .profile-image-upload p {
            margin-top: 10px;
            font-size: 0.85em; /* Smaller text */
            color: #777;
        }

        .hidden-file-input {
            display: none;
        }

        button[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        button[type="submit"]:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9em;
            font-weight: 500;
            text-align: center;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-to-login {
            display: block;
            margin-top: 15px;
            font-size: 0.9em;
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-to-login:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        /* Media Queries pour le Responsive Design */
        @media (max-width: 600px) {
            .signup-container {
                padding: 20px 10px;
                border-radius: 8px;
                max-width: 95%;
            }
            h2 {
                font-size: 1.6em;
                margin-bottom: 15px;
            }
            label {
                font-size: 0.85em;
            }
            input, button {
                padding: 6px;
                font-size: 0.85em;
            }
            .message {
                font-size: 0.85em;
                padding: 8px;
            }
            .back-to-login {
                font-size: 0.85em;
            }
            .profile-image-circle {
                width: 80px; /* Even smaller on mobile */
                height: 80px;
            }
            .profile-image-circle .icon-plus {
                font-size: 2em;
            }
            .profile-image-upload p {
                font-size: 0.8em;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <h2>Inscription Client</h2>

        <?php if (!empty($message)) : ?>
            <p class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <!-- Profile Image Upload Section -->
            <div class="profile-image-upload">
                <div class="profile-image-circle" id="profileImageCircle">
                    <img id="profileImagePreview" src="#" alt="Prévisualisation de la photo de profil">
                    <i data-lucide="plus" class="icon-plus"></i>
                </div>
                <p>Cliquez pour ajouter une photo de profil</p>
                <input type="file" id="photo" name="photo" accept="image/*" class="hidden-file-input">
            </div>

            <div class="form-group">
                <label for="nom">Nom :</label>
                <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="prenom">Prénom :</label>
                <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="date_naissance">Date de naissance :</label>
                <input type="date" id="date_naissance" name="date_naissance" required value="<?= htmlspecialchars($_POST['date_naissance'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="telephone">Téléphone :</label>
                <input type="tel" id="telephone" name="telephone" required value="<?= htmlspecialchars($_POST['telephone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe :</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>

            <div class="form-group">
                <label for="confirmer_mot_de_passe">Confirmer le mot de passe :</label>
                <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" required>
            </div>

            <button type="submit">S'inscrire</button>
        </form>

        <a href="connexion.php" class="back-to-login">Retour à la connexion</a>
    </div>

    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            const profileImageCircle = document.getElementById('profileImageCircle');
            const profileImageInput = document.getElementById('photo');
            const profileImagePreview = document.getElementById('profileImagePreview');
            const iconPlus = profileImageCircle.querySelector('.icon-plus');

            // Trigger file input click when circle is clicked
            profileImageCircle.addEventListener('click', () => {
                profileImageInput.click();
            });

            // Display selected image preview
            profileImageInput.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        profileImagePreview.src = e.target.result;
                        profileImagePreview.style.display = 'block'; // Show the image
                        iconPlus.style.display = 'none'; // Hide the plus icon
                    };
                    reader.readAsDataURL(file);
                } else {
                    profileImagePreview.src = '#';
                    profileImagePreview.style.display = 'none'; // Hide the image
                    iconPlus.style.display = 'block'; // Show the plus icon
                }
            });
        });
    </script>
</body>
</html>
