<?php
session_start();
require_once 'dbconnection.inc.php';

$error_msg   = '';$success_msg = '';

// Retrieve target email from session or query parameter
$email =$_SESSION['reset_email'] ?? $_SESSION['Email'] ?? $_GET['email'] ?? null;

// ==========================================
// 1. HANDLE PASSWORD UPDATE SUBMISSION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {$target_email = trim($_POST['email'] ?? $email ?? '');
    $new_password =$_POST['new_password'] ?? '';
    $cpassword    =$_POST['confirm_password'] ?? '';

    // Server-side validation
    if (empty($target_email)) {$error_msg = "Password reset session expired. Please request a new link.";
    } elseif (strlen($new_password) < 8) {$error_msg = "Password must be at least 8 characters in length.";
    } elseif ($new_password !== $cpassword) {$error_msg = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);$updated = false;

        // Try updating in donors table
        $stmt_donor =$conn->prepare("UPDATE `donors` SET `Password` = ? WHERE `Email_Address` = ?");
        $stmt_donor->bind_param("ss", $hashed_password, $target_email);$stmt_donor->execute();
        if ($stmt_donor->affected_rows > 0) {$updated = true;
        }
        $stmt_donor->close();

        // If not found in donors, try admin table
        if (!$updated) {
            $stmt_admin =$conn->prepare("UPDATE `admin` SET `Password` = ? WHERE `Email_Address` = ?");
            $stmt_admin->bind_param("ss", $hashed_password, $target_email);$stmt_admin->execute();
            if ($stmt_admin->affected_rows > 0) {$updated = true;
            }
            $stmt_admin->close();
        }

        if ($updated) {
            // Clear temporary reset session variable
            unset($_SESSION['reset_email']);
            header("Location: login_page.html?reset=success");
            exit();
        } else {
            $error_msg = "Unable to locate account record for " . htmlspecialchars($target_email);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Set New Password - Food Aid Traceability System</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #16a34a;
            --primary-hover: #15803d;
            --primary-subtle: #dcfce7;
            --dark: #0f172a;
            --gray-body: #64748b;
            --bg-light: #f8fafc;
            --card-border: #e2e8f0;
        }

        * { box-sizing: border-box; }

        html, body {
            height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--dark);
            overflow-x: hidden;
        }

        .auth-layout {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            justify-content: center;
            align-items: center;
            padding: 1.5rem 1rem;
            background: radial-gradient(circle at 50% 30%, rgba(220, 252, 231, 0.45) 0%, rgba(248, 250, 252, 1) 70%);
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
            padding: 2rem 2.25rem;
        }

        .brand-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            letter-spacing: -0.5px;
            display: inline-block;
            margin-bottom: 0.75rem;
        }
        .brand-title span { color: var(--dark); }

        .auth-header h3 {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--dark);
        }

        .auth-header p {
            font-size: 0.8rem;
            color: var(--gray-body);
            margin-bottom: 1.25rem;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.35rem;
            display: block;
        }

        .form-control {
            border: 1px solid var(--card-border);
            border-radius: 8px;
            padding: 0.6rem 0.85rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
        }

        .input-group-password {
            position: relative;
        }
        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: var(--gray-body);
            cursor: pointer;
            padding: 0;
            font-size: 1rem;
            z-index: 5;
        }

        /* Password Strength Progress Bar */
        .strength-bar-wrapper {
            height: 4px;
            background-color: #e2e8f0;
            border-radius: 4px;
            margin: 6px 0 10px 0;
            overflow: hidden;
        }
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        /* Criteria Checklist */
        .criteria-list {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
            display: grid;
            grid-template-columns: 1fr;
            gap: 4px;
        }
        .criteria-item {
            font-size: 0.76rem;
            color: var(--gray-body);
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }
        .criteria-item i {
            font-size: 0.85rem;
            transition: transform 0.2s ease;
        }
        .criteria-item.met {
            color: var(--primary);
            font-weight: 600;
        }
        .criteria-item.met i {
            color: var(--primary);
        }

        /* Match Status Feedback */
        .match-feedback {
            font-size: 0.775rem;
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: 600;
        }

        .btn-submit {
            background-color: var(--primary);
            border: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.7rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            width: 100%;
            margin-top: 0.75rem;
        }
        .btn-submit:hover:not(:disabled) {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }
        .btn-submit:disabled {
            background-color: #94a3b8;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .auth-footer {
            margin-top: 1rem;
            font-size: 0.8rem;
            color: var(--gray-body);
            text-align: center;
        }
        .auth-footer a {
            color: var(--gray-body);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .auth-footer a:hover {
            color: var(--dark);
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="auth-layout">
        <div class="auth-card">
            
            <div class="text-center">
                <a href="homepage.html" class="brand-title">Food<span>Trace</span></a>
                <div class="auth-header">
                    <h3>Set New Password</h3>
                    <p>Create a secure password to protect your account.</p>
                </div>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger py-2 small mb-3 text-center">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="new_password.php" id="passwordForm">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">

                <!-- Field 1: New Password -->
                <div class="mb-2">
                    <label class="form-label" for="newPassword">New Password</label>
                    <div class="input-group-password">
                        <input type="password" class="form-control" id="newPassword" name="new_password" placeholder="Create strong password" required autofocus>
                        <button type="button" class="password-toggle-btn" onclick="togglePass('newPassword', 'icon1')" aria-label="Toggle view">
                            <i class="bi bi-eye" id="icon1"></i>
                        </button>
                    </div>
                    
                    <!-- Strength Bar -->
                    <div class="strength-bar-wrapper">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>

                    <!-- Live Criteria Checklist -->
                    <ul class="criteria-list">
                        <li class="criteria-item" id="crit-length">
                            <i class="bi bi-circle" id="icon-length"></i> At least 8 characters
                        </li>
                        <li class="criteria-item" id="crit-number">
                            <i class="bi bi-circle" id="icon-number"></i> Contains at least 1 number (0-9)
                        </li>
                        <li class="criteria-item" id="crit-upper">
                            <i class="bi bi-circle" id="icon-upper"></i> Contains at least 1 uppercase letter (A-Z)
                        </li>
                    </ul>
                </div>

                <!-- Field 2: Confirm Password -->
                <div class="mb-3">
                    <label class="form-label" for="confirmPassword">Confirm Password</label>
                    <div class="input-group-password">
                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Repeat your new password" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePass('confirmPassword', 'icon2')" aria-label="Toggle view">
                            <i class="bi bi-eye" id="icon2"></i>
                        </button>
                    </div>
                    <!-- Live Match Status -->
                    <div id="matchStatus" class="match-feedback d-none"></div>
                </div>

                <button type="submit" name="update_password" class="btn btn-submit" id="btnSubmit" disabled>
                    Update Password
                </button>
            </form>

            <div class="auth-footer">
                <a href="login_page.html"><i class="bi bi-arrow-left me-1"></i> Cancel and Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        const newPassInput = document.getElementById('newPassword');
        const confPassInput = document.getElementById('confirmPassword');
        const submitBtn = document.getElementById('btnSubmit');
        const strengthBar = document.getElementById('strengthBar');
        const matchStatus = document.getElementById('matchStatus');

        // Checklist elements
        const critLength = document.getElementById('crit-length');
        const critNumber = document.getElementById('crit-number');
        const critUpper = document.getElementById('crit-upper');

        const iconLength = document.getElementById('icon-length');
        const iconNumber = document.getElementById('icon-number');
        const iconUpper = document.getElementById('icon-upper');

        // Listeners for live evaluation
        newPassInput.addEventListener('input', validateAll);
        confPassInput.addEventListener('input', validateAll);

        function validateAll() {
            const pwd = newPassInput.value;
            const conf = confPassInput.value;

            // 1. Check Criteria
            const hasLength = pwd.length >= 8;
            const hasNumber = /\d/.test(pwd);
            const hasUpper  = /[A-Z]/.test(pwd);

            updateCriterion(critLength, iconLength, hasLength);
            updateCriterion(critNumber, iconNumber, hasNumber);
            updateCriterion(critUpper, iconUpper, hasUpper);

            // 2. Calculate Strength Score (0 to 3)
            let score = 0;
            if (hasLength) score++;
            if (hasNumber) score++;
            if (hasUpper) score++;

            if (pwd.length === 0) {
                strengthBar.style.width = '0%';
            } else if (score === 1) {
                strengthBar.style.width = '33%';
                strengthBar.style.backgroundColor = '#ef4444'; // Red
            } else if (score === 2) {
                strengthBar.style.width = '66%';
                strengthBar.style.backgroundColor = '#f59e0b'; // Amber
            } else if (score === 3) {
                strengthBar.style.width = '100%';
                strengthBar.style.backgroundColor = '#16a34a'; // Emerald Green
            }

            const allCriteriaMet = hasLength && hasNumber && hasUpper;

            // 3. Check Confirmation Match
            let passwordsMatch = false;

            if (conf.length > 0) {
                matchStatus.classList.remove('d-none');
                if (pwd === conf && allCriteriaMet) {
                    passwordsMatch = true;
                    matchStatus.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> <span class="text-success">Passwords match</span>';
                    confPassInput.style.borderColor = '#16a34a';
                } else if (pwd === conf && !allCriteriaMet) {
                    matchStatus.innerHTML = '<i class="bi bi-info-circle text-warning"></i> <span class="text-warning">Passwords match, but criteria unmet</span>';
                    confPassInput.style.borderColor = '#f59e0b';
                } else {
                    matchStatus.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i> <span class="text-danger">Passwords do not match</span>';
                    confPassInput.style.borderColor = '#ef4444';
                }
            } else {
                matchStatus.classList.add('d-none');
                confPassInput.style.borderColor = '#e2e8f0';
            }

            // 4. Toggle Submit Button State
            if (allCriteriaMet && passwordsMatch) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        function updateCriterion(elem, iconElem, isMet) {
            if (isMet) {
                elem.classList.add('met');
                iconElem.className = 'bi bi-check-circle-fill text-success';
            } else {
                elem.classList.remove('met');
                iconElem.className = 'bi bi-circle text-muted';
            }
        }

        // Show/Hide Password Feature
        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
    </script>
</body>

</html>
<?php
$conn->close();
?>