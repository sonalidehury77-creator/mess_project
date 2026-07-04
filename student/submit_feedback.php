<?php
session_start();
include("../config/db_connect.php");

/* ==========================================================================
   1. DATABASE CONNECTION FALLBACK LATERAL LAYER
   ========================================================================== */
if (!isset($conn)) {
    if (isset($pdo)) {
        $conn = $pdo;
    } elseif (isset($db)) {
        $conn = $db;
    } elseif (isset($con)) {
        $conn = $con;
    }
}

/* ==========================================================================
   2. SECURE AJAX FORM SUBMISSION CONTROLLER
   ========================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['hostel_roll'])) {
        echo json_encode(['success' => false, 'message' => 'Your session expired. Please log in again.']);
        exit();
    }

    $roll      = $_SESSION['hostel_roll'];
    $date      = $_POST['date'] ?? '';
    $meal_type = $_POST['meal_type'] ?? '';
    $rating    = intval($_POST['rating'] ?? 0);
    $comment   = trim($_POST['comment'] ?? '');

    // Validation checks
    if (empty($date) || !in_array($meal_type, ['breakfast', 'lunch', 'dinner'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters selected. Please reload.']);
        exit();
    }

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid rating between 1 and 5 stars.']);
        exit();
    }

    try {
        // Upsert strategy: Saves rating or updates it if they want to revise their input
        $sql = "
            INSERT INTO meal_reviews (hostel_roll, date, meal_type, rating, comment)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), submitted_at = CURRENT_TIMESTAMP
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$roll, $date, $meal_type, $rating, $comment]);

        echo json_encode(['success' => true, 'message' => 'Thank you! Your food review has been successfully saved.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to save to database. Please try again.']);
    }
    exit();
}

// Ensure normal page visits see the HTML interface layout below instead of raw JSON text
if (!isset($_SESSION['hostel_roll'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Daily Mess Feedback</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;1,400&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: #F8FAFC;
            background-image: radial-gradient(#E2E8F0 1.1px, transparent 1.1px);
            background-size: 24px 24px;
            color: #0F172A;
            padding: 30px 24px 60px 24px;
            min-height: 100vh;
        }

        /* PREMIUM INTEGRATED BAR HEADER */
        .top-navbar {
            max-width: 600px;
            margin: 0 auto 32px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #FFFFFF;
            padding: 16px 24px;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 10px -2px rgba(15, 23, 42, 0.02);
        }

        .brand-meta h1 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 600;
            color: #0F172A;
            letter-spacing: -0.01em;
        }

        .brand-meta p {
            font-size: 12px;
            color: #64748B;
            margin-top: 2px;
            font-weight: 500;
        }

        /* PREMIUM RETURN TO DESKTOP BUTTON */
        .btn-desktop-return {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #0F172A;
            color: #FFFFFF;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 600;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }

        .btn-desktop-return:hover {
            background: #334155;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.2);
        }

        .btn-desktop-return svg {
            width: 14px;
            height: 14px;
            transition: transform 0.2s ease;
        }

        .btn-desktop-return:hover svg {
            transform: translateX(-3px);
        }

        /* MAIN APP WRAPPER CARD */
        .feedback-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 32px;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.03), 0 8px 10px -6px rgba(15, 23, 42, 0.03);
        }

        .feedback-title {
            font-size: 18px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .feedback-subtitle {
            font-size: 13px;
            color: #64748B;
            margin-bottom: 26px;
            line-height: 1.5;
        }

        /* INTERFACE SELECTION COMPONENT GRID */
        .select-group {
            margin-bottom: 22px;
        }

        .select-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 8px;
            letter-spacing: 0.05em;
        }

        .dropdown-menu {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #CBD5E1;
            background: #F8FAFC;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #1E293B;
            outline: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dropdown-menu:focus {
            border-color: #3B82F6;
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        /* HOVER INTERACTIVE STARS COMPONENT */
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 6px;
            margin-bottom: 4px;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 36px;
            color: #E2E8F0;
            cursor: pointer;
            transition: color 0.15s ease, transform 0.1s ease;
        }

        .star-rating label:active {
            transform: scale(0.85);
        }

        .star-rating input:checked~label,
        .star-rating label:hover,
        .star-rating label:hover~label {
            color: #F59E0B;
        }

        .comment-box {
            width: 100%;
            height: 110px;
            padding: 12px 14px;
            border: 1px solid #CBD5E1;
            background: #F8FAFC;
            border-radius: 8px;
            font-size: 13.5px;
            color: #1E293B;
            resize: none;
            outline: none;
            line-height: 1.5;
            transition: all 0.2s ease;
        }

        .comment-box:focus {
            border-color: #3B82F6;
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .btn-submit-review {
            width: 100%;
            background: #3B82F6;
            color: #FFFFFF;
            border: none;
            padding: 13px;
            font-weight: 600;
            font-size: 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s ease, transform 0.1s ease;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
            margin-top: 6px;
        }

        .btn-submit-review:hover {
            background: #2563EB;
        }
        
        .btn-submit-review:active {
            transform: scale(0.99);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #64748B;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #0F172A;
        }
    </style>
</head>

<body>

    <header class="top-navbar">
        <div class="brand-meta">
            <h1>Mess Analytics</h1>
            <p>Roll Number: <?php echo htmlspecialchars($_SESSION['hostel_roll']); ?></p>
        </div>
        <a href="dashboard.php" class="btn-desktop-return">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Return to Desktop
        </a>
    </header>

    <div class="feedback-card" id="feedbackContainer">
        <div class="feedback-title">🍽️ Rate Today's Food Quality</div>
        <div class="feedback-subtitle">Help our kitchen team improve tomorrow's recipe and taste preferences.</div>

        <form id="feedbackForm">
            <input type="hidden" name="date" value="<?php echo date('Y-m-d'); ?>">

            <div class="select-group">
                <label>Select Your Meal Cycle</label>
                <select name="meal_type" class="dropdown-menu">
                    <option value="breakfast">Breakfast Selection</option>
                    <option value="lunch" selected>Lunch Menu</option>
                    <option value="dinner">Dinner Distribution</option>
                </select>
            </div>

            <div class="select-group">
                <label>Your Rating Score</label>
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5"><label for="star5">★</label>
                    <input type="radio" id="star4" name="rating" value="4"><label for="star4">★</label>
                    <input type="radio" id="star3" name="rating" value="3"><label for="star3">★</label>
                    <input type="radio" id="star2" name="rating" value="2"><label for="star2">★</label>
                    <input type="radio" id="star1" name="rating" value="1"><label for="star1">★</label>
                </div>
            </div>

            <div class="select-group">
                <label>Add Your Comments (Optional)</label>
                <textarea class="comment-box" name="comment" placeholder="Tell the chef what tasted great or what can be improved..."></textarea>
            </div>

            <button type="submit" class="btn-submit-review">Submit Food Rating</button>
        </form>

    </div>

    <script>
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            if (!formData.get('rating')) {
                alert('Please click on a star score before submitting your form.');
                return;
            }

            fetch('submit_feedback.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('feedbackContainer');
                        container.innerHTML = `
                            <div style="text-align:center; padding: 24px 0;">
                                <div style="font-size: 54px; margin-bottom: 18px;">🎉</div>
                                <div style="color:#10B981; font-weight:700; font-size:18px; margin-bottom: 8px;">Submission Received!</div>
                                <div style="color:#475569; font-size:14px; line-height:1.5; margin-bottom: 28px;">${data.message}</div>
                                <a href="dashboard.php" style="display:inline-block; background:#0F172A; color:#FFF; text-decoration:none; font-weight:600; font-size:13px; padding:12px 24px; border-radius:10px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);">Go to Dashboard</a>
                            </div>`;
                    } else {
                        alert('System Response: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Submission breakdown error:', error);
                    alert('Network error. Could not reach server. Please try again.');
                });
        });
    </script>
</body>

</html>