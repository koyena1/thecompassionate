<?php
// Get booked time slots for a specific date
include '../config/db.php';

header('Content-Type: application/json');

if (isset($_GET['date'])) {
    $date = $_GET['date'];
    
    // Get all booked time slots for the selected date with confirmed or paid appointments
    // Also normalize the time format to HH:MM (remove seconds if present)
    $stmt = $conn->prepare("
        SELECT DISTINCT TIME_FORMAT(appointment_time, '%H:%i') as appointment_time 
        FROM appointments 
        WHERE appointment_date = ? 
        AND (payment_status = 'paid' OR status = 'Confirmed')
    ");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked_slots = [];
    while ($row = $result->fetch_assoc()) {
        $booked_slots[] = $row['appointment_time'];
    }
    
    echo json_encode([
        'success' => true,
        'booked_slots' => $booked_slots,
        'date' => $date,
        'count' => count($booked_slots)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Date parameter required'
    ]);
}
?>
