<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/class/class.php';
require_once __DIR__ . '/helpers/security.php';

// Security check - only admin can access user management
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

$userId = $_GET['id'] ?? '';
$erreur = '';
$succes = '';

if ($userId === '') {
    header('Location: ' . url('list-user'));
    exit;
}

$user = $admin->getUserById($userId);
if (!$user) {
    header('Location: ' . url('list-user'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete'])) {
        if ($admin->deleteUser($userId)) {
            $succes = "Utilisateur supprimé avec succès";
            // Redirect after successful deletion
            header('Location: ' . url('list-user') . '?success=deleted');
            exit;
        } else {
            $erreur = "Erreur lors de la suppression de l'utilisateur";
        }
    } else {
        // Cancel deletion
        header('Location: ' . url('list-user'));
        exit;
    }
}
?>

<div id="page-wrapper">
    <div class="page-title">
        <h1>Supprimer un utilisateur</h1>
    </div>

    <div class="panel">
        <div class="panel-heading">
            Confirmation de suppression
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

            <div class="alert alert-danger">
                <strong>Attention!</strong> Vous êtes sur le point de supprimer l'utilisateur suivant :
            </div>

            <table class="table mb-6">
                <tr>
                    <th>ID:</th>
                    <td><?= $user['id_admin'] ?></td>
                </tr>
                <tr>
                    <th>Login:</th>
                    <td><?= htmlspecialchars($user['login'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <th>Profil:</th>
                    <td>
                        <?php
                        $profileLabels = [
                            'agent' => 'Agent de saisie',
                            'responsable' => 'Responsable Maitrise de l\'energie',
                            'admin' => 'Administrateur'
                        ];
                        echo $profileLabels[$user['profile']] ?? $user['profile'];
                        ?>
                    </td>
                </tr>
            </table>

            <form method="post" class="form-horizontal">
                <div class="form-group">
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <p class="text-red-700 font-medium mb-2">Cette action est irréversible!</p>
                        <p class="text-red-600 text-sm">
                            La suppression de cet utilisateur entraînera la perte permanente de ses données et il ne pourra plus accéder au système.
                        </p>
                    </div>
                </div>

                <div class="form-group">
                    <div class="flex gap-2">
                        <button type="submit" name="confirm_delete" value="1" class="btn btn-danger">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6M14 11v6"/>
                            </svg>
                            Supprimer définitivement
                        </button>
                        <button type="submit" name="cancel" value="1" class="btn btn-default">
                            Annuler
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
