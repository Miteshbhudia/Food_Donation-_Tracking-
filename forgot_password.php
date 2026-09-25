<?php
session_start();
require_once "dbconnection.inc.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);

    if (empty($email)) {
        $error = "Please enter your registered email address.";
    } else {
        $found_user_type = null;

        // 1. Check the donors table first
        $stmt_donor = $conn->prepare("SELECT Donor_ID FROM donors WHERE Email_Address = ?");
        $stmt_donor->bind_param("s", $email);
        $stmt_donor->execute();
        $res_donor = $stmt_donor->get_result();

        if ($res_donor && $res_donor->num_rows > 0) {
            $found_user_type = "donor";
        }
        $stmt_donor->close();

        // 2. If not found in donors, check the admin table
        if (!$found_user_type) {
            $stmt_admin = $conn->prepare("SELECT Administrator_ID FROM admin WHERE Email_Address = ?");
            $stmt_admin->bind_param("s", $email);
            $stmt_admin->execute();
            $res_admin = $stmt_admin->get_result();

            if ($res_admin && $res_admin->num_rows > 0) {
                $found_user_type = "admin";
            }
            $stmt_admin->close();
        }

        // 3. Process OTP if account exists
        if ($found_user_type !== null) {
            $otp = rand(100000, 999999);
            $_SESSION['reset_email']     = $email;
            $_SESSION['reset_user_type'] = $found_user_type; // 'donor' or 'admin'
            $_SESSION['reset_otp']       = $otp;
            $_SESSION['otp_expiry']      = time() + 600; // 10 minutes

            // Attempt email dispatch
            $subject = "Your Password Reset OTP - Food Aid System";
            $message = "Hello,\r\n\r\nYour password reset code is: " . $otp . "\r\n\r\nThis code expires in 10 minutes.";
            $headers = "From: noreply@foodtraceability.org\r\nReply-To: noreply@foodtraceability.org";
            @mail($email, $subject, $message, $headers);

            header("Location: reset_password.php");
            exit();
        } else {
            $error = "No registered account found with that email address.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Forgot Password - Food Aid Traceability</title>
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
        <h4 class="fw-bold mb-1">Reset Password</h4>
        <p class="text-muted small mb-4">Enter your registered email address to receive a 6-digit verification code.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small text-start mb-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST" class="text-start">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
            </div>
            <button type="submit" class="btn btn-custom mt-2">Send Verification Code</button>
        </form>

        <div class="mt-4 pt-2 border-top">
            <a href="login_page.html" class="text-decoration-none small text-muted">
                <i class="bi bi-arrow-left me-1"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html>