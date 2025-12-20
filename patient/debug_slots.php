<?php
// Debug version - Get booked time slots for a specific date
include '../config/db.php';

header('Content-Type: application/json');

if (isset($_GET['date'])) {
    $date = $_GET['date'];
    
    // Get ALL appointments for the selected date (for debugging)
    $debug_query = "SELECT appointment_id, appointment_time, status, payment_status FROM appointments WHERE appointment_date = '$date'";
    $debug_result = $conn->query($debug_query);
    $all_appointments = [];
    while ($row = $debug_result->fetch_assoc()) {
        $all_appointments[] = $row;
    }
    
    // Get booked time slots (confirmed or paid)
    $stmt = $conn->prepare("SELECT appointment_time FROM appointments WHERE appointment_date = ? AND (payment_status = 'paid' OR status = 'Confirmed')");
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
        'debug_all_appointments' => $all_appointments,
        'debug_query' => "SELECT appointment_time FROM appointments WHERE appointment_date = '$date' AND (payment_status = 'paid' OR status = 'Confirmed')"
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Date parameter required'
    ]);
}
?>
