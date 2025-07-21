<?php
session_start();

// Identifiants admin fixes
$admin_username = "admin_user"; // À personnaliser
$admin_password_hash = password_hash("12345678", PASSWORD_DEFAULT); // À changer !

$message_erreur = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email === $admin_username && password_verify($password, $admin_password_hash)) {
        $_SESSION['user_id'] = -1; // ID fictif pour admin
        $_SESSION['user_email'] = $admin_username;
        $_SESSION['is_admin_fixed'] = true;
        header("Location: menu_admin.php");
        exit();
    } else {
        if (!empty($email) && !empty($password)) {
            $conn = new mysqli('localhost', 'root', '', 'gestiondebillet');
            if ($conn->connect_error) {
                die("Erreur de connexion : " . $conn->connect_error);
            }
            $conn->set_charset("utf8mb4");

            $stmt = $conn->prepare("SELECT Id_Utilisateur, Email, MotDePasse FROM utilisateur WHERE Email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($id, $email_db, $motdepasse_hash);
                    $stmt->fetch();

                    if (password_verify($password, $motdepasse_hash)) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $id;
                        $_SESSION['user_email'] = $email_db;
                        header("Location: menu_utilisateur.php");
                        exit();
                    } else {
                        $message_erreur = "Mot de passe incorrect.";
                    }
                } else {
                    $message_erreur = "Adresse email inconnue.";
                }
                $stmt->close();
            } else {
                $message_erreur = "Erreur de requête.";
            }
            $conn->close();
        } else {
            $message_erreur = "Veuillez remplir tous les champs.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion de Billets</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-image: url(nainoa-shizuru-NcdG9mK3PBY-unsplash.jpg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: flex;
            min-height: 500px;
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #ff6b35 0%, #ff8e53 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: white;
            position: relative;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="40" r="1.5" fill="white" opacity="0.1"/><circle cx="40" cy="80" r="1" fill="white" opacity="0.1"/></svg>');
        }

        .login-content {
            text-align: center;
            z-index: 2;
            position: relative;
        }

        .login-content h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .login-content p {
            font-size: 1.1em;
            opacity: 0.9;
            line-height: 1.6;
        }

        .login-right {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            color: #333;
            font-size: 2em;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #666;
            font-size: 0.95em;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #ff6b35;
            font-size: 1.1em;
        }

        .form-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ff6b35;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c44;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #ff6b35 0%, #ff8e53 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .form-footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 0.9em;
        }

        .form-footer a {
            color: #ff6b35;
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 400px;
            }
            
            .login-left {
                padding: 30px;
            }
            
            .login-content h1 {
                font-size: 2em;
            }
            
            .login-right {
                padding: 30px;
            }
        }

        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-content">
                <h1><i class="fas fa-ticket-alt"></i> Gestion de Billets</h1>
                <p>Plateforme professionnelle de gestion et de suivi des billets. Connectez-vous pour accéder à votre espace personnalisé.</p>
            </div>
        </div>
        
        <div class="login-right">
            <div class="form-header">
                <h2>Connexion</h2>
                <p>Accédez à votre espace</p>
            </div>
            
            <?php if (!empty($message_erreur)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($message_erreur); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="email">Email / Nom d'utilisateur</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" 
                               id="email" 
                               name="email" 
                               placeholder="Entrez votre email ou nom d'utilisateur"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder="Entrez votre mot de passe"
                               required>
                    </div>
                </div>
                
                <button type="submit" class="btn-login" id="submitBtn">
                    <span class="loading"></span>
                    <span class="btn-text">Se connecter</span>
                </button>
            </form>
            
            <div class="form-footer">
                <p>Mot de passe oublié ? <a href="inscription.php">inscription</a></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            const submitBtn = document.getElementById('submitBtn');
            const loading = submitBtn.querySelector('.loading');
            const btnText = submitBtn.querySelector('.btn-text');
            
            loading.style.display = 'inline-block';
            btnText.textContent = 'Connexion en cours...';
            submitBtn.disabled = true;
        });

        // Animation d'entrée
        window.addEventListener('load', function() {
            document.querySelector('.login-container').style.animation = 'slideIn 0.6s ease-out';
        });

        // Ajout de styles d'animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>