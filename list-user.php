<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/class/class.php';
require_once __DIR__ . '/helpers/security.php';

// Security check - only admin can access user management
enforceRouteAccess(getCurrentRoute(), getCurrentUserProfile());

$users = $admin->getAllUsers();
$profileLabels = [
    'agent' => 'Agent de saisie',
    'responsable' => 'Responsable Maitrise de l\'energie',
    'admin' => 'Administrateur'
];

// Handle success messages from redirects
$successMessage = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added':
            $successMessage = "Utilisateur ajouté avec succès";
            break;
        case 'updated':
            $successMessage = "Utilisateur modifié avec succès";
            break;
        case 'deleted':
            $successMessage = "Utilisateur supprimé avec succès";
            break;
    }
}
?>

<div id="page-wrapper">
    <div class="page-title">
        <h1>Gestion des utilisateurs</h1>
    </div>

    <div class="panel">
        <div class="panel-heading">
            Liste des utilisateurs
            <div class="flex gap-2">
                <a href="<?= url('ajoute-user') ?>" class="btn btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Ajouter un utilisateur
                </a>
            </div>
        </div>
        <div class="panel-body">
            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <?php if (empty($users)): ?>
                <div class="text-center py-8 text-slate-500">
                    Aucun utilisateur trouvé.
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Login</th>
                            <th>Profil</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id_admin'] ?></td>
                                <td><?= htmlspecialchars($user['login'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge">
                                        <?= $profileLabels[$user['profile']] ?? $user['profile'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <a href="<?= url('modifier-user') ?>?id=<?= $user['id_admin'] ?>" class="btn btn-sm btn-default">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                            Modifier
                                        </a>
                                        <?php if ($user['id_admin'] != $_SESSION['user_id']): ?>
                                            <a href="<?= url('supprimer-user') ?>?id=<?= $user['id_admin'] ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6M14 11v6"/>
                                                </svg>
                                                Supprimer
                                            </a>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">Utilisateur actuel</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
