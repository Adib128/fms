<?php

/**
 * Shared constants and enums for the application
 */

// Huile Types Enum - Updated to match oil_types table structure
class HuileType {
    const MOTEUR = 'Moteur';
    const BOITE_VITESSE = 'Boite Vitesse';
    const PONT = 'Pont';
    
    /**
     * Get all huile types as an array
     * @return array
     */
    public static function getAll() {
        return [
            self::MOTEUR,
            self::BOITE_VITESSE,
            self::PONT
        ];
    }
    
    /**
     * Get huile types as options for select dropdown
     * @return array
     */
    public static function getOptions() {
        return [
            '' => 'Choisir le type',
            self::MOTEUR => self::MOTEUR,
            self::BOITE_VITESSE => self::BOITE_VITESSE,
            self::PONT => self::PONT
        ];
    }
    
    /**
     * Check if a type is valid
     * @param string $type
     * @return bool
     */
    public static function isValid($type) {
        return in_array($type, self::getAll());
    }
}

// Filtre Types Enum
class FiltreType {
    const FILTRE_AIR = 'Filtre a air';
    const FILTRE_CARBURANT = 'Filtre carburant';
    const FILTRE_HUILE = 'Filtre a huile';
    const FILTRE_BOITE_VITESSE = 'Filtre boite vitesse';
    
    /**
     * Get all filtre types as an array
     * @return array
     */
    public static function getAll() {
        return [
            self::FILTRE_AIR,
            self::FILTRE_CARBURANT,
            self::FILTRE_HUILE,
            self::FILTRE_BOITE_VITESSE
        ];
    }
    
    /**
     * Get filtre types as options for select dropdown
     * @return array
     */
    public static function getOptions() {
        return [
            '' => 'Choisir un type',
            self::FILTRE_AIR => self::FILTRE_AIR,
            self::FILTRE_CARBURANT => self::FILTRE_CARBURANT,
            self::FILTRE_HUILE => self::FILTRE_HUILE,
            self::FILTRE_BOITE_VITESSE => self::FILTRE_BOITE_VITESSE
        ];
    }
    
    /**
     * Check if a type is valid
     * @param string $type
     * @return bool
     */
    public static function isValid($type) {
        return in_array($type, self::getAll());
    }
}

// Operation Nature Enum
class OperationNature {
    const APOINT = 'apoint';
    const VIDANGE = 'vidange';
    
    /**
     * Get all operation natures as an array
     * @return array
     */
    public static function getAll() {
        return [
            self::APOINT,
            self::VIDANGE
        ];
    }
    
    /**
     * Get operation natures as options for select dropdown
     * @return array
     */
    public static function getOptions() {
        return [
            '' => 'Choisir une nature',
            self::APOINT => 'Apoint',
            self::VIDANGE => 'Vidange'
        ];
    }
    
    /**
     * Check if a nature is valid
     * @param string $nature
     * @return bool
     */
    public static function isValid($nature) {
        return in_array($nature, self::getAll());
    }
}

// Filtre Action Enum
class FiltreAction {
    const NETTOYAGE = 'Nettoyage';
    const CHANGEMENT = 'Changement';
    
    /**
     * Get all filtre actions as an array
     * @return array
     */
    public static function getAll() {
        return [
            self::NETTOYAGE,
            self::CHANGEMENT
        ];
    }
    
    /**
     * Get filtre actions as options for select dropdown
     * @return array
     */
    public static function getOptions() {
        return [
            '' => 'Choisir une action',
            self::NETTOYAGE => self::NETTOYAGE,
            self::CHANGEMENT => self::CHANGEMENT
        ];
    }
    
    /**
     * Check if an action is valid
     * @param string $action
     * @return bool
     */
    public static function isValid($action) {
        return in_array($action, self::getAll());
    }
}

// Operation Category Enum
class OperationCategory {
    const HUILES = 'Huiles';
    const FILTRES = 'Filtres';
    
    /**
     * Get all operation categories as an array
     * @return array
     */
    public static function getAll() {
        return [
            self::HUILES,
            self::FILTRES
        ];
    }
    
    /**
     * Get operation categories as options for select dropdown
     * @return array
     */
    public static function getOptions() {
        return [
            '' => 'Choisir une catégorie',
            self::HUILES => self::HUILES,
            self::FILTRES => self::FILTRES
        ];
    }
    
    /**
     * Check if a category is valid
     * @param string $category
     * @return bool
     */
    public static function isValid($category) {
        return in_array($category, self::getAll());
    }
}
