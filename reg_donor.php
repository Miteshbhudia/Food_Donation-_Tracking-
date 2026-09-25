<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Food Aid Traceability System - Donor Registration</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Food Distribution System, Register, Donor Registration, New Account" name="keywords">
    <meta content="Register as a new donor to the Food Aid Traceability System and help deliver aid transparently." name="description">

    <link href="img/favicon.ico" rel="icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
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

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .brand-header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--card-border);
            padding: 1.25rem 0;
        }

        .brand-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        .brand-title span {
            color: var(--dark);
        }

        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid var(--card-border);
        }
        .navbar-nav .nav-link {
            color: var(--gray-body);
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        .navbar-nav .nav-link:hover {
            color: var(--primary);
        }
        .navbar-nav .nav-link.active {
            color: var(--primary);
            font-weight: 600;
            background-color: var(--primary-subtle);
        }

        .registration-wrapper {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            padding: 3.5rem 0;
        }

        .registration-card {
            max-width: 680px;
            width: 100%;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
            padding: 2.75rem;
        }

        .registration-card h2 {
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.35rem;
        }

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
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
        }

        .register-btn {
            background-color: var(--primary);
            border: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.85rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .register-btn:hover {
            background-color: var(--primary-hover);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .password-criteria {
            list-style: none;
            padding-left: 0;
            margin-top: 0.6rem;
            margin-bottom: 0;
            font-size: 0.825rem;
        }
        .password-criteria li {
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            color: #94a3b8;
            transition: color 0.2s ease;
        }
        .password-criteria li i {
            margin-right: 0.45rem;
            font-size: 0.85rem;
        }
        .password-criteria li.valid {
            color: var(--primary);
            font-weight: 500;
        }
        .password-criteria li.invalid {
            color: #ef4444;
        }

        #confirmPasswordFeedback {
            font-size: 0.825rem;
            margin-top: 0.45rem;
            font-weight: 500;
        }

        .auth-footer-links {
            font-size: 0.875rem;
            margin-top: 1.75rem;
            text-align: center;
        }
        .auth-footer-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        .auth-footer-links a:hover {
            text-decoration: underline;
        }

        .site-footer {
            background-color: var(--dark);
            color: #94a3b8;
            font-size: 0.9rem;
            padding-top: 3.5rem;
            padding-bottom: 1.5rem;
            flex-shrink: 0;
        }
        .site-footer h5 {
            color: #ffffff;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 1.25rem;
        }
        .site-footer a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.2s;
        }
        .site-footer a:hover {
            color: var(--primary);
        }
    </style>
</head>

