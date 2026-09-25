<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Food Distribution System - Register</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Food Distribution System, Register, Donor Registration, New Account" name="keywords">
    <meta content="Register as a new donor to the Food Distribution System and start making a difference today." name="description">

    <link href="img/favicon.ico" rel="icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="css/style.css" rel="stylesheet">

    <style>
        /* Base Variables - Keep consistent with your main site */
        :root {
            --primary: #34AD54; /* A vibrant, compassionate green */
            --secondary: #FFC107; /* A warm, inviting yellow */
            --dark: #212529; /* Deep, strong text/elements */
            --light: #F8F9FA; /* Soft, clean backgrounds */
            --gray-subtle: #ECECEC; /* Very light gray for subtle contrasts */
        }

        /* Utility for primary RGB to be used in rgba() */
        body {
            --primary-rgb: 52, 173, 84; /* RGB values for #34AD54 */
            font-family: 'Poppins', sans-serif;
            background-color: var(--light); /* Overall light background */
            color: var(--dark);
        }

        /* Topbar Styling */
        .topbar {
            background-color: var(--light) !important; /* Consistent with main site */
            border-bottom: 1px solid var(--gray-subtle); /* Subtle separator */
            color: var(--dark); /* Darker text */
        }
        .topbar .bi {
            color: var(--primary) !important;
        }
        .topbar a.btn-square {
            background-color: var(--primary); /* Consistent social button styling */
            border-color: var(--primary);
            color: white;
            font-size: 1.2rem;
            width: 38px; /* Slightly smaller for topbar */
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        .topbar a.btn-square:hover {
            background-color: #28a745;
            transform: translateY(-2px);
        }

        /* Navbar Styling */
        .navbar {
            background-color: white !important; /* White navbar */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08); /* Soft shadow */
        }
        .navbar-brand h1 .text-primary {
            color: var(--primary) !important;
        }
        .navbar-brand h1 .text-secondary {
            color: var(--secondary) !important;
        }
        .navbar-light .navbar-nav .nav-link {
            color: var(--dark);
            font-weight: 500;
            padding: 0.75rem 1rem;
            position: relative;
        }
        .navbar-light .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 5px;
            width: 0;
            height: 2px;
            background-color: var(--primary);
            transition: width 0.3s ease;
        }
        .navbar-light .navbar-nav .nav-link:hover::after,
        .navbar-light .navbar-nav .nav-link.active::after {
            width: calc(100% - 2rem);
        }
        .navbar .btn-primary, .navbar .btn-secondary { /* Ensure buttons in navbar match primary styles */
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 50px; /* Pill-shaped buttons */
            padding: 0.6rem 1.5rem;
            transition: all 0.3s ease;
        }
        .navbar .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        .navbar .btn-primary:hover {
            background-color: #28a745;
            border-color: #28a745;
            transform: translateY(-1px);
        }
        .navbar .btn-secondary {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: var(--dark);
        }
        .navbar .btn-secondary:hover {
            background-color: #e0a800;
            border-color: #e0a800;
            transform: translateY(-1px);
        }

        /* Removed hero-section styling, as it's no longer needed */
        .hero-section {
            display: none; /* Hide the hero section */
        }

        /* Registration Container Styling */
        .registration-container {
            max-width: 850px; /* Slightly wider for the form fields */
            margin: 4rem auto; /* Adjust top/bottom margin for better spacing */
            padding: 3.5rem; /* More generous padding */
            border-radius: 15px; /* More rounded corners */
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1); /* Deeper, softer shadow */
            background-color: #fff;
        }

        .registration-container .text-center h2 {
            font-size: 2.25rem; /* Larger heading */
            color: var(--primary); /* Primary color for main heading */
            margin-bottom: 0.75rem;
        }
        .registration-container .text-center p.text-muted {
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.6rem;
        }
        
        .form-control {
            border-radius: 8px; /* More rounded input fields */
            padding: 0.85rem 1.2rem; /* Increased padding */
            border: 1px solid #cce; /* Softer border color */
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(var(--primary-rgb), 0.2); /* Softer focus shadow */
        }
        
        .register-btn {
            background-color: var(--primary);
            border-color: var(--primary);
            font-weight: 700; /* Bolder button text */
            font-size: 1.15rem; /* Slightly larger button text */
            border-radius: 10px; /* More rounded buttons */
            padding: 1rem 1.8rem; /* More padding */
            transition: all 0.3s ease;
        }
        
        .register-btn:hover {
            background-color: #28a745;
            border-color: #28a745;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25); /* More prominent shadow on hover */
        }
        
        .login-link {
            font-size: 1rem;
            font-weight: 500;
            margin-top: 1.5rem;
        }
        .login-link a {
            color: var(--primary);
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .login-link a:hover {
            color: #28a745;
            text-decoration: underline;
        }

        .forgot-password-link {
            font-size: 0.95rem;
            margin-top: 0.5rem; /* Closer to the login link */
        }
        .forgot-password-link a {
            color: var(--dark); /* Darker text for more subtle link */
            transition: color 0.3s ease;
        }
        .forgot-password-link a:hover {
            color: var(--primary);
            text-decoration: underline;
        }


        /* Style for password validation error messages */
        .password-error {
            color: #dc3545; /* Bootstrap's danger color */
            font-size: 0.875em;
            margin-top: 0.5rem; /* Increased margin for better visibility */
            display: block; /* Always visible to show validation rules */
            list-style: none; /* Remove bullet points */
            padding-left: 0;
            font-weight: 500;
        }
        .password-error li {
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
        }
        .password-error li.valid {
            color: var(--primary); /* Green for valid criteria */
        }
        .password-error li.invalid {
            color: #dc3545; /* Red for invalid criteria */
        }
        .password-error li i {
            margin-right: 0.5rem;
            width: 1em; /* Fixed width for icon */
            text-align: center;
        }

        /* Footer styling consistency */
        .footer {
            background-color: var(--dark) !important; /* Dark footer for strong contrast */
            color: var(--light);
            padding-top: 4rem;
            padding-bottom: 2rem;
        }

        .footer h4 {
            color: var(--secondary); /* Yellow headings in dark footer */
            margin-bottom: 1.5rem;
        }

        .footer p, .footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: var(--primary);
        }

        .footer .btn.btn-square {
            background-color: var(--primary); /* Green social buttons */
            border-color: var(--primary);
            color: white;
            font-size: 1.2rem;
            width: 45px;
            height: 45px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        .footer .btn.btn-square:hover {
            background-color: #28a745;
            transform: translateY(-2px);
        }

        .footer .bi {
            color: var(--secondary); /* Icons in footer links */
        }
        /* Override for copyright text and link color */
        .container-fluid.bg-dark p.mb-0,
        .container-fluid.bg-dark a.text-secondary {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        .container-fluid.bg-dark a.text-secondary:hover {
            color: var(--primary) !important;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container-fluid px-5 d-none d-lg-block topbar">
        <div class="row gx-5 py-3 align-items-center">
            <div class="col-lg-3">
                <div class="d-flex align-items-center justify-content-start">
                    <i class="bi bi-phone-vibrate fs-1 text-primary me-2"></i>
                    <h2 class="mb-0 text-dark">0745603353</h2>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center justify-content-center">
                    <a href="index.html" class="navbar-brand ms-lg-5">
                        <h1 class="m-0 display-4 text-primary"><span class="text-secondary">Food</span> Distribution</h1>
                    </a>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="d-flex align-items-center justify-content-end">
                    <a class="btn btn-square rounded-circle me-2" href="#"><i class="fab fa-twitter"></i></a>
                    <a class="btn btn-square rounded-circle me-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm py-3 py-lg-0 px-3 px-lg-5">
        <a href="index.html" class="navbar-brand d-flex d-lg-none">
            <h1 class="m-0 display-4 text-primary"><span class="text-secondary">Food</span> Distribution</h1>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav mx-auto py-0">
                <a href="homepage.html" class="nav-item nav-link">Home</a>
                <a href="login.html" class="nav-item nav-link">Login</a>
                <a href="reg_donor.php" class="nav-item nav-link active">Register</a>
            </div>
        </div>
    </nav>
    <div class="container py-5">
        <div class="registration-container">
            <div class="text-center mb-4">
                <h2 class="mb-3">Register as a Donor</h2>
                <p class="text-muted">Join our mission to help communities in need. Simply fill out the form below.</p>
            </div>
            
            <form method="POST" action="insertion.inc.php" class="row g-3" id="registrationForm">
                <div class="col-md-6 mb-3">
                    <label for="fullname" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="fullname" placeholder="Enter your full name" required name="fname">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" placeholder="+254 7XX XXX XXX" required name="phone">
                </div>
                
                <div class="col-md-12 mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" placeholder="your.email@example.com" required name="email">
                </div>
                
                <div class="col-md-12 mb-3">
                    <label for="recovery-question" class="form-label">Password Recovery Question</label>
                    <input type="text" class="form-control" id="recovery-question" placeholder="Example: What's your mother's maiden name?" required name="rq">
                </div>
                
                <div class="col-md-12 mb-3">
                    <label for="recovery-answer" class="form-label">Password Recovery Answer</label>
                    <input type="text" class="form-control" id="recovery-answer" placeholder="Your answer" required name="ra">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" placeholder="Choose a strong password" required name="password">
                    <ul id="passwordError" class="password-error">
                        <li id="lengthCheck"><i class="fa fa-times-circle"></i> At least 8 characters long</li>
                        <li id="uppercaseCheck"><i class="fa fa-times-circle"></i> At least one uppercase letter</li>
                        <li id="specialCharCheck"><i class="fa fa-times-circle"></i> At least one special character (!@#$%^&*)</li>
                    </ul>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="confirm-password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm-password" placeholder="Confirm your password" required name="cpassword">
                    <div id="confirmPasswordError" class="password-error"></div>
                </div>
                
                <div class="col-12 mt-4">
                    <button class="btn btn-primary register-btn w-100 py-3" type="submit" name="reg">Register</button>
                </div>
            </form>
            
            <div class="text-center mt-4 login-link">
                Already have an account? <a href="login.html" class="text-primary">Login here</a>
            </div>
            
            <div class="text-center mt-2 forgot-password-link">
                <a href="recover_u_1.php" class="text-muted">Forgot your password?</a>
            </div>
        </div>
    </div>

    <div class="container-fluid bg-dark text-white py-5 footer">
        <div class="container">
            <div class="row g-5">
                <div id="contact" class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    <h4 class="mb-4">Get In Touch</h4>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-geo-alt text-secondary me-3"></i>
                        <p class="mb-0">Nairobi, KENYA.</p>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-envelope-open text-secondary me-3"></i>
                        <p class="mb-0">tarirai.makoni@strathmore.edu</p>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-telephone text-secondary me-3"></i>
                        <p class="mb-0">0745603353</p>
                    </div>
                    <div class="d-flex pt-2">
                        <a class="btn btn-square me-2" href="#"><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-square me-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    <h4 class="mb-4">Quick Links</h4>
                    <div class="d-flex flex-column justify-content-start">
                        <a class="mb-2" href="index.html"><i class="bi bi-arrow-right me-2"></i>Home</a>
                        <a class="mb-2" href="login.html"><i class="bi bi-arrow-right me-2"></i>Login</a>
                        <a class="mb-2" href="reg_donor.php"><i class="bi bi-arrow-right me-2"></i>Register</a>
                        <a class="mb-2" href="#contact"><i class="bi bi-arrow-right me-2"></i>Contact Us</a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.5s">
                    <h4 class="mb-4">Our Mission</h4>
                    <p class="text-white-50">Dedicated to connecting those who can give with those in need, ensuring vital resources like food and water reach vulnerable communities efficiently and effectively, fostering hope and stability.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid bg-dark text-white-50 py-4 border-top border-secondary">
        <div class="container text-center">
            <p class="mb-0">&copy; <a class="text-secondary fw-bold" href="index.html">Food Distribution System</a>. All Rights Reserved.</p>
        </div>
    </div>
    <a href="#" class="btn btn-primary py-3 fs-4 back-to-top"><i class="bi bi-arrow-up"></i></a>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <script>
        // JavaScript for password validation
        document.addEventListener('DOMContentLoaded', function() {
            const registrationForm = document.getElementById('registrationForm');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm-password');
            
            // Password validation checks elements
            const lengthCheck = document.getElementById('lengthCheck');
            const uppercaseCheck = document.getElementById('uppercaseCheck');
            const specialCharCheck = document.getElementById('specialCharCheck');
            const confirmPasswordError = document.getElementById('confirmPasswordError');

            function validatePassword() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                // Reset all checks
                lengthCheck.className = 'invalid';
                lengthCheck.querySelector('i').className = 'fa fa-times-circle';
                uppercaseCheck.className = 'invalid';
                uppercaseCheck.querySelector('i').className = 'fa fa-times-circle';
                specialCharCheck.className = 'invalid';
                specialCharCheck.querySelector('i').className = 'fa fa-times-circle';
                confirmPasswordError.textContent = '';
                confirmPasswordError.style.display = 'none';

                let allChecksPass = true;

                // Length check
                if (password.length >= 8) {
                    lengthCheck.className = 'valid';
                    lengthCheck.querySelector('i').className = 'fa fa-check-circle';
                } else {
                    allChecksPass = false;
                }

                // Uppercase letter check
                if (/[A-Z]/.test(password)) {
                    uppercaseCheck.className = 'valid';
                    uppercaseCheck.querySelector('i').className = 'fa fa-check-circle';
                } else {
                    allChecksPass = false;
                }

                // Special character check
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                    specialCharCheck.className = 'valid';
                    specialCharCheck.querySelector('i').className = 'fa fa-check-circle';
                } else {
                    allChecksPass = false;
                }

                // Confirm password match
                if (confirmPassword.length > 0 && password !== confirmPassword) {
                    confirmPasswordError.textContent = 'Passwords do not match.';
                    confirmPasswordError.style.display = 'block';
                    allChecksPass = false;
                } else if (confirmPassword.length > 0 && password === confirmPassword && allChecksPass) {
                    confirmPasswordError.textContent = 'Passwords match!';
                    confirmPasswordError.style.display = 'block';
                    confirmPasswordError.style.color = 'var(--primary)'; // Green for success
                } else if (confirmPassword.length === 0) {
                     confirmPasswordError.style.display = 'none'; // Hide if confirm password is empty
                }


                return allChecksPass && (password === confirmPassword);
            }

            passwordInput.addEventListener('keyup', validatePassword);
            confirmPasswordInput.addEventListener('keyup', validatePassword);


            registrationForm.addEventListener('submit', function(event) {
                if (!validatePassword()) {
                    event.preventDefault(); // Prevent form submission if validation fails
                }
            });

            // Initial validation check on page load if fields have content (e.g. browser autofill)
            validatePassword();
        });

        // Simple Back to Top button logic (from your main.js)
        $(window).scroll(function () {
            if ($(this).scrollTop() > 100) {
                $('.back-to-top').fadeIn('slow');
            } else {
                $('.back-to-top').fadeOut('slow');
            }
        });
        $('.back-to-top').click(function () {
            $('html, body').animate({ scrollTop: 0 }, 1500, 'easeInOutExpo');
            return false;
        });
    </script>
</body>

</html>