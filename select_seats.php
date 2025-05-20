<?php
include 'db.php';
session_start();

// Lấy showtime_id từ URL
$showtime_id = isset($_GET['showtime_id']) ? intval($_GET['showtime_id']) : 0;

$showtime = null;
$booked_seats = [];

if ($showtime_id > 0) {
    // Lấy thông tin suất chiếu, phim và phòng
    $sql = "SELECT s.id, s.showtime, s.price, 
            m.title as movie_title, m.poster, m.duration, m.genre,
            r.id as room_id, r.name as room_name, r.rows, r.cols
            FROM showtimes s
            JOIN movies m ON s.movie_id = m.id
            JOIN rooms r ON s.room_id = r.id
            WHERE s.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $showtime_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $showtime = $result->fetch_assoc();

    if ($showtime) {
        // Lấy các ghế đã đặt cho suất chiếu này
        $booked_seats_sql = "SELECT seat_number FROM booked_seats WHERE showtime_id = ?";
        $booked_seats_stmt = $conn->prepare($booked_seats_sql);
        $booked_seats_stmt->bind_param("i", $showtime_id);
        $booked_seats_stmt->execute();
        $booked_seats_result = $booked_seats_stmt->get_result();
        while ($row = $booked_seats_result->fetch_assoc()) {
            $booked_seats[] = $row['seat_number'];
        }
    }
}

// Ghế mẫu (ví dụ, bạn nên lấy từ DB)
$seat_rows = isset($showtime['rows']) ? $showtime['rows'] : 8; // Số hàng ghế
$seat_cols = isset($showtime['cols']) ? $showtime['cols'] : 10; // Số cột ghế
$alphabet = range('A', 'Z');

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chọn Ghế - <?php echo $showtime ? $showtime['movie_title'] : 'CGV Cinemas'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #1a1a1a;
            color: #fff;
            font-family: 'Arial', sans-serif;
            padding-bottom: 80px; /* Add padding to the bottom for the fixed summary */
        }
        .container-fluid {
            padding-top: 30px;
        }
        h2, h3, h4 {
            color: #ffc107; /* Warning color */
            text-align: center; /* Center headings */
        }
        .movie-info-header {
            text-align: center;
            margin-bottom: 30px;
        }
         .movie-info-header h4 {
             margin-bottom: 5px;
         }
         .movie-info-header p {
             margin-bottom: 5px;
             color: #ccc;
         }
        .seat-map {
            display: flex; /* Use flexbox for layout */
            flex-direction: column; /* Stack rows vertically */
            align-items: center; /* Center rows horizontally */
            margin: 20px auto;
            border: 1px solid #444;
            padding: 20px;
            background-color: #333;
            border-radius: 8px;
             width: fit-content; /* Adjust width to content */
        }
        .screen {
            background-color: #555;
            color: #fff;
            text-align: center;
            padding: 10px 0;
            margin-bottom: 20px;
            border-radius: 4px;
            width: 100%; /* Make screen width follow seat map */
        }
        .seat-row {
            display: flex;
            justify-content: center; /* Center seats in the row */
            margin-bottom: 8px; /* Space between rows */
            align-items: center; /* Vertically align row label and seats */
        }
        .row-label {
            width: 30px; /* Adjust width */
            text-align: center;
            margin-right: 15px; /* Space between label and seats */
            font-weight: bold;
            color: #ffc107; /* Warning color */
        }
        .seat-container {
            display: flex; /* Use flex for seats within a row */
        }
        .seat {
            width: 35px; /* Slightly larger */
            height: 35px; /* Square shape */
            margin: 0 4px; /* Spacing between seats */
            background-color: #ddd; /* Light gray for available */
            border: none; /* Remove border */
            border-radius: 5px; /* Rounded corners */
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.9em; /* Slightly larger font */
            user-select: none;
            color: #1a1a1a; /* Dark text for light seats */
            font-weight: bold;
            transition: background-color 0.2s ease;
        }
        .seat.booked {
            background-color: #f00; /* Red for booked */
            cursor: not-allowed;
            color: #fff;
        }
        .seat.selected {
            background-color: #28a745; /* Green for selected */
            color: #fff;
        }
         .seat.booked:hover {
             background-color: #f00; /* Keep red on hover */
         }
        .seat:not(.booked):hover {
            background-color: #ffc107; /* Yellow on hover for available */
        }
        .legend {
            margin-top: 20px;
            text-align: center;
             color: #ccc;
        }
        .legend-item {
            display: inline-flex;
            align-items: center;
            margin: 0 15px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 8px;
        }
        .summary-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #222; /* Slightly darker than seat map */
            color: #fff;
            padding: 15px 0;
            z-index: 1000;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
        }
        .summary-footer .container-fluid {
             padding-top: 0;
        }
        .summary-footer .btn {
            font-size: 1.1rem;
            padding: 8px 20px;
        }
         .summary-footer p {
             margin-bottom: 0;
             font-size: 1em;
         }
         .summary-info {
             font-weight: bold;
             color: #ffc107;
         }
    </style>
