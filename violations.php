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
    <title>Violations List | QR Violation Recorder</title>
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

    /* Violations Hero */
    .violations-hero {
        padding: 100px 0;
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-dark) 100%);
        color: var(--white);
        text-align: center;
    }

    .violations-hero h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--white);
    }

    .violations-hero p {
        font-size: 1.25rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Tabs */
    .violations-tabs {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin: 60px auto 40px;
        max-width: 800px;
    }

    .tab-btn {
        flex: 1;
        padding: 15px 30px;
        border: none;
        background: var(--white);
        color: var(--gray);
        font-weight: 600;
        border-radius: 10px;
        cursor: pointer;
        transition: var(--transition);
        font-size: 1.1rem;
        box-shadow: var(--shadow);
    }

    .tab-btn:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
    }

    .tab-btn.active {
        background: var(--primary-green);
        color: var(--white);
    }

    /* Tab Content */
    .tab-content {
        display: none;
        padding: 40px 0;
    }

    .tab-content.active {
        display: block;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Violations Grid */
    .violations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 25px;
    }

    .violation-card {
        background: var(--white);
        padding: 30px;
        border-radius: 15px;
        box-shadow: var(--shadow);
        transition: var(--transition);
        border-left: 4px solid var(--primary-green);
    }

    .violation-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .violation-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--gray-light);
    }

    .violation-category {
        background: var(--gray-light);
        color: var(--gray-dark);
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .violation-level {
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .violation-level.minor {
        background: #FEF3C7;
        color: #92400E;
    }

    .violation-level.major {
        background: #FEE2E2;
        color: #991B1B;
    }

    .violation-card h3 {
        margin-bottom: 10px;
        color: var(--gray-dark);
        font-size: 1.25rem;
    }

    .violation-card p {
        color: var(--gray);
        margin-bottom: 20px;
        line-height: 1.6;
    }

    .violation-meta {
        display: flex;
        flex-direction: column;
        gap: 10px;
        font-size: 0.875rem;
        color: var(--gray);
        padding-top: 15px;
        border-top: 1px dashed var(--gray-light);
    }

    .violation-meta span {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Note Section */
    .note-section {
        background: var(--accent-green);
        padding: 40px;
        border-radius: 15px;
        margin: 60px auto;
        max-width: 800px;
        text-align: center;
    }

    .note-section h3 {
        color: var(--primary-dark);
        margin-bottom: 15px;
        font-size: 1.5rem;
    }

    .note-section p {
        color: var(--gray-dark);
        line-height: 1.6;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .violations-hero h1 {
            font-size: 2.5rem;
        }
        
        .violations-tabs {
            flex-direction: column;
        }
        
        .violations-grid {
            grid-template-columns: 1fr;
        }
        
        .violation-card {
            padding: 25px;
        }
    }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <section class="violations-hero">
        <div class="container">
            <h1>Violation <span class="highlight">Categories</span></h1>
            <p>Comprehensive list of minor and major violations tracked by our system</p>
        </div>
    </section>

    <div class="container">
        <div class="violations-tabs">
            <button class="tab-btn active" data-tab="minor">
                <i class="fas fa-exclamation-circle"></i> Minor Violations
            </button>
            <button class="tab-btn" data-tab="major">
                <i class="fas fa-times-circle"></i> Major Violations
            </button>
        </div>
    </div>

    <!-- Minor Violations Tab -->
    <div class="tab-content active" id="minor-tab">
        <div class="container">
            <div class="violations-grid">
                <!-- Minor Violation 1 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category A</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>No ID</h3>
                    <p>Failure to conspicuously wear College-issued identification card on campus premises.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Verbal Warning</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
                
                <!-- Minor Violation 2 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category A</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>Improper Attire</h3>
                    <p>Wearing shorts, sando, sleeveless, or inappropriate clothing within school premises.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Verbal Warning</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
                
                <!-- Minor Violation 3 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category A</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>Improper Uniform</h3>
                    <p>Not wearing the prescribed school uniform during class days and official functions.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Verbal Warning</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
                
                <!-- Minor Violation 4 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category A</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>Late</h3>
                    <p>Arriving late to class or any official school activity without valid excuse.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Verbal Warning</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
                
                <!-- Minor Violation 5 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category B</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>Mobile Phone Use</h3>
                    <p>Disruptive use of mobile phone during class hours without teacher's permission.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Confiscation</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
                
                <!-- Minor Violation 6 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category B</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>Disruptive Behavior</h3>
                    <p>Running, loitering, shouting, or creating excessive noise in corridors and common areas.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Verbal Warning</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
                
                <!-- Minor Violation 7 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category B</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>Littering</h3>
                    <p>Spitting or littering on campus premises, violating cleanliness policies.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Verbal Warning</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
                
                <!-- Minor Violation 8 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category B</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>Public Display of Affection</h3>
                    <p>Engaging in petting, necking, or inappropriate physical contact in campus areas.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Verbal Warning</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
                
                <!-- Minor Violation 9 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category B</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>Vaping</h3>
                    <p>Use of e-cigarette/vape on campus premises, violating no-smoking policies.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Confiscation & Warning</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
                
                <!-- Minor Violation 10 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category B</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>Parking Violation</h3>
                    <p>Violation of parking rules and regulations within school premises.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Verbal Warning</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
                
                <!-- Minor Violation 11 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category C</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>Disrespect</h3>
                    <p>Disrespectful conduct toward school authorities, faculty, or staff members.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Warning & Counseling</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
                
                <!-- Minor Violation 12 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category C</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>Classroom Entry</h3>
                    <p>Entering classroom without teacher's permission or during unauthorized times.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Verbal Warning</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
                
                <!-- Minor Violation 13 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category C</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>Gambling Materials</h3>
                    <p>Possession of gambling items or paraphernalia on campus premises.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Confiscation</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
                
                <!-- Minor Violation 14 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category C</span>
                        <span class="violation-level minor">Minor</span>
                    </div>
                    <h3>Other Minor Infractions</h3>
                    <p>Other minor violations not specifically listed but violating school policies.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-clock"></i> 1st Offense: Verbal Warning</span>
                        <span><i class="fas fa-history"></i> 4 Minor violations = 1 Major violation</span>
                    </div>
                </div>
            </div>
            
            <div class="note-section">
                <h3><i class="fas fa-info-circle"></i> Important Note</h3>
                <p>Accumulation of <strong>4 Minor violations</strong> automatically converts to <strong>1 Major violation</strong>. Repeated offenses may lead to stricter disciplinary actions.</p>
            </div>
        </div>
    </div>

    <!-- Major Violations Tab -->
    <div class="tab-content" id="major-tab">
        <div class="container">
            <div class="violations-grid">
                <!-- Major Violation 1 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Academic Cheating</h3>
                    <p>Cheating during examinations, quizzes, or any academic requirements including plagiarism.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Immediate suspension</span>
                        <span><i class="fas fa-gavel"></i> Disciplinary hearing required</span>
                    </div>
                </div>
                
                <!-- Major Violation 2 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Plagiarism</h3>
                    <p>Submitting another person's work as one's own in academic requirements.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Course failure possible</span>
                        <span><i class="fas fa-gavel"></i> Academic integrity violation</span>
                    </div>
                </div>
                
                <!-- Major Violation 3 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Falsification</h3>
                    <p>Forgery, altering documents, spreading false information, or forging school stamps.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Document invalidation</span>
                        <span><i class="fas fa-gavel"></i> Legal consequences possible</span>
                    </div>
                </div>
                
                <!-- Major Violation 4 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Physical Assault</h3>
                    <p>Causing physical injury or harm to any student, faculty, or staff member.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Immediate suspension</span>
                        <span><i class="fas fa-gavel"></i> Possible expulsion</span>
                    </div>
                </div>
                
                <!-- Major Violation 5 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Threats</h3>
                    <p>Threatening any person verbally or digitally through messages or social media.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Immediate investigation</span>
                        <span><i class="fas fa-gavel"></i> Security involvement</span>
                    </div>
                </div>
                
                <!-- Major Violation 6 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Bullying</h3>
                    <p>Bullying, harassment, defamation, or cyberbullying against any individual.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Mandatory counseling</span>
                        <span><i class="fas fa-gavel"></i> Disciplinary hearing</span>
                    </div>
                </div>
                
                <!-- Major Violation 7 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Weapon Possession</h3>
                    <p>Bringing or possessing deadly weapons, firearms, or explosives within campus premises.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Immediate suspension</span>
                        <span><i class="fas fa-gavel"></i> Police involvement</span>
                    </div>
                </div>
                
                <!-- Major Violation 8 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Drug Violation</h3>
                    <p>Possession, use, or distribution of illegal drugs or substances on campus.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Immediate suspension</span>
                        <span><i class="fas fa-gavel"></i> Legal action required</span>
                    </div>
                </div>
                
                <!-- Major Violation 9 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Alcohol Violation</h3>
                    <p>Possessing or drinking alcoholic beverages on campus premises.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Confiscation & suspension</span>
                        <span><i class="fas fa-gavel"></i> Disciplinary hearing</span>
                    </div>
                </div>
                
                <!-- Major Violation 10 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Sexual Harassment</h3>
                    <p>Any unwelcome sexual advances, requests for favors, or verbal/physical conduct of sexual nature.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Immediate investigation</span>
                        <span><i class="fas fa-gavel"></i> Disciplinary hearing</span>
                    </div>
                </div>
                
                <!-- Major Violation 11 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Vandalism</h3>
                    <p>Willful destruction or damage to school property, equipment, or facilities.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Property damage charges</span>
                        <span><i class="fas fa-gavel"></i> Disciplinary hearing</span>
                    </div>
                </div>
                
                <!-- Major Violation 12 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Hazing</h3>
                    <p>Participation in hazing activities or initiation rites.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Organization suspension</span>
                        <span><i class="fas fa-gavel"></i> Legal consequences</span>
                    </div>
                </div>
                
                <!-- Major Violation 13 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Unauthorized Organization</h3>
                    <p>Joining unrecognized fraternities, sororities, or organizations.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Organization ban</span>
                        <span><i class="fas fa-gavel"></i> Disciplinary action</span>
                    </div>
                </div>
                
                <!-- Major Violation 14 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Unauthorized Solicitation</h3>
                    <p>Collecting money or donations without proper school approval.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Fund confiscation</span>
                        <span><i class="fas fa-gavel"></i> Disciplinary hearing</span>
                    </div>
                </div>
                
                <!-- Major Violation 15 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>System Tampering</h3>
                    <p>Tampering with IT systems, networks, or bypassing security protocols.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> System access revocation</span>
                        <span><i class="fas fa-gavel"></i> IT security investigation</span>
                    </div>
                </div>
                
                <!-- Major Violation 16 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Gambling</h3>
                    <p>Engaging in gambling activities within school premises.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Immediate suspension</span>
                        <span><i class="fas fa-gavel"></i> Legal consequences possible</span>
                    </div>
                </div>
                
                <!-- Major Violation 17 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Lewd Conduct</h3>
                    <p>Engaging in obscene or indecent acts within campus premises.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Immediate suspension</span>
                        <span><i class="fas fa-gavel"></i> Disciplinary hearing</span>
                    </div>
                </div>
                
                <!-- Major Violation 18 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Disruption of Classes</h3>
                    <p>Instigating disruption of classes or organizing illegal assembly.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Class suspension</span>
                        <span><i class="fas fa-gavel"></i> Disciplinary action</span>
                    </div>
                </div>
                
                <!-- Major Violation 19 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Smoking</h3>
                    <p>Smoking within school premises, violating no-smoking policies.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Fine & suspension</span>
                        <span><i class="fas fa-gavel"></i> Health policy violation</span>
                    </div>
                </div>
                
                <!-- Major Violation 20 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Publishing False Information</h3>
                    <p>Spreading false or damaging information about the school through any media.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Content removal required</span>
                        <span><i class="fas fa-gavel"></i> Defamation investigation</span>
                    </div>
                </div>
                
                <!-- Major Violation 21 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Forging Security Stamps</h3>
                    <p>Forging school security stamps, stickers, or passes.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Immediate suspension</span>
                        <span><i class="fas fa-gavel"></i> Security breach investigation</span>
                    </div>
                </div>
                
                <!-- Major Violation 22 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>ID or Document Misuse</h3>
                    <p>Using someone else's ID, lending ID, or tampering with identification documents.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> ID confiscation</span>
                        <span><i class="fas fa-gavel"></i> Identity fraud investigation</span>
                    </div>
                </div>
                
                <!-- Major Violation 23 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Accumulation of 4 Minor Offenses</h3>
                    <p>Every accumulation of 4 minor violations equals 1 major violation.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Automatic conversion</span>
                        <span><i class="fas fa-gavel"></i> Major violation record</span>
                    </div>
                </div>
                
                <!-- Major Violation 24 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Endangering Safety</h3>
                    <p>Acts endangering the safety of students, faculty, or staff members.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Immediate investigation</span>
                        <span><i class="fas fa-gavel"></i> Safety protocol violation</span>
                    </div>
                </div>
                
                <!-- Major Violation 25 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Forcible Entry</h3>
                    <p>Entering restricted areas or offices forcibly without authorization.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Trespassing charge</span>
                        <span><i class="fas fa-gavel"></i> Security breach</span>
                    </div>
                </div>
                
                <!-- Major Violation 26 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Unauthorized Use of Rooms</h3>
                    <p>Using classrooms, laboratories, or facilities without permission.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Facility access ban</span>
                        <span><i class="fas fa-gavel"></i> Resource misuse</span>
                    </div>
                </div>
                
                <!-- Major Violation 27 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Misuse of IT Systems</h3>
                    <p>Hacking, bypassing security, or unauthorized access to systems.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> System access revocation</span>
                        <span><i class="fas fa-gavel"></i> IT security violation</span>
                    </div>
                </div>
                
                <!-- Major Violation 28 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Bribery</h3>
                    <p>Offering money or favors to school personnel to influence decisions.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Ethical violation</span>
                        <span><i class="fas fa-gavel"></i> Disciplinary action</span>
                    </div>
                </div>
                
                <!-- Major Violation 29 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Stealing</h3>
                    <p>Theft of school property or personal property of others.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Property recovery</span>
                        <span><i class="fas fa-gavel"></i> Theft investigation</span>
                    </div>
                </div>
                
                <!-- Major Violation 30 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Tampering Emergency Devices</h3>
                    <p>Using fire alarms, extinguishers, or emergency equipment improperly.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Emergency protocol violation</span>
                        <span><i class="fas fa-gavel"></i> Safety hazard</span>
                    </div>
                </div>
                
                <!-- Major Violation 31 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Obscene Materials</h3>
                    <p>Possessing or distributing obscene or pornographic materials on campus.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Material confiscation</span>
                        <span><i class="fas fa-gavel"></i> Disciplinary hearing</span>
                    </div>
                </div>
                
                <!-- Major Violation 32 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Violent Protest or Coercion</h3>
                    <p>Organizing protests using force or intimidation tactics.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Immediate suspension</span>
                        <span><i class="fas fa-gavel"></i> Security involvement</span>
                    </div>
                </div>
                
                <!-- Major Violation 33 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Unauthorized Posting</h3>
                    <p>Posting notices, literature, or materials without proper approval.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Material removal</span>
                        <span><i class="fas fa-gavel"></i> Communication policy violation</span>
                    </div>
                </div>
                
                <!-- Major Violation 34 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Aiding Violations</h3>
                    <p>Helping or encouraging others to commit violations.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Complicity charge</span>
                        <span><i class="fas fa-gavel"></i> Equal responsibility</span>
                    </div>
                </div>
                
                <!-- Major Violation 35 -->
                <div class="violation-card">
                    <div class="violation-header">
                        <span class="violation-category">Category X</span>
                        <span class="violation-level major">Major</span>
                    </div>
                    <h3>Other Major Offenses</h3>
                    <p>Other major violations not specifically listed but severely violating policies.</p>
                    <div class="violation-meta">
                        <span><i class="fas fa-exclamation-circle"></i> Case-specific action</span>
                        <span><i class="fas fa-gavel"></i> Disciplinary hearing</span>
                    </div>
                </div>
            </div>
            
            <div class="note-section">
                <h3><i class="fas fa-exclamation-triangle"></i> Serious Offenses</h3>
                <p>Major violations require <strong>immediate disciplinary action</strong> and may result in suspension, expulsion, or legal proceedings depending on the severity of the offense.</p>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <script>
    // Tab functionality
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked button
            btn.classList.add('active');
            
            // Show corresponding content
            const tabId = btn.getAttribute('data-tab');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });
    
    // Add click animations to violation cards
    const violationCards = document.querySelectorAll('.violation-card');
    
    violationCards.forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
            setTimeout(() => {
                this.style.transform = '';
            }, 300);
        });
    });
    </script>
</body>
</html>