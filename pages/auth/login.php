<?php
require_once __DIR__ . '/../../class/class.php';

$erreur = '';
$loginValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginValue = isset($_POST['login']) ? trim($_POST['login']) : '';
    $passwordValue = $_POST['pass'] ?? '';

    if ($loginValue === '' || $passwordValue === '') {
        $erreur = "Ecrire votre login et mot de passe";
    } else {
        $admin->setLogin($loginValue);
        $admin->setPass(md5($passwordValue));
        $userData = $admin->login();
        if ($userData) {
            // Set session variables
            $_SESSION['user_id'] = $userData['id_admin'];
            $_SESSION['login'] = $loginValue;
            $_SESSION['profile'] = $userData['profile'];
            
            // Set session timeout (30 minutes of inactivity)
            $_SESSION['last_activity'] = time();
            
            // Redirect based on user profile
            if ($userData['profile'] === 'agent') {
                $redirect = $_GET['redirect'] ?? 'liste-doc-carburant';
            } elseif ($userData['profile'] === 'responsable_maintenance' || $userData['profile'] === 'admin') {
                $redirect = $_GET['redirect'] ?? 'dashboard-maintenance';
            } elseif ($userData['profile'] === 'respensable_maitrise') {
                $redirect = $_GET['redirect'] ?? 'dashboard';
            } else {
                $redirect = $_GET['redirect'] ?? 'dashboard';
            }
            header('Location: ' . url($redirect));
            exit;
        }

        $erreur = "Login ou mot de passe incorrect";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="SRTJ Maîtrise d'énergie">
        <meta name="author" content="Adib">
        <title>SRTJ - FMS</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            brand: {
                                50: '#eef2ff',
                                100: '#e0e7ff',
                                500: '#6366f1',
                                600: '#4f46e5'
                            }
                        }
                    }
                }
            };
        </script>
        <style type="text/tailwindcss">
            @layer base {
                body {
                    @apply bg-slate-100 text-slate-900 antialiased;
                }
                a {
                    @apply transition-colors duration-200;
                }
            }

            @layer components {
                .card {
                    @apply relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl;
                }

                .card::before {
                    content: "";
                    @apply pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 via-sky-500 to-brand-600;
                }

                .card-header {
                    @apply flex flex-col items-center gap-4 px-8 pt-10 text-center;
                }

                .card-body {
                    @apply px-8 pb-10;
                }

                .brand-button {
                    @apply inline-flex w-full items-center justify-center rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-500/30 transition hover:bg-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100;
                }

                .form-field {
                    @apply flex flex-col gap-2;
                }

                .form-input {
                    @apply w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring focus:ring-brand-100;
                }

                .alert-error {
                    @apply rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700;
                }
            }
        </style>
    </head>
    <body class="min-h-screen">
        <div class="relative flex min-h-screen flex-col justify-center px-4 py-16 sm:px-6 lg:px-8">
            <div class="absolute inset-0 -z-10 overflow-hidden">
                <div class="absolute -top-36 -right-24 h-80 w-80 rounded-full bg-brand-100 blur-3xl opacity-50"></div>
                <div class="absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-sky-100 blur-3xl opacity-40"></div>
            </div>
            <div class="mx-auto w-full max-w-md">
                <div class="mb-10 text-center">
                    <span class="inline-flex items-center justify-center rounded-2xl bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-brand-600 shadow-sm ring-1 ring-brand-100">
                        SRTJ
                    </span>
                    <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Fleet Management System</h1>
                    <p class="mt-2 text-sm text-slate-500">Connectez-vous pour accéder à votre espace de gestion.</p>
                </div>
                <div class="card">
                    <div class="card-header">
                        <img src="<?= url('img/logo.png'); ?>" alt="Logo" class="h-24 w-auto">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Connexion</h2>
                            <p class="mt-1 text-sm text-slate-500">Entrez vos identifiants pour continuer</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($erreur !== ''): ?>
                            <div class="mb-6 alert-error">
                                <?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                        <form method="post" action="<?= htmlspecialchars(url('/')); ?>" class="space-y-5">
                            <div class="form-field">
                                <label for="login" class="text-sm font-medium text-slate-600">Login</label>
                                <input
                                    id="login"
                                    name="login"
                                    type="text"
                                    autocomplete="username"
                                    class="form-input"
                                    placeholder="Entrez votre login"
                                    value="<?= htmlspecialchars($loginValue, ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                >
                            </div>
                            <div class="form-field">
                                <label for="pass" class="text-sm font-medium text-slate-600">Mot de passe</label>
                                <input
                                    id="pass"
                                    name="pass"
                                    type="password"
                                    autocomplete="current-password"
                                    class="form-input"
                                    placeholder="Entrez votre mot de passe"
                                    required
                                >
                            </div>
                            <button type="submit" class="brand-button">
                                Connexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