<body>
    <div class="brand-header text-center">
        <div class="container">
            <a href="homepage.html" class="brand-title">Food<span>Trace</span></a>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg">
        <div class="container justify-content-center">
            <div class="navbar-nav flex-row gap-2">
                <a href="homepage.html" class="nav-link">Home</a>
                <a href="login_page.html" class="nav-link">Login</a>
                <a href="reg_donor.php" class="nav-link active">Register</a>
            </div>
        </div>
    </nav>

    <div class="registration-wrapper">
        <div class="container">
            <div class="registration-card">
                <div class="text-center mb-4">
                    <h2>Register as a Donor</h2>
                    <p class="text-muted small mb-0">Join our verified aid network to pledge and track food consignments.</p>
                </div>
                
                <form method="POST" action="insertion.inc.php" class="row g-3" id="registrationForm">
                    <!-- Explicit hidden indicator ensures payload recognition -->
                    <input type="hidden" name="reg" value="1">

                    <div class="col-md-6">
                        <label for="fullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="fullname" placeholder="e.g. John Doe" required name="fname">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" placeholder="+254 7XX XXX XXX" required name="phone">
                    </div>
                    
                    <div class="col-12">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" placeholder="name@example.com" required name="email">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" placeholder="Create a strong password" required name="password">
                        <ul class="password-criteria" id="passwordCriteria">
                            <li id="lengthCheck"><i class="fa fa-circle"></i> At least 8 characters long</li>
                            <li id="uppercaseCheck"><i class="fa fa-circle"></i> At least one uppercase letter</li>
                            <li id="specialCharCheck"><i class="fa fa-circle"></i> At least one special symbol (!@#$%^&*)</li>
                        </ul>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="confirm-password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm-password" placeholder="Confirm your password" required name="cpassword">
                        <div id="confirmPasswordFeedback"></div>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <button class="btn register-btn w-100" type="submit" id="submitBtn">Create Donor Account</button>
                    </div>
                </form>
                
                <div class="auth-footer-links">
                    <span>Already registered? </span><a href="login_page.html">Login here</a>
                    <span class="mx-2 text-muted">&bull;</span>
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5>Get In Touch</h5>
                    <p class="mb-1"><i class="bi bi-geo-alt me-2 text-success"></i>Nairobi, KENYA</p>
                    <p class="mb-1"><i class="bi bi-envelope me-2 text-success"></i>tarirai.makoni@strathmore.edu</p>
                    <p class="mb-0"><i class="bi bi-telephone me-2 text-success"></i>0745603353</p>
                </div>
                <div class="col-lg-4 col-md-6">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-1"><a href="homepage.html"><i class="bi bi-chevron-right me-1 small"></i>Home</a></li>
                        <li class="mb-1"><a href="login_page.html"><i class="bi bi-chevron-right me-1 small"></i>Login</a></li>
                        <li class="mb-1"><a href="reg_donor.php"><i class="bi bi-chevron-right me-1 small"></i>Register as Donor</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-12">
                    <h5>System Mission</h5>
                    <p class="small">Enhancing last-mile accountability in food aid distribution through cryptographic QR code consignment verification and immutable audit telemetry.</p>
                </div>
            </div>
            <div class="text-center pt-4 mt-4 border-top border-secondary">
                <p class="mb-0 small text-muted">&copy; Food Aid Traceability System. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const submitBtn = document.getElementById('submitBtn');
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm-password');
            
            const lengthCheck = document.getElementById('lengthCheck');
            const uppercaseCheck = document.getElementById('uppercaseCheck');
            const specialCheck = document.getElementById('specialCharCheck');
            const confirmFeedback = document.getElementById('confirmPasswordFeedback');

            function updateRule(element, isValid) {
                const icon = element.querySelector('i');
                if (isValid) {
                    element.className = 'valid';
                    icon.className = 'fa fa-check-circle';
                } else {
                    element.className = 'invalid';
                    icon.className = 'fa fa-times-circle';
                }
            }

            function validate() {
                const pass = passwordInput.value;
                const confirm = confirmInput.value;

                const hasLength = pass.length >= 8;
                const hasUpper = /[A-Z]/.test(pass);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(pass);

                updateRule(lengthCheck, hasLength);
                updateRule(uppercaseCheck, hasUpper);
                updateRule(specialCheck, hasSpecial);

                const passwordMeetsRules = hasLength && hasUpper && hasSpecial;

                if (confirm.length > 0) {
                    if (confirm === pass && passwordMeetsRules) {
                        confirmFeedback.textContent = 'Passwords match.';
                        confirmFeedback.style.color = 'var(--primary)';
                    } else if (confirm !== pass) {
                        confirmFeedback.textContent = 'Passwords do not match.';
                        confirmFeedback.style.color = '#ef4444';
                    } else {
                        confirmFeedback.textContent = '';
                    }
                } else {
                    confirmFeedback.textContent = '';
                }

                return passwordMeetsRules && (pass === confirm);
            }

            passwordInput.addEventListener('input', validate);
            confirmInput.addEventListener('input', validate);

            let isSubmitting = false;
            form.addEventListener('submit', function(e) {
                if (!validate()) {
                    e.preventDefault();
                    alert('Please ensure your password satisfies all criteria and passwords match.');
                    return;
                }

                if (isSubmitting) {
                    e.preventDefault();
                    return;
                }

                isSubmitting = true;
                // Delay visual disable to allow native form submission payload capture
                setTimeout(function() {
                    submitBtn.style.pointerEvents = 'none';
                    submitBtn.style.opacity = '0.75';
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Creating Account...';
                }, 10);
            });
        });
    </script>
</body>

</html>