<?php
include 'db.php';
session_start();

// Lấy ID phim từ URL
$movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$movie = null;
$showtimes_by_date_theater = [];
$theaters = [];

if ($movie_id > 0) {
    // Lấy thông tin phim
    $movie_sql = "SELECT * FROM movies WHERE id = ?";
    $movie_stmt = $conn->prepare($movie_sql);
    $movie_stmt->bind_param("i", $movie_id);
    $movie_stmt->execute();
    $movie_result = $movie_stmt->get_result();
    $movie = $movie_result->fetch_assoc();

    if ($movie) {
        // Lấy danh sách suất chiếu cho phim này, chỉ lấy các suất trong tương lai
        $current_datetime = date('Y-m-d H:i:s');
        $showtimes_sql = "SELECT s.id, s.showtime, s.price, r.id as room_id, r.name as room_name 
                          FROM showtimes s
                          JOIN rooms r ON s.room_id = r.id
                          WHERE s.movie_id = ? AND s.showtime > ?
                          ORDER BY s.showtime ASC";
        $showtimes_stmt = $conn->prepare($showtimes_sql);
        $showtimes_stmt->bind_param("is", $movie_id, $current_datetime);
        $showtimes_stmt->execute();
        $showtimes_result = $showtimes_stmt->get_result();

        while ($row = $showtimes_result->fetch_assoc()) {
            $date = date('Y-m-d', strtotime($row['showtime']));
            $time = date('H:i', strtotime($row['showtime']));
            $room_id = $row['room_id'];
            $room_name = $row['room_name'];

            if (!isset($showtimes_by_date_theater[$date])) {
                $showtimes_by_date_theater[$date] = [];
            }
            if (!isset($showtimes_by_date_theater[$date][$room_id])) {
                $showtimes_by_date_theater[$date][$room_id] = [
                    'name' => $room_name,
                    'times' => []
                ];
                $theaters[$room_id] = $room_name; // Collect unique theaters
            }
            $showtimes_by_date_theater[$date][$room_id]['times'][] = [
                'id' => $row['id'],
                'time' => $time,
                'price' => $row['price']
            ];
        }
        ksort($showtimes_by_date_theater); // Sort dates
        asort($theaters); // Sort theaters by name
    }
}

$first_theater_id = key($theaters);
$first_date = key($showtimes_by_date_theater);

// Default selections (can be updated via JavaScript)
$selected_theater_id = $first_theater_id;
$selected_date = $first_date;
$selected_showtime_id = null;
$selected_showtime_time = null;
$selected_theater_name = isset($theaters[$selected_theater_id]) ? $theaters[$selected_theater_id] : null;

