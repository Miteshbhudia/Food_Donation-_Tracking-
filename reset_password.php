<?php
session_start();
require_once "dbconnection.inc.php";

// If user hasn't requested an OTP, redirect back
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $entered_otp = trim($_POST["otp"]);

    if (empty($entered_otp)) {
        $error = "Please enter the 6-digit OTP code.";
    } elseif ($entered_otp != $_SESSION['reset_otp']) {
        $error = "Invalid OTP code. Please check and try again.";
    } elseif (time() > $_SESSION['otp_expiry']) {
        $error = "The verification code has expired. Please request a new one.";
    } else {
        // Mark OTP as verified in the session
        $_SESSION['otp_verified'] = true;
        header("Location: new_password.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Verify Code - Food Aid Traceability</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #16a34a;
            --primary-hover: #15803d;
            --dark: #0f172a;
            --bg-light: #f8fafc;
            --card-border: #e2e8f0;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .auth-card {
            max-width: 440px;
            width: 100%;
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            padding: 2.5rem;
        }
        .brand-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1.25rem;
        }
        .brand-title span { color: var(--dark); }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.4rem;
        }
        .otp-input {
            letter-spacing: 0.35rem;
            font-size: 1.35rem;
            text-align: center;
            font-weight: 700;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid var(--card-border);
        }
        .otp-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
        }
        .btn-custom {
            background-color: var(--primary);
            border: none;
            color: #ffffff;
            font-weight: 600;
            padding: 0.75rem;
            border-radius: 8px;
            width: 100%;
            transition: all 0.2s ease;
        }
        .btn-custom:hover {
            background-color: var(--primary-hover);
            color: #ffffff;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="auth-card text-center">
        <a href="homepage.html" class="brand-title">Food<span>Trace</span></a>
        <h4 class="fw-bold mb-1">Verify OTP</h4>
        <p class="text-muted small mb-3">Verification code sent to<br><strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong></p>

        <!-- Academic Demo Helper Banner -->
        <div class="alert alert-info py-2 small mb-3">
            <i class="bi bi-info-circle me-1"></i> Demo OTP: <strong class="fs-6"><?php echo $_SESSION['reset_otp']; ?></strong>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small text-start mb-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="reset_password.php" method="POST" class="text-start">
            <div class="mb-3">
                <label for="otp" class="form-label">6-Digit OTP Code</label>
                <input type="text" maxlength="6" class="form-control otp-input" id="otp" name="otp" placeholder="123456" required autofocus>
            </div>
            <button type="submit" class="btn btn-custom mt-2">Verify Code</button>
        </form>

        <div class="mt-4 pt-2 border-top d-flex justify-content-between">
            <a href="forgot_password.php" class="text-decoration-none small text-muted">Resend Code</a>
            <a href="login_page.html" class="text-decoration-none small text-muted">Back to Login</a>
        </div>
    </div>
</body>
</html>