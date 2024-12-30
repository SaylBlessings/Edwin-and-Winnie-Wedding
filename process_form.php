<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure we're sending JSON response
header('Content-Type: application/json');

// Log function for debugging
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, 'form_errors.log');
}

// Configuration
$config = [
    'rsvp_recipients' => [
        'sayiwelcome@gmail.com',
        'welcomeblessingssayi@gmail.com'  
    ],
    'question_recipients' => [
        'sayiwelcome@gmail.com', 
        'welcomeblessingssayi@gmail.com' 
    ]
];

// Utility functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function send_multiple_emails($recipients, $subject, $message, $headers) {
    $success = true;
    foreach ($recipients as $recipient) {
        if (!mail($recipient, $subject, $message, $headers)) {
            logError("Failed to send email to: " . $recipient);
            $success = false;
        }
    }
    return $success;
}

// RSVP Form Handler
function handle_rsvp_form($data) {
    global $config;
    
    try {
        // Log incoming data
        logError("RSVP Form Data: " . print_r($data, true));
        
        // Required fields validation
        $required_fields = ['name', 'email', 'attendance'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Required field '$field' is missing");
            }
        }
        
        // Sanitize inputs
        $name = sanitize_input($data['name']);
        $email = sanitize_input($data['email']);
        $attendance = sanitize_input($data['attendance']);
        $guests = isset($data['guests']) ? sanitize_input($data['guests']) : '';
        $dietary = isset($data['dietary']) ? sanitize_input($data['dietary']) : '';
        
        // Email validation
        if (!is_valid_email($email)) {
            throw new Exception("Invalid email address");
        }
        
        // Prepare email content
        $subject = "Wedding RSVP from $name";
        $message = "New RSVP Submission\n\n";
        $message .= "Name: $name\n";
        $message .= "Email: $email\n";
        $message .= "Attending: " . ($attendance === 'yes' ? 'Yes' : 'No') . "\n";
        
        if ($attendance === 'yes' && !empty($guests)) {
            $message .= "Number of Guests: $guests\n";
        }
        
        if (!empty($dietary)) {
            $message .= "Dietary Requirements: $dietary\n";
        }
        
        // Email headers
        $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Send emails
        if (!send_multiple_emails($config['rsvp_recipients'], $subject, $message, $headers)) {
            throw new Exception("Failed to send RSVP email");
        }
        
        return "Thank you for your RSVP! We will send a confirmation email shortly.";
    } catch (Exception $e) {
        logError("RSVP Form Error: " . $e->getMessage());
        throw $e;
    }
}

// Questions Form Handler
function handle_questions_form($data) {
    global $config;
    
    try {
        // Log incoming data
        logError("Question Form Data: " . print_r($data, true));
        
        // Required fields validation
        $required_fields = ['name', 'email', 'question'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Required field '$field' is missing");
            }
        }
        
        // Sanitize inputs
        $name = sanitize_input($data['name']);
        $email = sanitize_input($data['email']);
        $question = sanitize_input($data['question']);
        
        // Email validation
        if (!is_valid_email($email)) {
            throw new Exception("Invalid email address");
        }
        
        // Prepare email content
        $subject = "Wedding Question from $name";
        $message = "New Question Submission\n\n";
        $message .= "Name: $name\n";
        $message .= "Email: $email\n";
        $message .= "Question: $question\n";
        
        // Email headers
        $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Send emails
        if (!send_multiple_emails($config['question_recipients'], $subject, $message, $headers)) {
            throw new Exception("Failed to send question email");
        }
        
        return "Thank you for your question! We'll get back to you soon.";
    } catch (Exception $e) {
        logError("Question Form Error: " . $e->getMessage());
        throw $e;
    }
}

// Main form processing
try {
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Invalid request method");
    }

    // Log raw POST data
    logError("Raw POST data: " . print_r($_POST, true));
    
    if (!isset($_POST['formType'])) {
        throw new Exception("Form type not specified");
    }
    
    // Process based on form type
    switch ($_POST['formType']) {
        case 'rsvp':
            $message = handle_rsvp_form($_POST);
            break;
            
        case 'question':
            $message = handle_questions_form($_POST);
            break;
            
        default:
            throw new Exception("Invalid form type");
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => $message
    ]);
    
} catch (Exception $e) {
    logError("Main Process Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>