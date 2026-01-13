<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/class/class.php';
require_once __DIR__ . '/helpers/security.php';

// Security check - only admin can access user management
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

$erreur = '';
$succes = '';
$loginValue = '';
$profileValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginValue = isset($_POST['login']) ? trim($_POST['login']) : '';
    $profileValue = $_POST['profile'] ?? '';
    $passwordValue = $_POST['pass'] ?? '';
    $passwordConfirmValue = $_POST['pass_confirm'] ?? '';

    if ($loginValue === '' || $passwordValue === '' || $profileValue === '') {
        $erreur = "Tous les champs sont obligatoires";
    } elseif ($passwordValue !== $passwordConfirmValue) {
        $erreur = "Les mots de passe ne correspondent pas";
    } elseif (strlen($passwordValue) < 4) {
        $erreur = "Le mot de passe doit contenir au moins 4 caractères";
    } elseif ($admin->isLoginExists($loginValue)) {
        $erreur = "Ce login est déjà utilisé";
    } else {
        $admin->setLogin($loginValue);
        $admin->setPass(md5($passwordValue));
        $admin->setProfile($profileValue);
        
        if ($admin->addUser()) {
            header('Location: ' . url('list-user') . '?success=added');
            exit;
        } else {
            $erreur = "Erreur lors de l'ajout de l'utilisateur";
        }
    }
}
?>

<div id="page-wrapper">
    <div class="page-title">
        <h1>Ajouter un utilisateur</h1>
    </div>

    <div class="panel">
        <div class="panel-heading">
            Nouvel utilisateur
            <div class="flex gap-2">
                <a href="<?= url('list-user') ?>" class="btn btn-default">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Retour
                </a>
            </div>
        </div>
        <div class="panel-body">
            <?php if ($erreur !== ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($succes !== ''): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($succes, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="form-horizontal">
                <div class="form-group">
                    <label class="control-label">Login</label>
                    <div>
                        <input type="text" name="login" class="form-control" 
                               value="<?= htmlspecialchars($loginValue, ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="Entrez le login" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label">Mot de passe</label>
                    <div>
                        <input type="password" name="pass" class="form-control" 
                               placeholder="Entrez le mot de passe" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label">Confirmer le mot de passe</label>
                    <div>
                        <input type="password" name="pass_confirm" class="form-control" 
                               placeholder="Confirmez le mot de passe" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label">Profil</label>
                    <div>
                        <select name="profile" class="form-control" required>
                            <option value="">Sélectionner un profil</option>
                            <option value="agent_maitrise" <?= $profileValue === 'agent_maitrise' ? 'selected' : '' ?>>Agent de saisie</option>
                            <option value="respensable_maitrise" <?= $profileValue === 'respensable_maitrise' ? 'selected' : '' ?>>Responsable Maitrise de l'energie</option>
                            <option value="responsable_maintenance" <?= $profileValue === 'responsable_maintenance' ? 'selected' : '' ?>>Responsable Maintenance</option>
                            <option value="respensable_maintenance_preventive" <?= $profileValue === 'respensable_maintenance_preventive' ? 'selected' : '' ?>>Respensable maintenance préventive</option>
                            <option value="controle_technique" <?= $profileValue === 'controle_technique' ? 'selected' : '' ?>>Contrôle Technique</option>
                            <option value="admin" <?= $profileValue === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 13l4 4L19 7"/>
                            </svg>
                            Ajouter
                        </button>
                        <a href="<?= url('list-user') ?>" class="btn btn-default">
                            Annuler
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
