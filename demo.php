<?php
session_start();
$currentPage = 'demo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo - Pixel Wizard Co.</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f3fffb 0%, #e6fff8 100%);
            color: #333;
            min-height: 100vh;
        }

        /* Demo Container */
        .demo-container {
            max-width: 1200px;
            margin: 80px auto 40px;
            padding: 0 20px;
        }

        .demo-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .demo-header h1 {
            font-size: 3rem;
            color: #00c476;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #00c476, #00a86b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .demo-header p {
            font-size: 1.2rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Demo Features */
        .demo-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 196, 118, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(0, 196, 118, 0.1);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 196, 118, 0.2);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #00c476, #00a86b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: white;
            font-size: 1.8rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 15px;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            padding: 8px 0;
            color: #555;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .feature-list li i {
            color: #00c476;
        }

        /* Demo Video/Preview */
        .demo-preview {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 50px;
            text-align: center;
        }

        .demo-preview h2 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 30px;
        }

        .preview-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }

        .preview-icon {
            font-size: 4rem;
            color: #00c476;
            margin-bottom: 20px;
        }

        .preview-text {
            font-size: 1.2rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Interactive Demo */
        .interactive-demo {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 50px;
        }

        .interactive-demo h2 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .demo-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
        }

        .demo-steps::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 2px;
            background: #eee;
            z-index: 1;
        }

        .step {
            background: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            color: #999;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .step.active {
            border-color: #00c476;
            background: #00c476;
            color: white;
            transform: scale(1.1);
        }

        .step.completed {
            border-color: #00c476;
            background: #00c476;
            color: white;
        }

        .demo-content {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .demo-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .demo-btn {
            padding: 12px 25px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .demo-btn.primary {
            background: #00c476;
            color: white;
        }

        .demo-btn.primary:hover {
            background: #00a86b;
        }

        .demo-btn.secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #ddd;
        }

        .demo-btn.secondary:hover {
            background: #e9ecef;
        }

        /* Call to Action */
        .demo-cta {
            text-align: center;
            padding: 60px 40px;
            background: linear-gradient(135deg, #00c476, #00a86b);
            border-radius: 15px;
            color: white;
            margin-bottom: 40px;
        }

        .demo-cta h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .demo-cta p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta-btn {
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .cta-btn.primary {
            background: white;
            color: #00c476;
        }

        .cta-btn.primary:hover {
            background: #f8f9fa;
            transform: translateY(-3px);
        }

        .cta-btn.secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .cta-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .demo-container {
                margin: 70px auto 30px;
            }

            .demo-header h1 {
                font-size: 2.2rem;
            }

            .demo-features {
                grid-template-columns: 1fr;
            }

            .demo-steps {
                flex-direction: column;
                gap: 30px;
            }

            .demo-steps::before {
                display: none;
            }

            .step {
                width: 50px;
                height: 50px;
                font-size: 1rem;
            }

            .demo-buttons {
                flex-direction: column;
                width: 100%;
            }

            .demo-btn {
                width: 100%;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .cta-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar - This should be OUTSIDE the <style> tag! -->
    <?php include 'nav.php'; ?>

    <div class="demo-container">
        <!-- Header -->
        <div class="demo-header">
            <h1>See Our System in Action</h1>
            <p>Experience how Pixel Wizard Co. revolutionizes student violation tracking with our interactive demo</p>
        </div>

        <!-- Features -->
        <div class="demo-features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h3>QR Code Scanning</h3>
                <p>Quickly scan student QR codes to access their violation records instantly.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> Instant student identification</li>
                    <li><i class="fas fa-check"></i> No manual data entry</li>
                    <li><i class="fas fa-check"></i> Real-time data access</li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Violation Tracking</h3>
                <p>Comprehensive system for recording and managing student violations.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> Minor/Major categorization</li>
                    <li><i class="fas fa-check"></i> Automatic violation conversion</li>
                    <li><i class="fas fa-check"></i> Real-time notifications</li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Analytics & Reports</h3>
                <p>Generate detailed reports and analytics for better decision making.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> Visual statistics dashboard</li>
                    <li><i class="fas fa-check"></i> Custom report generation</li>
                    <li><i class="fas fa-check"></i> Trend analysis</li>
                </ul>
            </div>
        </div>

        <!-- Preview -->
        <div class="demo-preview">
            <h2>System Preview</h2>
            <div class="preview-container">
                <div class="preview-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <p class="preview-text">
                    Our system provides a seamless interface for both administrators and staff to manage student violations efficiently. 
                    With intuitive navigation and real-time updates, you'll have complete control over the violation management process.
                </p>
                <div class="demo-buttons">
                    <button class="demo-btn primary" onclick="showStep(1)">
                        <i class="fas fa-play"></i> Start Interactive Demo
                    </button>
                </div>
            </div>
        </div>

        <!-- Interactive Demo -->
        <div class="interactive-demo" id="interactiveDemo" style="display: none;">
            <h2>Interactive Demo</h2>
            <div class="demo-steps">
                <div class="step active" id="step1">1</div>
                <div class="step" id="step2">2</div>
                <div class="step" id="step3">3</div>
                <div class="step" id="step4">4</div>
            </div>
            
            <div class="demo-content">
                <div id="stepContent">
                    <h3>Step 1: Student Identification</h3>
                    <p>Scan a student QR code or enter student number to access their profile.</p>
                    <div class="demo-buttons">
                        <button class="demo-btn primary" onclick="showStep(2)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
        </div>
        
    
    </div>
        <?php include 'footer.php'; ?>
    <script>
        let currentStep = 1;
        const totalSteps = 4;

        function showStep(step) {
            currentStep = step;
            
            // Update steps
            for (let i = 1; i <= totalSteps; i++) {
                const stepElement = document.getElementById('step' + i);
                if (i < step) {
                    stepElement.className = 'step completed';
                } else if (i === step) {
                    stepElement.className = 'step active';
                } else {
                    stepElement.className = 'step';
                }
            }
            
            // Show interactive demo if not visible
            const interactiveDemo = document.getElementById('interactiveDemo');
            if (interactiveDemo.style.display === 'none') {
                interactiveDemo.style.display = 'block';
            }
            
            // Update content based on step
            const stepContent = document.getElementById('stepContent');
            switch(step) {
                case 1:
                    stepContent.innerHTML = `
                        <h3>Step 1: Student Identification</h3>
                        <p>Scan a student QR code or enter student number to access their profile.</p>
                        <div class="demo-buttons">
                            <button class="demo-btn primary" onclick="showStep(2)">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    `;
                    break;
                case 2:
                    stepContent.innerHTML = `
                        <h3>Step 2: Record Violation</h3>
                        <p>Select violation type (Minor/Major), add description, and attach evidence if needed.</p>
                        <div class="demo-buttons">
                            <button class="demo-btn secondary" onclick="showStep(1)">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button class="demo-btn primary" onclick="showStep(3)">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    `;
                    break;
                case 3:
                    stepContent.innerHTML = `
                        <h3>Step 3: Assign Sanction</h3>
                        <p>Based on violation category and history, assign appropriate sanctions with due dates.</p>
                        <div class="demo-buttons">
                            <button class="demo-btn secondary" onclick="showStep(2)">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button class="demo-btn primary" onclick="showStep(4)">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    `;
                    break;
                case 4:
                    stepContent.innerHTML = `
                        <h3>Step 4: Monitor Progress</h3>
                        <p>Track sanction completion, view statistics, and generate reports.</p>
                        <div class="demo-buttons">
                            <button class="demo-btn secondary" onclick="showStep(3)">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button class="demo-btn primary" onclick="restartDemo()">
                                <i class="fas fa-redo"></i> Restart Demo
                            </button>
                        </div>
                    `;
                    break;
            }
        }

        function restartDemo() {
            showStep(1);
            const interactiveDemo = document.getElementById('interactiveDemo');
            interactiveDemo.style.display = 'none';
            
            // Scroll to preview section
            document.querySelector('.demo-preview').scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Initialize demo
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Demo page loaded successfully');
        });
    </script>

</body>

</html>