<?php
// Start session at the VERY TOP
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'nav.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features | QR Violation Recorder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* CSS Variables */
    :root {
        --primary-green: #00c476;
        --primary-dark: #009c5e;
        --primary-light: #34d399;
        --accent-green: #a7f3d0;
        --white: #ffffff;
        --light-bg: #f9fafb;
        --gray-light: #e5e7eb;
        --gray: #6b7280;
        --gray-dark: #374151;
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s ease;
    }

    /* Base Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
        color: var(--gray-dark);
        background-color: var(--light-bg);
        overflow-x: hidden;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .highlight {
        color: var(--primary-green);
        position: relative;
    }

    .highlight::after {
        content: '';
        position: absolute;
        bottom: 2px;
        left: 0;
        width: 100%;
        height: 8px;
        background-color: var(--accent-green);
        z-index: -1;
        opacity: 0.5;
    }

    /* Features Hero */
    .features-hero {
        padding: 100px 0;
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-dark) 100%);
        color: var(--white);
        text-align: center;
    }

    .features-hero h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--white);
    }

    .features-hero p {
        font-size: 1.25rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Features Grid */
    .features-grid-section {
        padding: 100px 0;
        background: var(--light-bg);
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
    }

    .feature-card {
        background: var(--white);
        padding: 40px;
        border-radius: 15px;
        box-shadow: var(--shadow);
        transition: var(--transition);
        border-top: 4px solid var(--primary-green);
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-lg);
    }

    .feature-icon {
        width: 70px;
        height: 70px;
        background: var(--accent-green);
        color: var(--primary-green);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin-bottom: 25px;
    }

    .feature-card h3 {
        font-size: 1.5rem;
        margin-bottom: 15px;
        color: var(--gray-dark);
    }

    .feature-card > p {
        color: var(--gray);
        margin-bottom: 20px;
        line-height: 1.6;
        flex-grow: 1;
    }

    .feature-card ul {
        margin-top: 20px;
        padding-left: 20px;
        list-style: none;
    }

    .feature-card li {
        margin-bottom: 12px;
        color: var(--gray);
        position: relative;
        padding-left: 30px;
        line-height: 1.5;
    }

    .feature-card li:before {
        content: "✓";
        color: var(--primary-green);
        font-weight: bold;
        position: absolute;
        left: 0;
        font-size: 1.1rem;
    }

    /* ISASEC Badge */
    .isasec-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--accent-green);
        color: var(--primary-dark);
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
        margin-top: 10px;
    }

    /* Feature Row for 4 and 6 */
    .feature-row {
        display: flex;
        gap: 30px;
        grid-column: 1 / -1;
    }

    .feature-row .feature-card {
        flex: 1;
        min-width: 0; /* Prevents flex items from overflowing */
    }

    /* Responsive */
    @media (max-width: 992px) {
        .feature-row {
            flex-direction: column;
        }
        
        .features-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .features-hero h1 {
            font-size: 2.5rem;
        }
        
        .features-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .feature-row {
            grid-column: 1;
        }
        
        .feature-card {
            padding: 30px;
        }
    }
    </style>
</head>
<body>
    <section class="features-hero">
        <div class="container">
            <h1>Powerful Features for Modern Schools</h1>
            <p>Discover how QR Violation Recorder transforms campus discipline management</p>
        </div>
    </section>

    <section class="features-grid-section">
        <div class="container">
            <div class="features-grid">
                <!-- Row 1: Features 1-3 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h3>Instant QR Scanning</h3>
                    <p>Scan student QR codes in seconds with our mobile app or dedicated scanners for immediate violation recording.</p>
                    <ul>
                        <li>Real-time scanning technology</li>
                        <li>Bulk QR code generation</li>
                        <li>Custom QR designs for each student</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-camera"></i>
                    </div>
                    <h3>Photo Evidence</h3>
                    <p>Attach photographic evidence directly to violation records for comprehensive documentation and verification.</p>
                    <ul>
                        <li>Automatic timestamp and date recording</li>
                        <li>Secured Photos</li>
                        <li>Secure cloud storage with backup</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Analytics Dashboard</h3>
                    <p>Gain valuable insights through comprehensive analytics and reporting tools for better decision-making.</p>
                    <ul>
                        <li>Real-time violation statistics</li>
                        <li>Pattern and trend analysis</li>
                        <li>All information are based on accurate data</li>
                    </ul>
                </div>
                
                <!-- Row 2: Feature 4 and 6 in separate divs -->
                <div class="feature-row">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3>Real-time Notifications</h3>
                        <p>Instant alerts keep administrators, parents, and students informed about violations immediately.</p>
                        <ul>
                            <li>Email notifications</li>
                            <li>Mobile push notifications</li>
                            <li>In time notifications</li>
                        </ul>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-sync"></i>
                        </div>
                        <h3>Auto-sync & Backup</h3>
                        <p>Automatic data synchronization ensures all records are up-to-date across devices with secure backups.</p>
                        <ul>
                            <li>Real-time sync across all devices</li>
                            <li>Daily automated system backups</li>
                            <li>Disaster recovery protocols</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Row 3: ISASEC Card (centered) -->
                <div class="feature-card" style="grid-column: 1 / -1; max-width: 800px; margin: 0 auto;">
                    <div class="feature-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3>Protected by ISASEC</h3>
                    <p style="text-align: center; margin-bottom: 25px;">Our system is monitored and protected by ISASEC faculty who ensure proper handling of student violations with care and professionalism.</p>
                    <div class="isasec-badge" style="justify-content: center; margin: 20px auto;">
                        <i class="fas fa-shield-alt"></i>
                        ISASEC - Institutional Safety & Security
                    </div>
                    <div style="display: flex; justify-content: center; gap: 40px; margin-top: 25px;">
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; color: var(--primary-green); margin-bottom: 10px;">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div style="font-weight: 600; color: var(--gray-dark);">Faculty-monitored</div>
                            <div style="color: var(--gray); font-size: 0.9rem;">violation handling</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; color: var(--primary-green); margin-bottom: 10px;">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div style="font-weight: 600; color: var(--gray-dark);">Student welfare</div>
                            <div style="color: var(--gray); font-size: 0.9rem;">focused approach</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; color: var(--primary-green); margin-bottom: 10px;">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div style="font-weight: 600; color: var(--gray-dark);">Professional</div>
                            <div style="color: var(--gray); font-size: 0.9rem;">conduct & discretion</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>