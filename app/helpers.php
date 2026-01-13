<?php
if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $normalized = trim($path);

        if ($normalized === '') {
            return '/';
        }

        return '/' . ltrim($normalized, '/');
    }
}

if (!function_exists('setFlashMessage')) {
    function setFlashMessage(string $message): void
    {
        $_SESSION['message'] = $message;
    }
}

if (!function_exists('getFlashMessage')) {
    function getFlashMessage(): ?string
    {
        if (isset($_SESSION['message'])) {
            $message = $_SESSION['message'];
            unset($_SESSION['message']);
            return $message;
        }
        return null;
    }
}

if (!function_exists('hasFlashMessage')) {
    function hasFlashMessage(): bool
    {
        return isset($_SESSION['message']);
    }
}

if (!function_exists('formatQuantity')) {
    function formatQuantity($val): string
    {
        if ($val === null || $val === '') return '0';
        $val = (float) $val;
        // Format with 2 decimals, comma as decimal separator, space as thousands separator
        $formatted = number_format($val, 2, ',', ' ');
        // Remove trailing zeros and trailing comma
        return rtrim(rtrim($formatted, '0'), ',');
    }
}
if (!function_exists('generateOrderNumber')) {
    function generateOrderNumber(PDO $db): string
    {
        $prefix = date('ym'); // YYMM
        
        // Find the highest number for this month
        $stmt = $db->prepare("SELECT numero FROM ordre WHERE numero LIKE ? ORDER BY numero DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $lastNumber = $stmt->fetchColumn();
        
        if ($lastNumber) {
            // Extract the sequence part (last 5 digits) and increment
            $sequence = (int) substr($lastNumber, 4);
            $nextSequence = $sequence + 1;
        } else {
            $nextSequence = 1;
        }
        
        // Format: YYMM + 5-digit sequence (padded with zeros)
        return $prefix . str_pad((string)$nextSequence, 5, '0', STR_PAD_LEFT);
    }
}
if (!function_exists('generateDemandeNumber')) {
    function generateDemandeNumber(PDO $db): string
    {
        $prefix = date('ym'); // YYMM
        
        // Find the highest number for this month
        $stmt = $db->prepare("SELECT numero FROM demande WHERE numero LIKE ? ORDER BY numero DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $lastNumber = $stmt->fetchColumn();
        
        if ($lastNumber && is_numeric($lastNumber) && strlen($lastNumber) === 9) {
            // Extract the sequence part (last 5 digits) and increment
            $sequence = (int) substr($lastNumber, 4);
            $nextSequence = $sequence + 1;
        } else {
            $nextSequence = 1;
        }
        
        // Format: YYMM + 5-digit sequence (padded with zeros)
        return $prefix . str_pad((string)$nextSequence, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generatePassationDemandeNumber')) {
    function generatePassationDemandeNumber(PDO $db): string
    {
        $prefix = 'PAS' . date('ym'); // PASYYMM
        
        // Find the highest number for this month
        $stmt = $db->prepare("SELECT numero FROM demande_passation WHERE numero LIKE ? ORDER BY numero DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $lastNumber = $stmt->fetchColumn();
        
        if ($lastNumber) {
            // Extract the sequence part (last 5 digits) and increment
            $sequence = (int) substr($lastNumber, 7);
            $nextSequence = $sequence + 1;
        } else {
            $nextSequence = 1;
        }
        
        // Format: PASYYMM + 5-digit sequence (padded with zeros)
        return $prefix . str_pad((string)$nextSequence, 5, '0', STR_PAD_LEFT);
    }
}
