<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['movie_id']) || !isset($_POST['showtime_id']) || !isset($_POST['selected_seats'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$movie_id = $_POST['movie_id'];
$showtime_id = $_POST['showtime_id'];
$selected_seats = explode(',', $_POST['selected_seats']);
$total_price = count($selected_seats) * 85000; 

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert booking
    $sql = "INSERT INTO bookings (user_id, movie_id, showtime_id, total_price, booking_date) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiid", $user_id, $movie_id, $showtime_id, $total_price);
    $stmt->execute();
    $booking_id = $conn->insert_id;

    // Insert booked seats
    $sql = "INSERT INTO booked_seats (booking_id, seat_number) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    foreach ($selected_seats as $seat) {
        $stmt->bind_param("is", $booking_id, $seat);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    // Redirect to success page
    header('Location: booking_success.php?booking_id=' . $booking_id);
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    die("Lỗi đặt vé: " . $e->getMessage());
}
?> 