<?php
class Router
{
    /**
     * @var array<string, array<string, callable|string>>
     */
    private array $routes;

    private string $basePath;

    private string $frontController;

    /**
     * @var string[]
     */
    private array $publicPaths = [];

    /**
     * @param array<string, array<string, callable|string>> $routes
     */
    public function __construct(array $routes, string $basePath, string $frontController, array $publicPaths = [])
    {
        $this->routes = $routes;
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->frontController = realpath($frontController) ?: $frontController;
        $this->publicPaths = array_map(function (string $path): string {
            $normalized = trim($path);

            if ($normalized === '') {
                return '/';
            }

            $normalized = '/' . ltrim($normalized, '/');

            if ($normalized !== '/' && substr($normalized, -1) === '/') {
                $normalized = rtrim($normalized, '/');
            }

            if ($normalized === '/index.php') {
                return '/';
            }

            return $normalized;
        }, $publicPaths);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        if ($path === '/index.php') {
            $path = '/';
        }

        if (!$this->isPublicPath($path) && !$this->isAuthenticated()) {
            $this->redirect(url('/'));
            return;
        }

        if ($this->isPublicPath($path) && $this->isAuthenticated() && $path === '/') {
            $this->redirect(url('dashboard'));
            return;
        }

        // Add authorization check for authenticated users
        if ($this->isAuthenticated() && !$this->isAuthorized($path)) {
            $this->renderForbidden();
            return;
        }

        $method = strtoupper($method);
        $handler = $this->matchRoute($method, $path);

        if ($handler === null) {
            if ($this->tryFallback($path)) {
                return;
            }

            $this->renderNotFound();
            return;
        }

        if (is_callable($handler)) {
            $handler();
            return;
        }

        $this->renderFile($handler, $path);
    }

    private function matchRoute(string $method, string $path): callable|string|null
    {
        if (isset($this->routes[$method][$path])) {
            return $this->routes[$method][$path];
        }

        if (isset($this->routes['ANY'][$path])) {
            return $this->routes['ANY'][$path];
        }

        if ($method === 'HEAD' && isset($this->routes['GET'][$path])) {
            return $this->routes['GET'][$path];
        }

        return null;
    }

    private function renderFile(string $relativePath, string $requestPath): void
    {
        $file = $this->basePath . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR);

        if (!file_exists($file)) {
            $this->renderNotFound();
            return;
        }

        $resolved = realpath($file) ?: $file;
        $this->setServerContext($requestPath, $resolved);

