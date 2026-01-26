<?php
/**
 * Validation Helper Functions
 */

require_once __DIR__ . '/../../config/constants.php';

class ValidationHelper
{
    /**
     * Validate CSRF token
     *
     * @param string $token Token to validate
     * @return bool True if token is valid, false otherwise
     */
    public static function validateCSRFToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Validate email format
     *
     * @param string $email Email to validate
     * @return bool True if email is valid, false otherwise
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate password strength
     *
     * @param string $password Password to validate
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra mayúscula';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra minúscula';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un número';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un carácter especial';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate verification code format
     *
     * @param string $code Code to validate
     * @return bool True if code is valid, false otherwise
     */
    public static function validateVerificationCode(string $code): bool
    {
        return preg_match('/^[0-9]{6}$/', $code) === 1;
    }

    /**
     * Validate required fields
     *
     * @param array $data Data to validate
     * @param array $requiredFields List of required fields
     * @return array List of missing fields
     */
    public static function validateRequired(array $data, array $requiredFields): array
    {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $missing[] = $field;
            }
        }
        
        return $missing;
    }

    /**
     * Sanitize input
     *
     * @param string $input Input to sanitize
     * @return string Sanitized input
     */
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate numeric value
     *
     * @param mixed $value Value to validate
     * @return bool True if value is numeric, false otherwise
     */
    public static function validateNumeric($value): bool
    {
        return is_numeric($value);
    }

    /**
     * Validate integer value
     *
     * @param mixed $value Value to validate
     * @return bool True if value is integer, false otherwise
     */
    public static function validateInteger($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate date format
     *
     * @param string $date Date to validate
     * @param string $format Expected format (default: Y-m-d)
     * @return bool True if date is valid, false otherwise
     */
    public static function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate file upload (wrapper for simpler usage)
     *
     * @param array $file File data from $_FILES
     * @param array $allowedExtensions Allowed file extensions (e.g. ['jpg', 'pdf'])
     * @param int $maxSize Maximum file size in bytes
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateFile(array $file, array $allowedExtensions, int $maxSize): array
    {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error al subir el archivo';
            return ['valid' => false, 'errors' => $errors];
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = 'El archivo excede el tamaño máximo permitido';
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Tipo de archivo no permitido. Permitidos: ' . implode(', ', $allowedExtensions);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate file upload
     *
     * @param array $file File data from $_FILES
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateFileUpload(array $file, array $allowedTypes, int $maxSize): array
    {
        $errors = [];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error al subir el archivo';
            return ['valid' => false, 'errors' => $errors];
        }

        if ($file['size'] > $maxSize) {
            $errors[] = 'El archivo excede el tamaño máximo permitido';
        }

        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = 'Tipo de archivo no permitido';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate phone number format
     *
     * @param string $phone Phone number to validate
     * @return bool True if phone is valid, false otherwise
     */
    public static function validatePhone(string $phone): bool
    {
        // Remove all non-digit characters for validation
        $cleaned = preg_replace('/\D/', '', $phone);

        // Venezuelan phone number patterns:
        // - 0412/0414/0416/0424/0426 + 7 digits (10 digits total)
        // - 0212 + 7 digits (local Caracas)
        // - International format: +58 + 10 digits

        // Check for international format first
        if (preg_match('/^\+58\d{10}$/', $phone)) {
            return true;
        }

        // Check for local format (10 digits starting with 04 or 0212)
        if (preg_match('/^(0412|0414|0416|0424|0426|0212)\d{7}$/', $cleaned)) {
            return true;
        }

        // Check for 10-digit format without prefix
        if (preg_match('/^\d{10}$/', $cleaned)) {
            return true;
        }

        return false;
    }
}
