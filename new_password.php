<?php
session_start();
require_once "dbconnection.inc.php";

// Guard: User must have verified the OTP first
if (!isset($_SESSION['reset_email']) || empty($_SESSION['otp_verified']) || !isset($_SESSION['reset_user_type'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_password     = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $email  = $_SESSION['reset_email'];
        $type   = $_SESSION['reset_user_type'];

        // Update database table matching your schema
        if ($type === "donor") {
            $stmt = $conn->prepare("UPDATE donors SET Password = ? WHERE Email_Address = ?");
        } else {
            $stmt = $conn->prepare("UPDATE admin SET Password = ? WHERE Email_Address = ?");
        }

        $stmt->bind_param("ss", $hashed, $email);

        if ($stmt->execute()) {
            // Clean up session flags
            unset($_SESSION['reset_otp']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_user_type']);
            unset($_SESSION['otp_verified']);
            unset($_SESSION['otp_expiry']);

            header("Location: login_page.html?reset=success");
            exit();
        } else {
            $error = "Database error while updating password. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Set New Password - Food Aid Traceability</title>
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
        .form-control {
            border: 1px solid var(--card-border);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.925rem;
        }
        .form-control:focus {
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
        <h4 class="fw-bold mb-1">Set New Password</h4>
        <p class="text-muted small mb-4">Please choose a secure password of at least 8 characters.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small text-start mb-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="new_password.php" method="POST" class="text-start">
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="At least 8 characters" required autofocus>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
            </div>
            <button type="submit" class="btn btn-custom mt-2">Update Password</button>
        </form>

        <div class="mt-4 pt-2 border-top text-center">
            <a href="login_page.html" class="text-decoration-none small text-muted">Cancel and Back to Login</a>
        </div>
    </div>
</body>
</html>