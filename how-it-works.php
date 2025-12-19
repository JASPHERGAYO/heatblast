<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How It Works | QR Violation Recorder</title>
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

    /* Hero Section */
    .how-it-works-hero {
        padding: 100px 0;
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-dark) 100%);
        color: var(--white);
        text-align: center;
        margin-bottom: 60px;
    }

    .how-it-works-hero h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--white);
    }

    .how-it-works-hero p {
        font-size: 1.25rem;
        opacity: 0.9;
        max-width: 800px;
        margin: 0 auto;
    }

    /* Steps Section */
    .steps-container {
        padding: 60px 0;
    }

    .section-title {
        text-align: center;
        margin-bottom: 50px;
    }

    .section-title h2 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--gray-dark);
    }

    .section-title p {
        font-size: 1.2rem;
        color: var(--gray);
        max-width: 600px;
        margin: 0 auto;
    }

    .steps-timeline {
        position: relative;
        max-width: 1000px;
        margin: 0 auto;
    }

    .steps-timeline::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 50%;
        width: 4px;
        background: var(--primary-light);
        transform: translateX(-50%);
        z-index: 1;
    }

    .step {
        display: flex;
        align-items: center;
        margin-bottom: 80px;
        position: relative;
        z-index: 2;
    }

    .step:nth-child(even) {
        flex-direction: row-reverse;
    }

    .step-icon {
        width: 100px;
        height: 100px;
        background: var(--white);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: var(--shadow-lg);
        border: 4px solid var(--primary-green);
        position: relative;
        z-index: 3;
        flex-shrink: 0;
    }

    .step-icon i {
        font-size: 2.5rem;
        color: var(--primary-green);
    }

    .step-content {
        flex: 1;
        padding: 30px;
        background: var(--white);
        border-radius: 15px;
        box-shadow: var(--shadow);
        margin: 0 40px;
        position: relative;
    }

    .step-number {
        position: absolute;
        top: -20px;
        left: 20px;
        width: 40px;
        height: 40px;
        background: var(--primary-green);
        color: var(--white);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
    }

    .step-content h3 {
        font-size: 1.5rem;
        margin-bottom: 15px;
        color: var(--gray-dark);
    }

    .step-content p {
        color: var(--gray);
        margin-bottom: 15px;
    }

    .step-features {
        list-style: none;
        margin-top: 15px;
    }

    .step-features li {
        padding: 8px 0;
        padding-left: 25px;
        position: relative;
    }

    .step-features li::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: var(--primary-green);
        font-weight: bold;
    }

    /* Roles Section */
    .roles-section {
        background: var(--accent-green);
        padding: 80px 0;
        margin: 60px 0;
    }

    .roles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-top: 50px;
    }

    .role-card {
        background: var(--white);
        padding: 40px 30px;
        border-radius: 15px;
        text-align: center;
        box-shadow: var(--shadow);
        transition: var(--transition);
    }

    .role-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-lg);
    }

    .role-icon {
        width: 80px;
        height: 80px;
        background: var(--primary-green);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
    }

    .role-icon i {
        font-size: 2rem;
        color: var(--white);
    }

    .role-card h3 {
        font-size: 1.5rem;
        margin-bottom: 15px;
        color: var(--gray-dark);
    }

    .role-card p {
        color: var(--gray);
        line-height: 1.6;
    }

    /* Benefits Section */
    .benefits-section {
        padding: 80px 0;
    }

    .benefits-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        margin-top: 50px;
    }

    .benefit-card {
        background: var(--white);
        padding: 30px;
        border-radius: 15px;
        box-shadow: var(--shadow);
        text-align: center;
        transition: var(--transition);
    }

    .benefit-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .benefit-card i {
        font-size: 2.5rem;
        color: var(--primary-green);
        margin-bottom: 20px;
    }

    .benefit-card h3 {
        font-size: 1.3rem;
        margin-bottom: 15px;
        color: var(--gray-dark);
    }

    .benefit-card p {
        color: var(--gray);
        line-height: 1.6;
    }

    /* ISASEC Highlight */
    .isasec-highlight {
        background: linear-gradient(135deg, var(--primary-dark) 0%, #006b47 100%);
        padding: 60px;
        border-radius: 15px;
        color: var(--white);
        text-align: center;
        margin: 60px auto;
        max-width: 900px;
        position: relative;
        overflow: hidden;
    }

    .isasec-highlight::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none" opacity="0.1"><path d="M0,0 L100,0 L100,100 Z" fill="white"/></svg>');
        background-size: cover;
    }

    .isasec-highlight h3 {
        font-size: 2rem;
        margin-bottom: 20px;
        position: relative;
        z-index: 2;
    }

    .isasec-highlight p {
        font-size: 1.2rem;
        opacity: 0.9;
        max-width: 700px;
        margin: 0 auto;
        position: relative;
        z-index: 2;
    }

    .isasec-badge {
        display: inline-block;
        background: var(--white);
        color: var(--primary-dark);
        padding: 8px 20px;
        border-radius: 25px;
        font-weight: 700;
        margin-top: 20px;
        font-size: 1.1rem;
        position: relative;
        z-index: 2;
    }

    /* FAQ Section */
    .faq-section {
        padding: 80px 0;
        background: var(--light-bg);
    }

    .faq-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .faq-item {
        background: var(--white);
        margin-bottom: 15px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: var(--shadow);
    }

    .faq-question {
        padding: 20px 30px;
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--gray-dark);
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: var(--transition);
    }

    .faq-question:hover {
        background: var(--light-bg);
    }

    .faq-question i {
        transition: var(--transition);
    }

    .faq-answer {
        padding: 0 30px;
        max-height: 0;
        overflow: hidden;
        transition: var(--transition);
        color: var(--gray);
        line-height: 1.6;
    }

    .faq-item.active .faq-answer {
        padding: 20px 30px;
        max-height: 500px;
    }

    .faq-item.active .faq-question i {
        transform: rotate(180deg);
    }

    /* CTA Section */
    .cta-section {
        padding: 80px 0;
        text-align: center;
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-dark) 100%);
        color: var(--white);
    }

    .cta-content {
        max-width: 800px;
        margin: 0 auto;
    }

    .cta-content h2 {
        font-size: 2.5rem;
        margin-bottom: 20px;
    }

    .cta-content p {
        font-size: 1.2rem;
        opacity: 0.9;
        margin-bottom: 30px;
    }

    .cta-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .cta-btn {
        padding: 15px 40px;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition);
        cursor: pointer;
        border: none;
    }

    .cta-btn.primary {
        background: var(--white);
        color: var(--primary-green);
    }

    .cta-btn.primary:hover {
        background: var(--light-bg);
        transform: translateY(-3px);
    }

    .cta-btn.secondary {
        background: transparent;
        color: var(--white);
        border: 2px solid var(--white);
    }

    .cta-btn.secondary:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-3px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .how-it-works-hero h1 {
            font-size: 2.5rem;
        }
        
        .steps-timeline::before {
            left: 30px;
        }
        
        .step {
            flex-direction: column !important;
            margin-bottom: 60px;
        }
        
        .step-icon {
            margin-bottom: 20px;
            align-self: flex-start;
            margin-left: 0;
        }
        
        .step-content {
            margin: 0;
            width: 100%;
        }
        
        .step-number {
            left: 10px;
        }
        
        .isasec-highlight {
            padding: 40px 20px;
        }
        
        .cta-buttons {
            flex-direction: column;
            align-items: center;
        }
        
        .cta-btn {
            width: 100%;
            max-width: 300px;
        }
    }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <!-- Hero Section -->
    <section class="how-it-works-hero">
        <div class="container">
            <h1>How It <span class="highlight">Works</span></h1>
            <p>Simple, efficient, and transparent violation reporting system through QR code technology</p>
        </div>
    </section>

    <!-- Steps Section -->
    <section class="steps-container">
        <div class="container">
            <div class="section-title">
                <h2>Three Simple Steps</h2>
                <p>From scanning to resolution - our streamlined process ensures efficient violation management</p>
            </div>

            <div class="steps-timeline">
                <!-- Step 1 -->
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="step-content">
                        <div class="step-number">1</div>
                        <h3>Scan QR Code</h3>
                        <p>Use any smartphone camera or QR scanner app to scan the violation reporting QR code displayed around campus.</p>
                        <ul class="step-features">
                            <li>No app installation required</li>
                            <li>Works with any modern smartphone</li>
                            <li>Instant access to violation form</li>
                            <li>Location-specific QR codes</li>
                        </ul>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="step-content">
                        <div class="step-number">2</div>
                        <h3>Report Violation</h3>
                        <p>Fill out the simple digital form with violation details and submit securely.</p>
                        <ul class="step-features">
                            <li>Select from violation categories</li>
                            <li>Add descriptive notes if needed</li>
                            <li>Attach photos (optional)</li>
                            <li>Anonymous reporting available</li>
                            <li>Instant timestamp and location tagging</li>
                        </ul>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="step-content">
                        <div class="step-number">3</div>
                        <h3>ISASEC Review & Action</h3>
                        <p>The violation report is automatically sent to ISASEC (Institutional Student Affairs and Services) for review and appropriate action.</p>
                        <ul class="step-features">
                            <li>Immediate notification to ISASEC faculty</li>
                            <li>Secure digital record keeping</li>
                            <li>Follow-up actions documented</li>
                            <li>Student notification of resolution</li>
                            <li>Disciplinary proceedings if required</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Roles Section -->
    <section class="roles-section">
        <div class="container">
            <div class="section-title">
                <h2>System Roles</h2>
                <p>Understanding the key players in our violation reporting ecosystem</p>
            </div>

            <div class="roles-grid">
                <!-- Role 1 -->
                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3>Student/Reporter</h3>
                    <p>Scans QR code and reports violations. Can choose to report anonymously or provide identification for follow-up.</p>
                </div>

                <!-- Role 2 -->
                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3>ISASEC Faculty</h3>
                    <p>Reviews all violation reports, investigates incidents, determines appropriate actions, and maintains disciplinary records.</p>
                </div>

                <!-- Role 3 -->
                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>School Administration</h3>
                    <p>Receives periodic reports and analytics to monitor campus discipline trends and policy effectiveness.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ISASEC Highlight -->
    <div class="container">
        <div class="isasec-highlight">
            <h3><i class="fas fa-star"></i> ISASEC: Your Campus Safety Guardians</h3>
            <p>The Institutional Student Affairs and Services (ISASEC) faculty are specially trained to handle all reported violations with professionalism, confidentiality, and fairness. They ensure proper investigation and appropriate disciplinary measures according to school policies.</p>
            <div class="isasec-badge">Official Review Body</div>
        </div>
    </div>

    <!-- Benefits Section -->
    <section class="benefits-section">
        <div class="container">
            <div class="section-title">
                <h2>Key Benefits</h2>
                <p>Why our QR violation system is better than traditional methods</p>
            </div>

            <div class="benefits-grid">
                <!-- Benefit 1 -->
                <div class="benefit-card">
                    <i class="fas fa-bolt"></i>
                    <h3>Instant Reporting</h3>
                    <p>Report violations in seconds instead of filling out paperwork or waiting in lines.</p>
                </div>

                <!-- Benefit 2 -->
                <div class="benefit-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Digital Tracking</h3>
                    <p>All reports are digitally tracked, timestamped, and organized for easy reference.</p>
                </div>

                <!-- Benefit 3 -->
                <div class="benefit-card">
                    <i class="fas fa-user-shield"></i>
                    <h3>Confidentiality</h3>
                    <p>Secure system protects reporter identity while ensuring accountability.</p>
                </div>

                <!-- Benefit 4 -->
                <div class="benefit-card">
                    <i class="fas fa-clock"></i>
                    <h3>24/7 Availability</h3>
                    <p>QR codes are always accessible for reporting violations anytime, anywhere on campus.</p>
                </div>

                <!-- Benefit 5 -->
                <div class="benefit-card">
                    <i class="fas fa-paper-plane"></i>
                    <h3>Direct to ISASEC</h3>
                    <p>Reports go directly to ISASEC faculty for immediate attention and action.</p>
                </div>

                <!-- Benefit 6 -->
                <div class="benefit-card">
                    <i class="fas fa-tree"></i>
                    <h3>Paperless</h3>
                    <p>Eco-friendly solution that eliminates paper forms and physical filing.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
   

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Make Campus Better?</h2>
                <p>Join us in maintaining a safe and disciplined academic environment. Scan QR codes around campus to report violations and help ISASEC take appropriate actions.</p>
                <div class="cta-buttons">
                    <a href="violations.php" class="cta-btn primary">
                        <i class="fas fa-list"></i> View Violation Categories
                    </a>
                    <a href="index.php" class="cta-btn secondary">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
    
    <script>
    // FAQ Accordion
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            // Close other items
            faqItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
            
            // Toggle current item
            item.classList.toggle('active');
        });
    });

    // Step animations on scroll
    const observerOptions = {
        threshold: 0.3,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe step elements
    document.querySelectorAll('.step').forEach((step, index) => {
        step.style.opacity = '0';
        step.style.transform = 'translateY(30px)';
        step.style.transition = `opacity 0.5s ease ${index * 0.2}s, transform 0.5s ease ${index * 0.2}s`;
        observer.observe(step);
    });

    // Observe benefit cards
    document.querySelectorAll('.benefit-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.5s ease ${index * 0.1}s, transform 0.5s ease ${index * 0.1}s`;
        observer.observe(card);
    });

    // Observe role cards
    document.querySelectorAll('.role-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.5s ease ${index * 0.1}s, transform 0.5s ease ${index * 0.1}s`;
        observer.observe(card);
    });
    </script>
</body>
</html>