</head>
<body>

<div class="container-fluid">
    <?php if ($showtime): ?>
        <div class="row">
            <div class="col-md-12 text-center mb-4">
                <h2>Chọn Ghế</h2>
                <h4>Phim: <?php echo htmlspecialchars($showtime['movie_title']); ?></h4>
                <p>Rạp: <?php echo htmlspecialchars($showtime['room_name']); ?> - Thời gian: <?php echo date('d/m/Y H:i', strtotime($showtime['showtime'])); ?></p>
                 <p>Giá vé mỗi ghế: <?php echo number_format($showtime['price'], 0, ',', '.') . ' VNĐ'; ?></p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-auto">
                <div class="seat-map">
                    <div class="screen">Màn hình</div>
                    <?php for ($i = 0; $i < $seat_rows; $i++): ?>
                        <div class="seat-row">
                            <div class="row-label"><?php echo $alphabet[$i]; ?></div>
                            <?php for ($j = 1; $j <= $seat_cols; $j++): ?>
                                <?php $seat_number = $alphabet[$i] . $j; ?>
                                <div class="seat <?php echo in_array($seat_number, $booked_seats) ? 'booked' : ''; ?>" 
                                     data-seat-number="<?php echo $seat_number; ?>">
                                    <?php echo $j; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Seat Legend -->
        <div class="row justify-content-center mt-3">
             <div class="col-auto">
                 <div class="d-flex align-items-center">
                     <div class="seat" style="background-color: #ddd; cursor: default;">1</div>
                     <span class="ms-2 me-4">Trống</span>
                     <div class="seat booked" style="cursor: default;">1</div>
                     <span class="ms-2 me-4">Đã đặt</span>
                     <div class="seat selected" style="cursor: default;">1</div>
                     <span class="ms-2">Đã chọn</span>
                 </div>
             </div>
        </div>


    <?php else: ?>
        <div class="alert alert-danger">Không tìm thấy thông tin suất chiếu.</div>
    <?php endif; ?>
</div>

<!-- Fixed Summary Footer -->
<div class="summary-footer">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-4 text-center text-md-start">
                <p class="mb-0">Ghế đã chọn: <span id="selected-seats-count">0</span></p>
                <p class="mb-0">Tổng tiền: <span id="total-price">0 VNĐ</span></p>
            </div>
            <div class="col-md-4 text-center mt-2 mt-md-0">
                <!-- Movie/Showtime Info can be added here if needed -->
                 <p class="mb-0"><strong><?php echo htmlspecialchars($showtime['movie_title']); ?></strong> at <?php echo date('H:i', strtotime($showtime['showtime'])); ?></p>
            </div>
            <div class="col-md-4 text-center text-md-end mt-2 mt-md-0">
                <button id="proceed-to-payment" class="btn btn-success" disabled>Tiếp tục</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const seats = document.querySelectorAll('.seat:not(.booked)');
        const selectedSeatsCountSpan = document.getElementById('selected-seats-count');
        const totalPriceSpan = document.getElementById('total-price');
        const proceedButton = document.getElementById('proceed-to-payment');
        const seatPrice = <?php echo $showtime ? $showtime['price'] : 0; ?>;
        const selectedSeats = new Set();

        seats.forEach(seat => {
            seat.addEventListener('click', function() {
                const seatNumber = this.dataset.seatNumber;
                if (selectedSeats.has(seatNumber)) {
                    selectedSeats.delete(seatNumber);
                    this.classList.remove('selected');
                } else {
                    selectedSeats.add(seatNumber);
                    this.classList.add('selected');
                }
                updateSummary();
            });
        });

        function updateSummary() {
            selectedSeatsCountSpan.textContent = selectedSeats.size;
            const total = selectedSeats.size * seatPrice;
            totalPriceSpan.textContent = total.toLocaleString('vi-VN') + ' VNĐ';

            if (selectedSeats.size > 0) {
                proceedButton.disabled = false;
                 // Set the link for the next step (e.g., payment)
                // You will need to create a payment/confirmation page (e.g., checkout.php)
                // and pass the selected seats and showtime_id to it.
                // Example:
                // proceedButton.onclick = function() { 
                //     const seatsArray = Array.from(selectedSeats);
                //     window.location.href = 'checkout.php?showtime_id=<?php echo $showtime_id; ?>&seats=' + seatsArray.join(',');
                // };

                 // For now, just enable the button
                 proceedButton.disabled = false;
            } else {
                proceedButton.disabled = true;
                 // Reset the onclick if it was set
                 // proceedButton.onclick = null;
            }
        }
         
        // Add event listener for the proceed button (handle the next step)
         proceedButton.addEventListener('click', function() {
             if (selectedSeats.size > 0) {
                 const seatsArray = Array.from(selectedSeats);
                 // Redirect to the next page, passing selected seats and showtime ID
                 window.location.href = 'checkout.php?showtime_id=<?php echo $showtime_id; ?>&seats=' + seatsArray.join(',');
             }
         });

    });
</script>

</body>
</html>

<?php
$conn->close();
?>