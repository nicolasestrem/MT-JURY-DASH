<?php
/**
 * Data Validator
 * 
 * @package MobilityTrailblazers
 * @since 2.5.42
 */

namespace MobilityTrailblazers\Validators;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data Validator Class
 * 
 * Centralized validation for all data types
 */
class MT_Data_Validator {
    
    /**
     * Validate evaluation score
     * 
     * @param float $score The score to validate
     * @param bool $allow_zero Whether to allow 0 scores
     * @return array Validation result [valid => bool, error => string|null]
     */
    public static function validate_score($score, $allow_zero = false) {
        $score = floatval($score);
        
        // Range validation (0-10)
        if ($score < 0 || $score > 10) {
            return [
                'valid' => false,
                'error' => __('Score must be between 0 and 10.', 'mobility-trailblazers')
            ];
        }
        
        // Check for 0 if not allowed
        if (!$allow_zero && $score < 1.0) {
            return [
                'valid' => false,
                'error' => __('Score cannot be 0 for completed evaluations.', 'mobility-trailblazers')
            ];
        }
        
        // Validate 0.5 increments
        $decimal_part = $score - floor($score);
        if ($decimal_part != 0 && $decimal_part != 0.5) {
            return [
                'valid' => false,
                'error' => __('Score must be in 0.5 increments (e.g., 7.0, 7.5, 8.0).', 'mobility-trailblazers')
            ];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Validate all evaluation scores
     * 
     * @param array $scores Array of scores to validate
     * @param bool $is_final Whether this is a final submission
     * @return array Validation results
     */
    public static function validate_evaluation_scores($scores, $is_final = false) {
        $errors = [];
        $valid = true;
        
        $required_fields = [
            'courage_score' => __('Courage & Pioneer Spirit', 'mobility-trailblazers'),
            'innovation_score' => __('Innovation Degree', 'mobility-trailblazers'),
            'implementation_score' => __('Implementation & Impact', 'mobility-trailblazers'),
            'relevance_score' => __('Mobility Transformation Relevance', 'mobility-trailblazers'),
            'visibility_score' => __('Role Model & Visibility', 'mobility-trailblazers')
        ];
        
        foreach ($required_fields as $field => $label) {
            // Check if field exists for final submission
            if ($is_final && (!isset($scores[$field]) || $scores[$field] === '' || $scores[$field] === null)) {
                $errors[] = sprintf(
                    __('%s is required for final submission.', 'mobility-trailblazers'),
                    $label
                );
                $valid = false;
                continue;
            }
            
            // Validate score if present
            if (isset($scores[$field]) && $scores[$field] !== '') {
                $result = self::validate_score($scores[$field], !$is_final);
                if (!$result['valid']) {
                    $errors[] = $label . ': ' . $result['error'];
                    $valid = false;
                }
            }
        }
        
        return [
            'valid' => $valid,
            'errors' => $errors
        ];
    }
    
    /**
     * Validate foreign key relationship
     * 
     * @param string $table Table name (without prefix)
     * @param int $id ID to check
     * @return bool
     */
    public static function validate_foreign_key($table, $id) {
        global $wpdb;
        
        $id = absint($id);
        if ($id <= 0) {
            return false;
        }
        
        // Handle different table types
        switch ($table) {
            case 'posts':
            case 'mt_candidate':
            case 'mt_jury_member':
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE ID = %d",
                    $id
                ));
                break;
                
            case 'users':
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->users} WHERE ID = %d",
                    $id
                ));
                break;
                
            default:
                // Custom tables
                $table_name = $wpdb->prefix . $table;
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE id = %d",
                    $id
                ));
        }
        
        return $count > 0;
    }
    
    /**
     * Validate assignment relationship
     * 
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return bool
     */
    public static function validate_assignment($jury_member_id, $candidate_id) {
        global $wpdb;
        
        $jury_member_id = absint($jury_member_id);
        $candidate_id = absint($candidate_id);
        
        if ($jury_member_id <= 0 || $candidate_id <= 0) {
            return false;
        }
        
        $table = $wpdb->prefix . 'mt_jury_assignments';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
             WHERE jury_member_id = %d AND candidate_id = %d",
            $jury_member_id,
            $candidate_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Validate email format
     * 
     * @param string $email Email to validate
     * @return bool
     */
    public static function validate_email($email) {
        return is_email($email) !== false;
    }
    
    /**
     * Validate URL format
     * 
     * @param string $url URL to validate
     * @return bool
     */
    public static function validate_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate date format
     * 
     * @param string $date Date string
     * @param string $format Expected format (default Y-m-d)
     * @return bool
     */
    public static function validate_date($date, $format = 'Y-m-d') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Validate numeric range
     * 
     * @param mixed $value Value to check
     * @param float $min Minimum value
     * @param float $max Maximum value
     * @return bool
     */
    public static function validate_range($value, $min, $max) {
        if (!is_numeric($value)) {
            return false;
        }
        
        $value = floatval($value);
        return $value >= $min && $value <= $max;
    }
    
    /**
     * Sanitize and validate integer
     * 
     * @param mixed $value Value to validate
     * @param int $min Optional minimum value
     * @param int $max Optional maximum value
     * @return int|false
     */
    public static function sanitize_int($value, $min = null, $max = null) {
        $value = intval($value);
        
        if ($min !== null && $value < $min) {
            return false;
        }
        
        if ($max !== null && $value > $max) {
            return false;
        }
        
        return $value;
    }
    
    /**
     * Sanitize and validate float
     * 
     * @param mixed $value Value to validate
     * @param float $min Optional minimum value
     * @param float $max Optional maximum value
     * @param float $step Optional step value (e.g., 0.5)
     * @return float|false
     */
    public static function sanitize_float($value, $min = null, $max = null, $step = null) {
        $value = floatval($value);
        
        if ($min !== null && $value < $min) {
            return false;
        }
        
        if ($max !== null && $value > $max) {
            return false;
        }
        
        if ($step !== null) {
            $remainder = fmod($value - ($min ?: 0), $step);
            if ($remainder > 0.0001) { // Allow for floating point precision
                return false;
            }
        }
        
        return $value;
    }
    
    /**
     * Check for duplicate entries
     * 
     * @param string $table Table name (without prefix)
     * @param array $conditions WHERE conditions
     * @param int|null $exclude_id ID to exclude from check
     * @return bool True if duplicate exists
     */
    public static function check_duplicate($table, $conditions, $exclude_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $table;
        $where_clauses = [];
        $values = [];
        
        foreach ($conditions as $field => $value) {
            $where_clauses[] = "{$field} = %s";
            $values[] = $value;
        }
        
        $query = "SELECT COUNT(*) FROM {$table_name} WHERE " . implode(' AND ', $where_clauses);
        
        if ($exclude_id !== null) {
            $query .= " AND id != %d";
            $values[] = $exclude_id;
        }
        
        $count = $wpdb->get_var($wpdb->prepare($query, $values));
        
        return $count > 0;
    }
}