// Find the first showtime for the default selections to pre-fill summary card
if (isset($showtimes_by_date_theater[$selected_date][$selected_theater_id]['times'][0])) {
    $selected_showtime_id = $showtimes_by_date_theater[$selected_date][$selected_theater_id]['times'][0]['id'];
    $selected_showtime_time = $showtimes_by_date_theater[$selected_date][$selected_theater_id]['times'][0]['time'];
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $movie ? $movie['title'] : 'Phim không tồn tại'; ?> - CGV Cinemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #1a1a1a;
            color: #fff;
            font-family: 'Arial', sans-serif;
        }
        .container-fluid {
            padding-top: 30px;
        }
        .movie-poster {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        h2, h3, h4 {
            color: #ffc107; /* Warning color from Bootstrap */
        }
        .btn-outline-light {
            color: #fff;
            border-color: #fff;
        }
        .btn-outline-light:hover,
        .btn-outline-light.active {
            background-color: #ffc107;
            color: #1a1a1a;
            border-color: #ffc107;
        }
        .date-selector .btn,
        .time-selector .btn {
             color: #1a1a1a;
             background-color: #fff;
             border-color: #fff;
             margin-right: 10px;
             margin-bottom: 10px;
        }
         .date-selector .btn.active,
         .time-selector .btn.active {
            background-color: #28a745; /* Success color from Bootstrap */
            color: #fff;
            border-color: #28a745;
        }
        .summary-card {
            background-color: #333;
            border-radius: 8px;
            padding: 20px;
        }
        .summary-card h4 {
            color: #fff;
            margin-bottom: 15px;
        }
        .summary-card p {
            margin-bottom: 5px;
        }
        .proceed-btn {
            background-color: #28a745; /* Success color */
            border-color: #28a745;
            color: #fff;
            font-size: 1.2rem;
            padding: 10px 20px;
            width: 100%;
        }
         .proceed-btn:hover {
             background-color: #218838;
             border-color: #1e7e34;
             color: #fff;
         }
         .info-section {
             margin-bottom: 30px;
             padding-bottom: 20px;
             border-bottom: 1px solid #444;
         }
    </style>
</head>
<body>

<div class="container-fluid">
    <?php if ($movie): ?>
        <div class="row">
            <!-- Left Column: Theater, Date, Time Selection -->
            <div class="col-md-8">
                <div class="info-section">
                    <h2>Theater</h2>
                    <div class="theater-selector mt-3">
                        <?php foreach ($theaters as $room_id => $room_name): ?>
                            <button type="button" class="btn btn-outline-light theater-btn" data-room-id="<?php echo $room_id; ?>">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($room_name); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="info-section">
                    <h2>Date</h2>
                    <div class="date-selector mt-3">
                        <?php foreach ($showtimes_by_date_theater as $date => $theaters_data): ?>
                             <?php // Only show dates if the selected theater has showtimes on this date ?>
                             <?php if (isset($theaters_data[$selected_theater_id])): ?>
                                <button type="button" class="btn date-btn" data-date="<?php echo $date; ?>">
                                    <?php echo date('d M', strtotime($date)); ?><br>
                                    <?php echo date('D', strtotime($date)); // Day of week ?>
                                </button>
                             <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="info-section">
                    <h2>Time</h2>
                    <div class="time-selector mt-3">
                        <?php
                        // Display times for the initially selected theater and date
                        if (isset($showtimes_by_date_theater[$selected_date][$selected_theater_id]['times'])) {
                            foreach ($showtimes_by_date_theater[$selected_date][$selected_theater_id]['times'] as $showtime) {
                                echo '<button type="button" class="btn time-btn" data-showtime-id="' . $showtime['id'] . '">' . $showtime['time'] . '</button>';
                            }
                        }
                        ?>
                    </div>
                </div>

            </div>

            <!-- Right Column: Movie Info and Summary -->
            <div class="col-md-4">
                <img src="<?php echo htmlspecialchars($movie['poster']); ?>" class="movie-poster mb-3" alt="<?php echo htmlspecialchars($movie['title']); ?> Poster">
                <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                <p>Movie description here...</p> <!-- Placeholder for description -->
                <p><strong>Duration:</strong> <?php echo htmlspecialchars($movie['duration']); ?> mins</p>
                <p><strong>Thể loại:</strong> <?php echo htmlspecialchars($movie['genre']); ?></p>

                <div class="summary-card mt-4">
                    <h4><?php echo htmlspecialchars($selected_theater_name); ?></h4>
                    <p><?php echo date('d F Y', strtotime($selected_date)); ?></p>
                    <p><?php echo htmlspecialchars($selected_showtime_time); ?></p>
                    <p class="mt-3">*Seat selection can be done after this</p>
                    <a href="select_seats.php?showtime_id=<?php echo $selected_showtime_id?>" id="proceed-link" class="btn proceed-btn mt-3 <?php echo $selected_showtime_id ? '' : 'disabled'; ?>" <?php echo $selected_showtime_id ? 'data-showtime-id="' . $selected_showtime_id . '"' : ''; ?>>Proceed</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">Không tìm thấy phim này.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const theaterButtons = document.querySelectorAll('.theater-btn');
        const dateButtons = document.querySelectorAll('.date-btn');
        const timeButtons = document.querySelectorAll('.time-btn');
        const summaryCard = document.querySelector('.summary-card');
        const proceedLink = document.getElementById('proceed-link');

        let selectedTheaterId = '<?php echo $selected_theater_id; ?>';
        let selectedDate = '<?php echo $selected_date; ?>';
        let selectedShowtimeId = '<?php echo $selected_showtime_id; ?>';

        // Initial active states
        setActiveButton('.theater-btn', '[data-room-id="' + selectedTheaterId + '"]');
        setActiveButton('.date-btn', '[data-date="' + selectedDate + '"]');
         if (selectedShowtimeId) {
             setActiveButton('.time-btn', '[data-showtime-id="' + selectedShowtimeId + '"]');
         }

        function setActiveButton(selector, targetSelector) {
            document.querySelectorAll(selector).forEach(btn => btn.classList.remove('active'));
            const targetButton = document.querySelector(targetSelector);
            if (targetButton) {
                targetButton.classList.add('active');
            }
        }

        function updateDateButtons() {
             // Hide all date buttons initially
            document.querySelectorAll('.date-selector .btn').forEach(btn => btn.style.display = 'none');

            // Show only dates that have showtimes for the selected theater
            const showtimesData = <?php echo json_encode($showtimes_by_date_theater); ?>;
            if (showtimesData[selectedDate] && showtimesData[selectedDate][selectedTheaterId]) {
                // Date is valid for the new theater, keep it
            } else {
                // Date is not valid, select the first available date for this theater
                for (const date in showtimesData) {
                    if (showtimesData[date][selectedTheaterId]) {
                        selectedDate = date;
                        break;
                    }
                }
            }
             // If no date found for the selected theater, reset date and showtime
             if (!selectedDate || !showtimesData[selectedDate] || !showtimesData[selectedDate][selectedTheaterId]) {
                 selectedDate = null;
                 selectedShowtimeId = null;
                 updateTimeButtons(); // Clear time buttons
                 updateSummary(); // Clear summary
                 return; // Stop if no valid date
             }

             // Update active date button
             setActiveButton('.date-btn', '[data-date="' + selectedDate + '"]');

            // Show relevant date buttons
            for (const date in showtimesData) {
                 if (showtimesData[date][selectedTheaterId]) {
                     document.querySelector('.date-btn[data-date="' + date + '"]').style.display = 'block';
                 }
            }
             // After updating date buttons, update time buttons based on the new selected date
             updateTimeButtons();
        }


        function updateTimeButtons() {
            const timeSelectorDiv = document.querySelector('.time-selector');
            timeSelectorDiv.innerHTML = ''; // Clear current time buttons

            const showtimesData = <?php echo json_encode($showtimes_by_date_theater); ?>;
            let times = [];
            if (showtimesData[selectedDate] && showtimesData[selectedDate][selectedTheaterId]) {
                 times = showtimesData[selectedDate][selectedTheaterId]['times'];
            }

            if (times.length > 0) {
                 times.forEach(showtime => {
                     const button = document.createElement('button');
                     button.type = 'button';
                     button.classList.add('btn', 'time-btn');
                     button.dataset.showtimeId = showtime.id;
                     button.textContent = showtime.time;
                     timeSelectorDiv.appendChild(button);
                 });
                 // Set the first time as active by default for the new selection
                 selectedShowtimeId = times[0].id;
                 setActiveButton('.time-btn', '[data-showtime-id="' + selectedShowtimeId + '"]');
            } else {
                selectedShowtimeId = null; // No showtimes available for this date/theater
            }

             // Re-attach event listeners to new time buttons
            document.querySelectorAll('.time-btn').forEach(button => {
                button.addEventListener('click', handleTimeClick);
            });

            updateSummary();
        }

        function updateSummary() {
            const showtimesData = <?php echo json_encode($showtimes_by_date_theater); ?>;
            const theatersData = <?php echo json_encode($theaters); ?>;

            const selectedTheaterName = theatersData[selectedTheaterId] || 'Chọn rạp';
            let selectedShowtimeTime = 'Chọn suất chiếu';
            let proceedLinkHref = '#';
            let proceedLinkDisabled = true;

            if (selectedShowtimeId && showtimesData[selectedDate] && showtimesData[selectedDate][selectedTheaterId]) {
                const showtimesForDateTheater = showtimesData[selectedDate][selectedTheaterId]['times'];
                const selectedShowtime = showtimesForDateTheater.find(st => st.id == selectedShowtimeId);
                if (selectedShowtime) {
                    selectedShowtimeTime = selectedShowtime.time;
                     // Link to the next page, e.g., seat selection
                     proceedLinkHref = 'select_seats.php?showtime_id=' + selectedShowtimeId;
                     proceedLinkDisabled = false;
                }
            }

            summaryCard.innerHTML = `
                <h4>${selectedTheaterName}</h4>
                <p>${selectedDate ? new Date(selectedDate).toLocaleDateString('vi-VN', { day: '2-digit', month: 'long', year: 'numeric' }) : 'Chọn ngày'}</p>
                <p>${selectedShowtimeTime}</p>
                <p class="mt-3">*Seat selection can be done after this</p>
                <a href="${proceedLinkHref}" id="proceed-link-summary" class="btn proceed-btn mt-3 ${proceedLinkDisabled ? 'disabled' : ''}">Proceed</a>
            `;
             // Update the global proceedLink variable and re-attach listener if needed
             const newProceedLink = summaryCard.querySelector('#proceed-link-summary');
             if (newProceedLink) {
                 proceedLink.href = newProceedLink.href;
                 if (proceedLinkDisabled) {
                      proceedLink.classList.add('disabled');
                 } else {
                      proceedLink.classList.remove('disabled');
                 }
             }
        }

        function handleTheaterClick(event) {
            selectedTheaterId = event.target.dataset.roomId;
            setActiveButton('.theater-btn', '[data-room-id="' + selectedTheaterId + '"]');
            updateDateButtons(); // Update date buttons based on new theater
            // The call to updateTimeButtons and updateSummary is now inside updateDateButtons
        }

        function handleDateClick(event) {
            selectedDate = event.target.dataset.date;
            setActiveButton('.date-btn', '[data-date="' + selectedDate + '"]');
            updateTimeButtons(); // Update time buttons based on new date
        }

        function handleTimeClick(event) {
            selectedShowtimeId = event.target.dataset.showtimeId;
            setActiveButton('.time-btn', '[data-showtime-id="' + selectedShowtimeId + '"]');
            updateSummary(); // Update summary based on new time
        }

        // Add event listeners
        theaterButtons.forEach(button => {
            button.addEventListener('click', handleTheaterClick);
        });

        // Date buttons need to have listeners added after updateDateButtons
         document.querySelectorAll('.date-selector').addEventListener('click', function(event) {
             if (event.target.classList.contains('date-btn')) {
                 handleDateClick(event);
             }
         });

        // Time buttons need to have listeners added after updateTimeButtons
        document.querySelectorAll('.time-selector').addEventListener('click', function(event) {
            if (event.target.classList.contains('time-btn')) {
                 handleTimeClick(event);
             }
        });

        // Initial update
        if (selectedTheaterId && selectedDate) {
             updateDateButtons(); // This will call updateTimeButtons and updateSummary
        } else if (selectedTheaterId) {
             updateDateButtons(); // Find first available date for the theater
        } else if (selectedDate) {
             // This case is unlikely to be the initial state if no theater is selected
             updateTimeButtons(); // This will call updateSummary
        } else {
             updateSummary(); // Update summary with default/empty states
        }

    });
</script>

</body>
</html>

<?php
$conn->close();
?>