        require $resolved;
    }

    private function tryFallback(string $path): bool
    {
        $candidates = [];

        $normalized = trim($path, '/');

        if ($normalized === '') {
            $candidates[] = 'index.php';
        } else {
            if (substr($normalized, -4) === '.php') {
                $candidates[] = $normalized;
            }

            $candidates[] = $normalized . '.php';
            $candidates[] = str_replace('-', '_', $normalized) . '.php';
        }

        foreach ($candidates as $candidate) {
            $file = $this->basePath . DIRECTORY_SEPARATOR . $candidate;

            if (!file_exists($file)) {
                continue;
            }

            $resolved = realpath($file) ?: $file;

            if ($resolved === $this->frontController) {
                continue;
            }

            $this->setServerContext($path === '' ? '/' : $path, $resolved);

            require $resolved;
            return true;
        }

        return false;
    }

    private function setServerContext(string $requestPath, string $resolvedFile): void
    {
        $normalizedPath = $requestPath === '' ? '/' : $requestPath;

        if ($normalizedPath[0] !== '/') {
            $normalizedPath = '/' . $normalizedPath;
        }

        $_SERVER['PHP_SELF'] = $normalizedPath;
        $_SERVER['SCRIPT_NAME'] = $normalizedPath;
        $_SERVER['SCRIPT_FILENAME'] = $resolvedFile;
    }

    private function isPublicPath(string $path): bool
    {
        return in_array($path, $this->publicPaths, true);
    }

    private function isAuthenticated(): bool
    {
        return !empty($_SESSION['login']);
    }

    private function redirect(string $to): void
    {
        header('Location: ' . $to);
        exit;
    }

    private function renderNotFound(): void
    {
        http_response_code(404);
        $errorPage = $this->basePath . DIRECTORY_SEPARATOR . 'pages/errors/404.php';

        if (file_exists($errorPage)) {
            require $errorPage;
            return;
        }

        echo '<h1>404</h1><p>Page non trouvée.</p>';
    }

    private function renderForbidden(): void
    {
        http_response_code(403);
        $errorPage = $this->basePath . DIRECTORY_SEPARATOR . '403.php';

        if (file_exists($errorPage)) {
            require $errorPage;
            return;
        }

        echo '<h1>403</h1><p>Accès interdit.</p>';
    }

    private function isAuthorized(string $path): bool
    {
        if (!isset($_SESSION['profile'])) {
            return false;
        }

        $userProfile = $_SESSION['profile'];
        
        // Explicit deny rules for user management (except for admin)
        $userManagementRoutes = ['/list-user', '/ajoute-user', '/modifier-user', '/supprimer-user'];
        if (in_array($path, $userManagementRoutes) && $userProfile !== 'admin') {
            return false;
        }

        // Explicit deny rules for maintenance add/edit/delete for respensable_maitrise
        if ($userProfile === 'respensable_maitrise') {
            $maintenanceRestrictedPatterns = [
                '/^\/ajouter-(reclamation|demande|ordre)/',
                '/^\/modifier-(reclamation|demande|ordre)/',
                '/^\/supprimer-(reclamation|demande|ordre)/',
                '/^\/cloturer-demande/',
                '/^\/cloturer-ordre/'
            ];
            foreach ($maintenanceRestrictedPatterns as $pattern) {
                if (preg_match($pattern, $path)) {
                    return false;
                }
            }
        }
        
        // Define allowed routes for each profile
        $allowedRoutes = [
            'admin' => [
                // Admin has access to everything
                '.*' // All routes
            ],
            'respensable_maitrise' => [
                // Respensable has access to everything except user management (handled above)
                '/dashboard',
                '/liste-.*',
                '/ajouter-.*',
                '/modifier-.*',
                '/supprimer-.*',
                '/rapport-.*',
                '/etat-.*',
                '/planification-.*',
                '/gestion-.*',
                '/enregistrer-.*',
                '/consulter-.*',
                '/details-.*',
                '/vehicule-details',
                '/api/.*',
                // Agent-specific routes are also accessible
                '/liste-doc-carburant',
                '/enregistrer-carburant',
                '/consulter-doc-carburant',
                '/enregistrer-carburant-item',
                '/modifier-carburant',
                '/liste-fiche-entretien',
                '/ajouter-fiche-entretien',
                '/details-fiche-entretien',
                '/enregistrer-kilometrage',
                '/liste-kilometrage',
                // Additional specific routes that should be allowed
                '/api/get_documents_by_date_station',
                '/disponibilite-vehicules',
                '/historique-maintenance',
                '/statistique-maintenance',
                // Logout route
                '/deconnexion'
            ],
            'agent_maitrise' => [
                // Agent has limited access - only specific routes
                '/liste-doc-carburant',
                '/enregistrer-carburant',
                '/consulter-doc-carburant',
                '/enregistrer-carburant-item',
                '/modifier-carburant',
                '/liste-fiche-entretien',
                '/ajouter-fiche-entretien',
                '/details-fiche-entretien',
                '/modifier-document',
                '/enregistrer-kilometrage',
                '/liste-kilometrage',
                // Vehicle management routes for agents
                '/liste-vehicule',
                '/ajouter-vehicule',
                '/modifier-vehicule',
                '/supprimer-vehicule',
                '/deconnexion'
            ],
            'responsable_maintenance' => [
                '/dashboard',
                '/dashboard-maintenance',
                '/liste-vehicule',
                '/ajouter-vehicule',
                '/modifier-vehicule',
                '/supprimer-vehicule',
                '/liste-immobilisation',
                '/ajouter-immobilisation',
                '/modifier-immobilisation',
                '/supprimer-immobilisation',
                '/fin-immobilisation',
                '/liste-reclamation',
                '/ajouter-reclamation',
                '/modifier-reclamation',
                '/supprimer-reclamation',
                '/liste-demande',
                '/ajouter-demande',
                '/modifier-demande',
                '/supprimer-demande',
                '/cloturer-demande',
                '/consulter-demande',
                '/imprimer-demande',
                '/liste-ordre',
                '/ajouter-ordre',
                '/modifier-ordre',
                '/supprimer-ordre',
                '/cloturer-ordre',
                '/consulter-ordre',
                '/imprimer-ordre',
                '/disponibilite-vehicules',
                '/historique-maintenance',
                '/statistique-maintenance',
                '/liste-checklist-items',
                '/ajouter-checklist-item',
                '/modifier-checklist-item',
                '/supprimer-checklist-item',
                '/liste-atelier',
                '/ajouter-atelier',
                '/modifier-atelier',
                '/supprimer-atelier',
                '/liste-systeme',
                '/ajouter-systeme',
                '/modifier-systeme',
                '/supprimer-systeme',
                '/liste-anomalie',
                '/ajouter-anomalie',
                '/modifier-anomalie',
                '/supprimer-anomalie',
                '/liste-maintenance',
                '/ajouter-maintenance',
                '/modifier-maintenance',
                '/supprimer-maintenance',
                '/liste-intervention',
                '/ajouter-intervention',
                '/modifier-intervention',
                '/supprimer-intervention',
                '/liste-article',
                '/ajouter-article',
                '/modifier-article',
                '/supprimer-article',
                '/vehicule-details',
                '/api/.*',
                '/deconnexion'
            ],
            'controle_technique' => [
                '/dashboard',
                '/liste-vehicule',
                '/ajouter-vehicule',
                '/modifier-vehicule',
                '/supprimer-vehicule',
                '/liste-immobilisation',
                '/ajouter-immobilisation',
                '/modifier-immobilisation',
                '/supprimer-immobilisation',
                '/fin-immobilisation',
                '/liste-reclamation',
                '/ajouter-reclamation',
                '/modifier-reclamation',
                '/supprimer-reclamation',
                '/liste-demande',
                '/ajouter-demande',
                '/modifier-demande',
                '/supprimer-demande',
                '/cloturer-demande',
                '/consulter-demande',
                '/imprimer-demande',
                '/liste-ordre',
                '/ajouter-ordre',
                '/modifier-ordre',
                '/supprimer-ordre',
                '/cloturer-ordre',
                '/consulter-ordre',
                '/imprimer-ordre',
                '/disponibilite-vehicules',
                '/historique-maintenance',
                '/statistique-maintenance',
                '/liste-checklist-items',
                '/ajouter-checklist-item',
                '/modifier-checklist-item',
                '/supprimer-checklist-item',
                '/liste-atelier',
                '/ajouter-atelier',
                '/modifier-atelier',
                '/supprimer-atelier',
                '/liste-systeme',
                '/ajouter-systeme',
                '/modifier-systeme',
                '/supprimer-systeme',
                '/liste-anomalie',
                '/ajouter-anomalie',
                '/modifier-anomalie',
                '/supprimer-anomalie',
                '/liste-maintenance',
                '/ajouter-maintenance',
                '/modifier-maintenance',
                '/supprimer-maintenance',
                '/liste-intervention',
                '/ajouter-intervention',
                '/modifier-intervention',
                '/supprimer-intervention',
                '/liste-article',
                '/ajouter-article',
                '/modifier-article',
                '/supprimer-article',
                '/vehicule-details',
                '/api/.*',
                '/deconnexion'
            ],
            'respensable_maintenance_preventive' => [
                '/dashboard',
                // Maitrise de l'energie (Reports only, no docs)
                '/api/get_documents_by_date_station',
                // Fiche d'entretien
                '/liste-fiche-entretien',
                '/ajouter-fiche-entretien',
                '/details-fiche-entretien',
                // Planing entretien
                '/planification-.*',
                // Common
                '/api/.*',
                '/deconnexion'
            ]
        ];

        // Admin has access to everything
        if ($userProfile === 'admin') {
            return true;
        }

        // Check if route is explicitly allowed for the user's profile
        if (isset($allowedRoutes[$userProfile])) {
            foreach ($allowedRoutes[$userProfile] as $allowedRoute) {
                // Handle wildcards
                if (strpos($allowedRoute, '.*') !== false) {
                    $pattern = '/^' . str_replace('\.\*', '.*', preg_quote($allowedRoute, '/')) . '$/';
                    if (preg_match($pattern, $path)) {
                        return true;
                    }
                } else {
                    // Exact match
                    if ($path === $allowedRoute) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
