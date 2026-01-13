<?php
/**
 * Security helper functions for profile-based authorization
 */

/**
 * Check if current user has access to a specific route based on their profile
 * @param string $route The current route
 * @param string $userProfile The user's profile (agent, responsable, admin)
 * @return bool True if access is granted, false otherwise
 */
function checkRouteAccess($route, $userProfile) {
    // Explicit deny rules for user management (except for admin)
    $userManagementRoutes = ['/list-user', '/ajoute-user', '/modifier-user', '/supprimer-user'];
    if (in_array($route, $userManagementRoutes) && $userProfile !== 'admin') {
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
            if (preg_match($pattern, $route)) {
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
                '/statistique-maintenance'
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
            '/supprimer-vehicule'
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
                if (preg_match($pattern, $route)) {
                    return true;
                }
            } else {
                // Exact match
                if ($route === $allowedRoute) {
                    return true;
                }
                // Handle routes with query parameters (like ?id=123)
                if (strpos($route, '?') !== false) {
                    $routeWithoutQuery = explode('?', $route)[0];
                    if ($routeWithoutQuery === $allowedRoute) {
                        return true;
                    }
                }
            }
        }
    }

    return false;
}

/**
 * Redirect to 403 page if access is denied
 * @param string $route The current route
 * @param string $userProfile The user's profile
 */
function enforceRouteAccess($route, $userProfile) {
    if (!checkRouteAccess($route, $userProfile)) {
        header('HTTP/1.0 403 Forbidden');
        header('Location: /403.php');
        exit;
    }
}

/**
 * Get the current request route
 * @return string The current route
 */
function getCurrentRoute() {
    $requestUri = $_SERVER['REQUEST_URI'];
    $parsedUrl = parse_url($requestUri);
    return $parsedUrl['path'];
}

/**
 * Check if user profile exists in session
 * @return bool True if user is logged in
 */
function isUserLoggedIn() {
    return isset($_SESSION['profile']) && !empty($_SESSION['profile']);
}

/**
 * Get current user profile from session
 * @return string|null The user profile or null if not logged in
 */
function getCurrentUserProfile() {
    return $_SESSION['profile'] ?? null;
}
?>
