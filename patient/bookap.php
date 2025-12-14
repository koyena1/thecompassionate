<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* --- 1. CSS VARIABLES & RESET --- */
        :root {
            --bg-color: #F5F6FA;
            --sidebar-width: 240px;
            --primary-purple: #7B61FF;
            --primary-red: #FF5C60;
            --primary-orange: #FFB800;
            --primary-blue: #1FB6FF;
            --text-dark: #2D3436;
            --text-light: #A0A4A8;
            --white: #FFFFFF;
            --shadow: 0 4px 15px rgba(0,0,0,0.03);
            --radius: 20px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden; 
        }

        /* --- 2. SIDEBAR --- */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--white);
            padding: 30px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
            z-index: 999;
            transition: transform 0.3s ease;
            left: 0;
            top: 0;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 50px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 5px;
            transition: 0.3s;
            font-size: 14px;
        }

        .menu-item:hover, .menu-item.active {
            background-color: var(--text-dark);
            color: var(--white);
        }

        /* --- 3. MAIN CONTENT --- */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px 40px;
            transition: margin-left 0.3s ease;
            width: 100%;
        }

        /* --- TOGGLE LOGIC --- */
        body.toggled .sidebar {
            transform: translateX(-100%);
        }
        body.toggled .main-content {
            margin-left: 0;
        }

        #toggle-btn {
            font-size: 24px;
            cursor: pointer;
            margin-right: 20px;
            color: var(--text-dark);
            transition: 0.3s;
            display: block;
        }
        #toggle-btn:hover {
            color: var(--primary-blue);
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .search-bar {
            background: var(--white);
            padding: 10px 20px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 300px;
            color: var(--text-light);
            box-shadow: var(--shadow);
        }

        .search-bar input {
            border: none;
            outline: none;
            width: 100%;
            color: var(--text-dark);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .icon-btn {
            background: var(--white);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            box-shadow: var(--shadow);
            cursor: pointer;
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-info img {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            object-fit: cover;
        }

        /* Welcome Text */
        .welcome-container {
            display: flex;
            align-items: center;
        }

        .welcome-text h1 {
            font-size: 24px;
            font-weight: 600;
        }
        .welcome-text p {
            color: var(--text-light);
            font-size: 14px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Card Hover Effects */
        .card {
            padding: 25px;
            border-radius: var(--radius);
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .card-icon {
            background: rgba(255,255,255,0.2);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }

        .card:hover .card-icon {
            transform: rotate(15deg);
        }

        .card-info h2 { font-size: 28px; }
        .card-info span { font-size: 13px; opacity: 0.9; }

        .card.purple { background: var(--primary-purple); }
        .card.red { background: var(--primary-red); }
        .card.orange { background: var(--primary-orange); }
        .card.blue { background: var(--primary-blue); }

        /* Dashboard Lower Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr;
            gap: 25px;
        }

        .panel {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .panel-header h3 { font-size: 18px; }
        .view-all { color: var(--primary-blue); font-size: 12px; cursor: pointer; text-decoration: none; }

        /* Request Items */
        .request-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            transition: all 0.5s ease;
        }

        .request-item.fade-out-confirm {
            background-color: rgba(31, 182, 255, 0.1);
            opacity: 0;
            transform: translateX(20px);
            padding: 10px;
            border-radius: 10px;
        }
        
        /* Helper to Hide Sections */
        .hidden-section {
            display: none !important;
        }
        
        .hidden {
            display: none !important;
        }

        .patient-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .patient-info img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
        }

        .patient-text h4 { font-size: 14px; margin-bottom: 2px; }
        .patient-text p { font-size: 11px; color: var(--text-light); }

        .confirm { color: var(--primary-blue); background: rgba(31, 182, 255, 0.1); padding: 5px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; transition: 0.3s;}
        .confirm:hover { background: var(--primary-blue); color: white; }

        /* Gender Chart Container */
        .chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
            position: relative;
            height: 200px; 
            width: 100%;
        }

        /* Calendar Widget */
        .mini-calendar { text-align: center; }
        
        .calendar-dates {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 15px;
            font-size: 12px;
            color: var(--text-light);
        }
        
        .date-num { 
            padding: 8px; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: 0.3s;
        }

        .date-num:hover {
            background-color: #f0f0f0; 
        }
        
        .date-num.active {
            background-color: var(--primary-blue);
            color: var(--white);
            box-shadow: 0 4px 10px rgba(31, 182, 255, 0.4);
        }
        .date-num.active:hover {
            background-color: var(--primary-blue);
        }

        /* --- NEW: BOOKING FORM STYLES --- */
        .booking-container {
            background: var(--white);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: var(--shadow);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #eee;
            border-radius: 10px;
            outline: none;
            background: #FAFAFA;
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
        }

        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--primary-blue);
            background: #fff;
        }

        .full-width {
            grid-column: span 2;
        }

        .book-btn {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            transition: 0.3s;
        }

        .book-btn:hover {
            background: #0d9adb;
        }

        .success-message {
            text-align: center;
            padding: 40px;
            display: none; /* Hidden by default */
        }

        .success-icon {
            font-size: 50px;
            color: #00B69B;
            margin-bottom: 20px;
        }

        .appt-id {
            background: #E6F7F0;
            color: #00B69B;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
            margin: 10px 0;
            font-weight: 600;
        }

        /* --- 4. RESPONSIVE MEDIA QUERIES --- */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            body.toggled .sidebar {
                transform: translateX(0);
            }
            body.toggled .main-content {
                margin-left: 0; 
                opacity: 0.5;
                pointer-events: none;
            }
            .search-bar {
                width: 100%;
                order: 3;
                margin-top: 15px;
            }
            .user-profile {
                margin-left: auto;
            }
            .profile-info div {
                display: none;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .form-grid {
                grid-template-columns: 1fr; /* Stack form on mobile */
            }
            .full-width {
                grid-column: span 1;
            }
        }

    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div class="welcome-container">
                <i class="fa-solid fa-bars" id="toggle-btn"></i>
                <div class="welcome-text">
                    <h1>Welcome, Alex</h1>
                    <p>Track your health journey here</p>
                </div>
            </div>
            
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search Doctors, Appointments...">
            </div>

            <div class="user-profile">
                <div class="icon-btn"><i class="fa-regular fa-bell"></i></div>
                <div class="profile-info">
                    <div style="text-align: right;">
                        <h4 style="font-size: 14px;">Alex Morgan</h4>
                        <p style="font-size: 11px; color: var(--text-light);">Patient</p>
                    </div>
                    <img src="https://i.pravatar.cc/150?img=33" alt="Patient Profile">
                </div>
            </div>
        </header>

        <div id="overview-section">
            <section class="stats-grid">
                <div class="card purple">
                    <div class="card-icon"><i class="fa-regular fa-calendar"></i></div>
                    <div class="card-info">
                        <h2>04</h2>
                        <span>Upcoming Visits</span>
                    </div>
                </div>
                <div class="card red">
                    <div class="card-icon"><i class="fa-solid fa-file-medical"></i></div>
                    <div class="card-info">
                        <h2>12</h2>
                        <span>Prescriptions</span>
                    </div>
                </div>
                <div class="card orange">
                    <div class="card-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                    <div class="card-info">
                        <h2>Good</h2>
                        <span>Health Status</span>
                    </div>
                </div>
                <div class="card blue">
                    <div class="card-icon"><i class="fa-solid fa-wallet"></i></div>
                    <div class="card-info">
                        <h2>$120</h2>
                        <span>Pending Bill</span>
                    </div>
                </div>
            </section>

            <section class="dashboard-grid">
                <div class="panel">
                    <div class="panel-header">
                        <h3>Recent Notifications</h3>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <div id="request-list">
                        <div class="request-item">
                            <div class="patient-info">
                                <img src="https://i.pravatar.cc/150?img=11" alt="">
                                <div class="patient-text">
                                    <h4>Dr. Stephen</h4>
                                    <p>Added new prescription</p>
                                </div>
                            </div>
                            <span style="font-size: 12px; color: var(--text-light);">10:00 AM</span>
                        </div>
                        <div class="request-item">
                            <div class="patient-info">
                                <img src="https://i.pravatar.cc/150?img=5" alt="">
                                <div class="patient-text">
                                    <h4>Appointment Confirmed</h4>
                                    <p>With Dr. Savannah</p>
                                </div>
                            </div>
                            <button class="confirm" style="font-size: 10px;">View</button>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h3>Health Stats</h3>
                        <a href="#" class="view-all">Weekly <i class="fa-solid fa-chevron-down"></i></a>
                    </div>
                    <div class="chart-container">
                        <canvas id="genderChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item"><div class="dot" style="background: var(--primary-orange);"></div> Activity</div>
                        <div class="legend-item"><div class="dot" style="background: var(--primary-blue);"></div> Sleep</div>
                        <div class="legend-item"><div class="dot" style="background: var(--primary-purple);"></div> Water</div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h3>Upcoming Schedule</h3>
                        <a href="#" class="view-all">See All</a>
                    </div>
                    <div id="today-appointments">
                        <div class="request-item">
                            <div class="patient-info">
                                <img src="https://i.pravatar.cc/150?img=59" alt="">
                                <div class="patient-text">
                                    <h4>Dr. Frank</h4>
                                    <p>General Checkup</p>
                                </div>
                            </div>
                            <span style="font-size: 12px; color: var(--primary-blue); background: rgba(31, 182, 255, 0.1); padding: 4px 8px; border-radius: 4px;">Today</span>
                        </div>
                    </div>
                    <div style="margin-top: 30px;">
                        <div style="display:flex; justify-content: space-between; font-weight: 600; font-size: 14px;">
                            <span>May 2024</span>
                            <div><i class="fa-solid fa-chevron-left"></i> <i class="fa-solid fa-chevron-right"></i></div>
                        </div>
                        <div class="calendar-dates">
                            <div class="date-num header">S</div><div class="date-num header">M</div><div class="date-num header">T</div><div class="date-num header">W</div><div class="date-num header">T</div><div class="date-num header">F</div><div class="date-num header">S</div>
                            <div class="date-num">3</div><div class="date-num">4</div><div class="date-num">5</div><div class="date-num active">6</div><div class="date-num">7</div><div class="date-num">8</div><div class="date-num">9</div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div id="booking-section" class="hidden-section">
            <h2 style="margin-bottom: 20px;">Book a New Appointment</h2>
            
            <div class="booking-container">
                <form id="appointmentForm">
                    <div class="form-grid">
                        
                        <div class="form-group">
                            <label>Select Date</label>
                            <input type="date" required>
                        </div>
                        <div class="form-group">
                            <label>Available Timeslot</label>
                            <select required>
                                <option value="" disabled selected>Select Time</option>
                                <option>09:00 AM - 10:00 AM</option>
                                <option>10:00 AM - 11:00 AM</option>
                                <option>11:00 AM - 12:00 PM</option>
                                <option>02:00 PM - 03:00 PM</option>
                                <option>04:00 PM - 05:00 PM</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" placeholder="Alex Morgan" required>
                        </div>
                        <div class="form-group">
                            <label>Age</label>
                            <input type="number" placeholder="28" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" placeholder="+1 234 567 890" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" placeholder="alex@example.com" required>
                        </div>

                        <div class="form-group full-width">
                            <label>Health Issue (Short Description)</label>
                            <textarea rows="4" placeholder="Describe your symptoms..."></textarea>
                        </div>

                        <div class="form-group full-width" style="display: flex; justify-content: space-between; align-items: center; background: #F5F6FA; padding: 15px; border-radius: 10px;">
                            <div>
                                <h4 style="font-size: 14px;">Appointment Fee</h4>
                                <p style="font-size: 11px; color: var(--text-light);">Consultation Charge</p>
                            </div>
                            <h3 style="color: var(--primary-blue);">$50.00</h3>
                        </div>

                        <div class="full-width">
                            <button type="submit" class="book-btn">Confirm & Pay</button>
                        </div>
                    </div>
                </form>

                <div id="bookingSuccess" class="success-message">
                    <div class="success-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <h3>Appointment Booked Successfully!</h3>
                    <p style="color: var(--text-light); margin-bottom: 10px;">A confirmation email has been sent to you.</p>
                    
                    <div>Your Appointment ID:</div>
                    <div class="appt-id" id="generatedID">#APT-0000</div>
                    
                    <button class="book-btn" onclick="location.reload()" style="background: #2D3436; margin-top: 20px;">Back to Dashboard</button>
                </div>
            </div>
        </div>

    </main>

    <script>
        // --- 1. SIDEBAR NAVIGATION LOGIC ---
        const navOverview = document.getElementById('nav-overview');
        const navBooking = document.getElementById('nav-booking');
        const overviewSection = document.getElementById('overview-section');
        const bookingSection = document.getElementById('booking-section');
        const allMenuItems = document.querySelectorAll('.menu-item');

        // Function to handle switching
        function switchSection(activeItem, showSection, hideSection) {
            // Update Menu Active State
            allMenuItems.forEach(item => item.classList.remove('active'));
            activeItem.classList.add('active');

            // Show/Hide Sections
            showSection.classList.remove('hidden-section');
            hideSection.classList.add('hidden-section');
        }

        navOverview.addEventListener('click', (e) => {
            e.preventDefault();
            switchSection(navOverview, overviewSection, bookingSection);
        });

        navBooking.addEventListener('click', (e) => {
            e.preventDefault();
            switchSection(navBooking, bookingSection, overviewSection);
        });

        // --- 2. BOOKING FORM LOGIC ---
        const appointmentForm = document.getElementById('appointmentForm');
        const bookingSuccess = document.getElementById('bookingSuccess');
        const generatedID = document.getElementById('generatedID');

        appointmentForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Stop page reload

            // Simulate processing...
            const randomID = 'APT-' + Math.floor(1000 + Math.random() * 9000);
            generatedID.textContent = '#' + randomID;

            // Hide Form, Show Success
            appointmentForm.style.display = 'none';
            bookingSuccess.style.display = 'block';
        });

        // --- 3. EXISTING DASHBOARD LOGIC (Sidebar toggle, Charts, Calendar) ---
        const toggleBtn = document.getElementById('toggle-btn');
        const body = document.body;
        toggleBtn.addEventListener('click', () => { body.classList.toggle('toggled'); });

        // Search
        const searchInput = document.getElementById('searchInput');
        const allRequestItems = document.querySelectorAll('.request-item');
        searchInput.addEventListener('keyup', function(e) {
            const searchText = e.target.value.toLowerCase();
            allRequestItems.forEach(item => {
                const name = item.querySelector('h4').textContent.toLowerCase();
                const details = item.querySelector('p').textContent.toLowerCase();
                if (name.includes(searchText) || details.includes(searchText)) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        });

        // Calendar
        const dateNums = document.querySelectorAll('.date-num');
        dateNums.forEach(date => {
            date.addEventListener('click', function() {
                if(!isNaN(this.innerText)) {
                    dateNums.forEach(d => d.classList.remove('active'));
                    this.classList.add('active');
                }
            });
        });

        // Chart
        const ctx = document.getElementById('genderChart').getContext('2d');
        const genderChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Activity', 'Sleep', 'Water'],
                datasets: [{
                    data: [60, 25, 15],
                    backgroundColor: ['#FFB800', '#1FB6FF', '#7B61FF'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%', 
                plugins: { legend: { display: false }, tooltip: { enabled: true } }
            }
        });
    </script>
</body>
</html>