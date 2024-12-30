<?php
// process_form.php
header('Content-Type: application/json');

// Configuration
$config = [
    'rsvp_recipients' => [
        'edwinmathe@yahoo.co.uk',
        'winfreyb23@yahoo.com'  
    ],
    'question_recipients' => [
        'edwinmathe@yahoo.co.uk', 
        'winfreyb23@yahoo.com' 
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
            $success = false;
        }
    }
    return $success;
}

// RSVP Form Handler
function handle_rsvp_form($data) {
    global $config;
    
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
    
    // Additional validation for guests if attending
    if ($attendance === 'yes' && (!is_numeric($guests) || $guests < 1 || $guests > 4)) {
        throw new Exception("Please enter a valid number of guests (1-4)");
    }
    
    // Prepare email content
    $subject = "Wedding RSVP from $name";
    $message = "New RSVP Submission\n\n";
    $message .= "Name: $name\n";
    $message .= "Email: $email\n";
    $message .= "Attending: " . ($attendance === 'yes' ? 'Yes' : 'No') . "\n";
    
    if ($attendance === 'yes') {
        $message .= "Number of Guests: $guests\n";
    }
    
    if (!empty($dietary)) {
        $message .= "Dietary Requirements: $dietary\n";
    }
    
    $message .= "\nSubmitted on: " . date('Y-m-d H:i:s') . "\n";
    
    // Email headers
    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Send emails
    if (!send_multiple_emails($config['rsvp_recipients'], $subject, $message, $headers)) {
        throw new Exception("Failed to send RSVP email");
    }
    
    return "Thank you for your RSVP! We will send a confirmation email shortly.";
}

// Questions Form Handler
function handle_questions_form($data) {
    global $config;
    
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
    $message .= "Question: $question\n\n";
    $message .= "Submitted on: " . date('Y-m-d H:i:s') . "\n";
    
    // Email headers
    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Send emails
    if (!send_multiple_emails($config['question_recipients'], $subject, $message, $headers)) {
        throw new Exception("Failed to send question email");
    }
    
    return "Thank you for your question! We'll get back to you soon.";
}

// Main form processing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $response = array();
    
    try {
        // Determine form type
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
        
        $response['status'] = 'success';
        $response['message'] = $message;
        
    } catch (Exception $e) {
        $response['status'] = 'error';
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}
?>