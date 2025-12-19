 <?php 
    // Start session at the VERY TOP of index.php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    ?>
<?php include 'email_config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Violation Recorder | Modern Digital Monitoring</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Reset and Base Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --primary-green: #10B981;
        --primary-dark: #059669;
        --primary-light: #34D399;
        --accent-green: #A7F3D0;
        --white: #FFFFFF;
        --light-bg: #F9FAFB;
        --gray-light: #E5E7EB;
        --gray: #6B7280;
        --gray-dark: #374151;
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s ease;
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
        width: 100%;
    }

    /* Typography */
    h1, h2, h3, h4 {
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 1rem;
    }

    h1 { font-size: 3.5rem; }
    h2 { font-size: 2.5rem; }
    h3 { font-size: 1.75rem; }
    h4 { font-size: 1.25rem; }

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

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition);
        border: 2px solid transparent;
        cursor: pointer;
        font-size: 1rem;
    }

    .btn-primary {
        background-color: var(--primary-green);
        color: var(--white);
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .btn-secondary {
        background-color: var(--white);
        color: var(--primary-green);
        border-color: var(--primary-green);
    }

    .btn-secondary:hover {
        background-color: var(--primary-green);
        color: var(--white);
        transform: translateY(-2px);
    }

    .btn-outline {
        background-color: transparent;
        color: var(--primary-green);
        border-color: var(--primary-green);
    }

    .btn-outline:hover {
        background-color: var(--primary-green);
        color: var(--white);
    }

    .btn-light {
        background-color: var(--white);
        color: var(--primary-green);
    }

    .btn-light:hover {
        background-color: var(--gray-light);
    }

    /* Hero Section */
    .hero-section {
        padding: 80px 0;
        background: linear-gradient(135deg, var(--white) 0%, #F0FDF4 100%);
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="%2310B981" opacity="0.03"/></svg>');
        background-size: cover;
    }

    .hero-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
        position: relative;
        z-index: 1;
    }

    .badge {
        display: inline-block;
        background-color: var(--accent-green);
        color: var(--primary-dark);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .hero-title {
        font-size: 3.5rem;
        margin-bottom: 20px;
    }

    .hero-subtitle {
        font-size: 1.25rem;
        color: var(--gray);
        margin-bottom: 30px;
    }

    .highlight-text {
        color: var(--primary-green);
        font-weight: 600;
    }

    .hero-features {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 40px;
    }

    .feature {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
        color: var(--gray);
    }

    .feature i {
        color: var(--primary-green);
        font-size: 1.25rem;
    }

    .hero-buttons {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .qr-card {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--shadow-lg);
        transition: var(--transition);
    }

    .qr-card:hover {
        transform: translateY(-10px) scale(1.02);
    }

    .qr-image {
        width: 100%;
        height: auto;
        display: block;
    }

    .qr-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(16, 185, 129, 0.9));
        padding: 30px;
        color: var(--white);
        text-align: center;
    }

    .scan-animation {
        font-size: 3rem;
        margin-bottom: 10px;
        animation: scan 2s infinite;
    }

    @keyframes scan {
        0%, 100% { opacity: 0.5; transform: translateY(0); }
        50% { opacity: 1; transform: translateY(-10px); }
    }

    /* Info Section */
    .section-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .section-header p {
        color: var(--gray);
        font-size: 1.125rem;
        max-width: 600px;
        margin: 0 auto;
    }

    .info-section {
        padding: 80px 0;
    }

    .info-card {
        background: var(--white);
        border-radius: 20px;
        padding: 60px;
        box-shadow: var(--shadow);
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
        margin-top: 40px;
    }

    .stat-item {
        text-align: center;
        padding: 20px;
        background: var(--light-bg);
        border-radius: 10px;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary-green);
        margin-bottom: 5px;
    }

    .stat-label {
        color: var(--gray);
        font-size: 0.9rem;
    }

    .visual-step {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        margin-bottom: 15px;
        background: var(--light-bg);
        border-radius: 10px;
        transition: var(--transition);
    }

    .visual-step:hover {
        transform: translateX(10px);
        background: var(--accent-green);
    }

    .step-number {
        width: 40px;
        height: 40px;
        background: var(--primary-green);
        color: var(--white);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }

    /* Company Section */
    .company-section {
        padding: 80px 0;
        background: var(--white);
    }

    .company-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
    }

    .logo-card {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--shadow-lg);
    }

    .logo-card img {
        width: 100%;
        height: 300px;
        object-fit: cover;
        display: block;
    }

    .logo-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(16, 185, 129, 0.95));
        padding: 30px;
        color: var(--white);
        text-align: center;
    }

    .mission-vision {
        display: grid;
        gap: 30px;
        margin-bottom: 40px;
    }

    .mv-card {
        padding: 30px;
        background: var(--light-bg);
        border-radius: 15px;
        transition: var(--transition);
    }

    .mv-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow);
    }

    .mv-icon {
        width: 60px;
        height: 60px;
        background: var(--primary-green);
        color: var(--white);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 20px;
    }

    .values h3 {
        margin-bottom: 20px;
    }

    .value-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .value-tag {
        background: var(--accent-green);
        color: var(--primary-dark);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    /* FIXED TEAM CAROUSEL SECTION */
    .team-section {
        padding: 80px 0;
        background: var(--light-bg);
    }

    .team-carousel {
        position: relative;
        overflow: hidden;
        padding: 20px 0;
    }

    .carousel-container {
        position: relative;
        max-width: 1200px;
        margin: 0 auto;
    }

    .carousel-track {
        display: flex;
        gap: 40px;
        transition: transform 0.5s cubic-bezier(0.645, 0.045, 0.355, 1);
        padding: 10px;
    }

    .team-card {
        flex: 0 0 calc(33.333% - 27px);
        background: var(--white);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: var(--transition);
        opacity: 0.7;
        transform: scale(0.95);
    }

    .team-card.active {
        opacity: 1;
        transform: scale(1);
        box-shadow: var(--shadow-lg);
    }

    .team-image {
        position: relative;
        height: 300px;
        overflow: hidden;
    }

    .team-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: var(--transition);
    }

    .team-card:hover .team-image img {
        transform: scale(1.05);
    }

    .team-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(16, 185, 129, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: var(--transition);
    }

    .team-card:hover .team-overlay {
        opacity: 1;
    }

    .social-icons {
        display: flex;
        gap: 15px;
    }

    .social-icons a {
        width: 40px;
        height: 40px;
        background: var(--white);
        color: var(--primary-green);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: var(--transition);
    }

    .social-icons a:hover {
        background: var(--primary-dark);
        color: var(--white);
        transform: translateY(-3px);
    }

    .team-info {
        padding: 30px;
    }

    .team-role {
        color: var(--primary-green);
        font-weight: 600;
        margin-bottom: 15px;
    }

    .team-contact {
        color: var(--gray);
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }

    .carousel-controls {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 30px;
        margin-top: 40px;
    }

    .carousel-btn {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--white);
        border: 2px solid var(--primary-green);
        color: var(--primary-green);
        font-size: 1.25rem;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }

    .carousel-btn:hover {
        background: var(--primary-green);
        color: var(--white);
        transform: scale(1.1);
    }

    .carousel-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .carousel-btn:disabled:hover {
        background: var(--white);
        color: var(--primary-green);
        transform: none;
    }

    .carousel-dots {
        display: flex;
        gap: 10px;
    }

    .dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--gray-light);
        cursor: pointer;
        transition: var(--transition);
    }

    .dot.active {
        background: var(--primary-green);
        transform: scale(1.2);
    }

    /* FAQ Section */
    .faq-section {
        padding: 80px 0;
        background: var(--white);
    }

    .faq-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .faq-item {
        margin-bottom: 15px;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid var(--gray-light);
    }

    .faq-question {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px;
        cursor: pointer;
        transition: var(--transition);
    }

    .faq-question:hover {
        background: var(--accent-green);
    }

    .faq-number {
        background: var(--primary-green);
        color: var(--white);
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-right: 15px;
        flex-shrink: 0;
    }

    .faq-question h3 {
        margin: 0;
        flex: 1;
        font-size: 1.125rem;
        text-align: left;
    }

    .faq-question i {
        transition: var(--transition);
    }

    .faq-item.active .faq-question i {
        transform: rotate(180deg);
    }

    .faq-answer {
        padding: 0 20px;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .faq-item.active .faq-answer {
        padding: 20px;
        max-height: 500px;
    }

    /* Contact Section */
    .contact-section {
        padding: 80px 0;
        background: var(--light-bg);
    }

    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
    }

    .contact-card {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 30px;
        background: var(--white);
        border-radius: 15px;
        margin-bottom: 20px;
        box-shadow: var(--shadow);
        transition: var(--transition);
    }

    .contact-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .contact-icon {
        width: 60px;
        height: 60px;
        background: var(--primary-green);
        color: var(--white);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .contact-details h3 {
        margin-bottom: 5px;
    }

    .contact-details p {
        color: var(--gray-dark);
        font-weight: 600;
        margin-bottom: 5px;
    }

    .contact-details small {
        color: var(--gray);
    }

    .contact-form {
        background: var(--white);
        padding: 40px;
        border-radius: 15px;
        box-shadow: var(--shadow);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 15px;
        border: 2px solid var(--gray-light);
        border-radius: 8px;
        font-size: 1rem;
        transition: var(--transition);
        font-family: inherit;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    /* CTA Section */
    .cta-section {
        padding: 100px 0;
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-dark) 100%);
        color: var(--white);
        text-align: center;
    }

    .cta-content h2 {
        font-size: 3rem;
        margin-bottom: 20px;
    }

    .cta-content p {
        font-size: 1.25rem;
        margin-bottom: 40px;
        opacity: 0.9;
    }

    .cta-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        z-index: 2000;
        overflow-y: auto;
        padding: 20px;
    }

    .modal-content {
        background: var(--white);
        width: 90%;
        max-width: 800px;
        margin: 50px auto;
        border-radius: 20px;
        overflow: hidden;
        animation: modalSlide 0.3s ease;
    }

    @keyframes modalSlide {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 30px;
        background: var(--primary-green);
        color: var(--white);
    }

    .modal-header h2 {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .close-modal {
        background: none;
        border: none;
        color: var(--white);
        font-size: 2rem;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
    }

    .close-modal:hover {
        transform: rotate(90deg);
    }

    .modal-body {
        padding: 30px;
        max-height: 70vh;
        overflow-y: auto;
    }

    .privacy-content {
        max-width: 700px;
        margin: 0 auto;
    }

    .privacy-intro {
        font-size: 1.125rem;
        margin-bottom: 30px;
        padding: 20px;
        background: var(--light-bg);
        border-radius: 10px;
        border-left: 4px solid var(--primary-green);
    }

    .privacy-section {
        margin-bottom: 30px;
    }

    .privacy-section h3 {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--primary-green);
        margin-bottom: 15px;
    }

    .privacy-section ul {
        margin-left: 20px;
        margin-bottom: 15px;
    }

    .privacy-section li {
        margin-bottom: 8px;
    }

    .modal-footer {
        padding: 20px 30px;
        background: var(--light-bg);
        text-align: right;
    }

    /* RESPONSIVE DESIGN */
    @media (max-width: 1200px) {
        .team-card {
            flex: 0 0 calc(33.333% - 27px);
        }
    }

    @media (max-width: 1024px) {
        .hero-grid,
        .info-grid,
        .company-grid,
        .contact-grid {
            grid-template-columns: 1fr;
            gap: 40px;
        }
        
        .hero-title {
            font-size: 2.5rem;
        }
        
        h2 {
            font-size: 2rem;
        }
        
        .team-card {
            flex: 0 0 calc(50% - 20px);
        }
        
        .info-card {
            padding: 40px;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .hero-section {
            padding: 60px 0;
        }
        
        .hero-features,
        .hero-buttons,
        .stats-grid,
        .mission-vision,
        .cta-buttons {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .hero-buttons .btn,
        .cta-buttons .btn {
            width: 100%;
            max-width: 300px;
            justify-content: center;
        }
        
        .team-card {
            flex: 0 0 calc(100% - 20px);
        }
        
        .contact-grid {
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        .carousel-controls {
            flex-direction: row;
            gap: 30px;
        }
        
        .hero-title {
            font-size: 2rem;
        }
        
        h1 {
            font-size: 2.5rem;
        }
        
        h2 {
            font-size: 1.75rem;
        }
        
        .info-section,
        .company-section,
        .team-section,
        .faq-section,
        .contact-section {
            padding: 60px 0;
        }
        
        .section-header {
            margin-bottom: 40px;
        }
        
        .info-card {
            padding: 30px 20px;
        }
        
        .visual-step {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
        }
        
        .cta-content h2 {
            font-size: 2rem;
        }
        
        .cta-content p {
            font-size: 1.1rem;
        }
        
        .value-tags {
            justify-content: center;
        }
        
        .contact-form {
            padding: 30px 20px;
        }
        
        .modal-body {
            max-height: 80vh;
        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 0 15px;
        }
        
        .hero-title {
            font-size: 1.75rem;
        }
        
        h1 {
            font-size: 2rem;
        }
        
        h2 {
            font-size: 1.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.1rem;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 4px 10px;
        }
        
        .feature {
            font-size: 0.85rem;
        }
        
        .stat-item {
            padding: 15px;
        }
        
        .stat-number {
            font-size: 2rem;
        }
        
        .team-image {
    height: 250px;       /* set a uniform height for all team images */
    overflow: hidden;    /* hide any overflow if image is bigger */
}
.team-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
        
        .team-info {
            padding: 20px;
        }
        
        .carousel-btn {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
        
        .carousel-controls {
            gap: 20px;
        }
        
        .faq-question h3 {
            font-size: 1rem;
        }
        
        .contact-card {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
        
        .contact-icon {
            width: 50px;
            height: 50px;
            font-size: 1.25rem;
        }
        
        .modal-header {
            padding: 20px;
        }
        
        .modal-body {
            padding: 20px;
        }
    }

    @media (max-width: 375px) {
        .hero-title {
            font-size: 1.5rem;
        }
        
        .value-tag {
            font-size: 0.75rem;
            padding: 6px 12px;
        }
        
        .team-contact {
            font-size: 0.8rem;
        }
    }
    </style>
</head>
<body>
   
    <?php include 'nav.php'; ?>
    <!-- Privacy Modal -->
    <div id="privacyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-shield-alt"></i> Data Privacy & Security</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="privacy-content">
                    <p class="privacy-intro">
                        This website follows the Data Privacy Act of 2012 (Republic Act 10173) and the guidelines of the National Privacy Commission (NPC). We are committed to protecting the personal information of all users.
                    </p>
                    
                    <div class="privacy-section">
                        <h3><i class="fas fa-database"></i> 1. Collection of Personal Information</h3>
                        <p>We only collect personal data that is necessary for the functions of this website. This may include your name, email address, contact information, or any details you provide when using our services or forms.</p>
                    </div>
                    
                    <div class="privacy-section">
                        <h3><i class="fas fa-cogs"></i> 2. Use of Personal Information</h3>
                        <p>All collected information is used solely for the following purposes:</p>
                        <ul>
                            <li>To provide and improve our services</li>
                            <li>To verify your identity when accessing secure features</li>
                            <li>To keep records of transactions or interactions</li>
                            <li>To communicate updates, notices, or responses</li>
                        </ul>
                        <p>We do not use your personal data for purposes outside those stated above without your consent.</p>
                    </div>
                    
                    <div class="privacy-section">
                        <h3><i class="fas fa-lock"></i> 3. Protection and Security</h3>
                        <p>We implement physical, technical, and organizational security measures to protect all personal data against:</p>
                        <ul>
                            <li>Unauthorized access</li>
                            <li>Loss or damage</li>
                            <li>Alteration or misuse</li>
                        </ul>
                        <p>Only authorized personnel are allowed to access stored information.</p>
                    </div>
                    
                    <div class="privacy-section">
                        <h3><i class="fas fa-share-alt"></i> 4. Sharing of Information</h3>
                        <p>Your personal data will not be shared with any third party unless:</p>
                        <ul>
                            <li>You give your clear consent</li>
                            <li>It is required by law</li>
                            <li>It is necessary for the operation of a service you requested</li>
                        </ul>
                        <p>We do not sell or distribute personal information.</p>
                    </div>
                    
                    <div class="privacy-section">
                        <h3><i class="fas fa-user-check"></i> 5. User Rights</h3>
                        <p>Under the Data Privacy Act, you have the right to:</p>
                        <ul>
                            <li>Access your personal data</li>
                            <li>Correct or update your information</li>
                            <li>Withdraw consent</li>
                            <li>Request deletion of your data</li>
                            <li>File a complaint with the National Privacy Commission</li>
                        </ul>
                        <p>You may contact us anytime to exercise these rights.</p>
                    </div>
                    
                    <div class="privacy-section">
                        <h3><i class="fas fa-cookie-bite"></i> 6. Cookies and Tracking Technologies</h3>
                        <p>This website may use cookies to improve user experience. Cookies help remember user settings and track website performance. You may disable cookies in your browser if you prefer.</p>
                    </div>
                    
                    <div class="privacy-section">
                        <h3><i class="fas fa-archive"></i> 7. Data Retention</h3>
                        <p>Personal data will only be kept for as long as necessary to fulfill the purpose for which it was collected or as required by law. After this period, all information will be securely deleted.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close-modal">Close</button>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="badge">Innovative Solution</div>
                    <h1 class="hero-title">QR Violation <span class="highlight">Recorder</span></h1>
                    <p class="hero-subtitle">A futuristic system to streamline violation monitoring using QR technology — <span class="highlight-text">fast, secure, reliable.</span></p>
                    
                    <div class="hero-features">
                        <div class="feature">
                            <i class="fas fa-bolt"></i>
                            <span>Instant Scanning</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-shield-alt"></i>
                            <span>Encrypted Data</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-chart-line"></i>
                            <span>Real-time Analytics</span>
                        </div>
                    </div>
                    
                    <div class="hero-buttons">
                        <a href="#about" class="btn btn-primary">Learn More</a>
                        <a href="features.php" class="btn btn-outline">View Features</a>
                        <button id="privacyBtn" class="btn btn-secondary">
                            <i class="fas fa-shield-alt"></i> Privacy Policy
                        </button>
                    </div>
                </div>
                
                <div class="hero-image">
                    <div class="qr-card">
                        <img src="qr.jpg" alt="QR Code System" class="qr-image">
                        <div class="qr-overlay">
                            <div class="scan-animation">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <p>Scan. Record. Done.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- What is QR Violation -->
    <section class="info-section">
        <div class="container">
            <div class="section-header">
                <h2>What is <span class="highlight">QR Violation Recorder?</span></h2>
                <p>A revolutionary approach to campus discipline management</p>
            </div>
            
            <div class="info-card">
                <div class="info-grid">
                    <div class="info-text">
                        <p style="text-align: justify;">
                            QR Violation Recorder is a digital monitoring system designed to streamline how student violations are tracked and documented. Each student is assigned a unique QR tag that serves as their secure identifier. When a violation occurs, guards or teachers can simply scan the QR code to instantly log the incident.
                        </p>
                        <p>
                            The system automatically records essential details such as the exact time, the type of violation, photo evidence taken on the spot, and any additional notes from the administering staff. All stored information is encrypted to ensure privacy and protect sensitive student data.
                        </p>
                        
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-number">99.9%</div>
                                <div class="stat-label">Accuracy Rate</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">3s</div>
                                <div class="stat-label">Average Scan Time</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">256-bit</div>
                                <div class="stat-label">Encryption</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">24/7</div>
                                <div class="stat-label">Monitoring</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-visual">
                        <div class="visual-card">
                            <div class="visual-step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>QR Assignment</h4>
                                    <p>Each student receives a unique encrypted QR code</p>
                                </div>
                            </div>
                            <div class="visual-step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Instant Scan</h4>
                                    <p>Staff scans QR code using mobile app or scanner</p>
                                </div>
                            </div>
                            <div class="visual-step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Record Violation</h4>
                                    <p>System logs violation with time, type, and evidence</p>
                                </div>
                            </div>
                            <div class="visual-step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h4>Secure Storage</h4>
                                    <p>Data encrypted and stored in secure database</p>
                                </div>
                            </div>
                            <div class="visual-step">
                                <div class="step-number">5</div>
                                <div class="step-content">
                                    <h4>Generate Report</h4>
                                    <p>The system automatically compiles violation records</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Company Profile -->
    <section id="about" class="company-section">
        <div class="container">
            <div class="section-header">
                <h2>About <span class="highlight">Pixel Wizard Co.</span></h2>
                <p>Transforming educational management through technology</p>
            </div>
            
            <div class="company-grid">
                <div class="company-logo">
                    <div class="logo-card">
                        <img src="comlogo.jpg" alt="Pixel Wizard Company Logo">
                        <div class="logo-overlay">
                            <h3>Pixel Wizard Co.</h3>
                            <p>Est. 2024</p>
                        </div>
                    </div>
                </div>

                
                <div class="company-info">
                    <div class="mission-vision">
                        <div class="mv-card">
                            <div class="mv-icon">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <h3>Our Mission</h3>
                            <p>To create innovative, user-friendly, and secure digital solutions that empower schools to manage information, communicate effectively, and enhance the learning experience for students, teachers, and administrators.</p>
                        </div>
                        
                        <div class="mv-card">
                            <div class="mv-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <h3>Our Vision</h3>
                            <p>To be the leading provider of cutting-edge educational web solutions, transforming the way schools operate and fostering a connected, efficient, and technologically advanced learning environment.</p>
                        </div>
                    </div>
                    
                    <div class="values">
                        <h3>Core Values</h3>
                        <div class="value-tags">
                            <span class="value-tag"><i class="fas fa-lock"></i> Privacy First</span>
                            <span class="value-tag"><i class="fas fa-bolt"></i> Speed & Efficiency</span>
                            <span class="value-tag"><i class="fas fa-eye"></i> Transparency</span>
                            <span class="value-tag"><i class="fas fa-users"></i> Collaboration</span>
                            <span class="value-tag"><i class="fas fa-rocket"></i> Innovation</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <div class="section-header">
                <h2>Meet Our <span class="highlight">Team</span></h2>
                <p>The creative minds behind QR Violation Recorder</p>
            </div>
            
            <div class="team-carousel">
                <div class="carousel-container">
                    <div class="carousel-track">
                        <!-- Team Member 1 -->
                        <div class="team-card active">
                            <div class="team-image">
                                <img src="angelo.png" alt="Angelo Brian R. Faustino">
                                <div class="team-overlay">
                                    <div class="social-icons">
                                        <a href="#"><i class="fab fa-linkedin"></i></a>
                                        <a href="#"><i class="fas fa-envelope"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="team-info">
                                <h3>Angelo Brian R. Faustino</h3>
                                <p class="team-role">Backend Specialist</p>
                                <p class="team-contact">
                                    <i class="fas fa-envelope"></i> abfaustino@kld.edu.ph
                                </p>
                                <p class="team-contact">
                                    <i class="fas fa-phone"></i> 09363587545
                                </p>
                            </div>
                        </div>
                        
                        <!-- Team Member 2 -->
                        <div class="team-card">
                            <div class="team-image">
                                <img src="jello.png" alt="John Jello P. Garcia">
                                <div class="team-overlay">
                                    <div class="social-icons">
                                        <a href="#"><i class="fab fa-linkedin"></i></a>
                                        <a href="#"><i class="fas fa-envelope"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="team-info">
                                <h3>John Jello P. Garcia</h3>
                                <br>
                                <p class="team-role">Frontend Developer</p>
                                <p class="team-contact">
                                    <i class="fas fa-envelope"></i> jjgarcia@kld.edu.ph
                                </p>
                                <p class="team-contact">
                                    <i class="fas fa-phone"></i> 09123456789
                                </p>
                            </div>
                        </div>
                        
                        <!-- Team Member 3 -->
                        <div class="team-card">
                            <div class="team-image">
                                <img src="ash.png" alt="Ashley Jhoreen B. Gregorio">
                                <div class="team-overlay">
                                    <div class="social-icons">
                                        <a href="#"><i class="fab fa-linkedin"></i></a>
                                        <a href="#"><i class="fas fa-envelope"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="team-info">
                                <h3>Ashley Jhoreen B. Gregorio</h3>
                                <p class="team-role">System Analyst</p>
                                <p class="team-contact">
                                    <i class="fas fa-envelope"></i> ajgregorio@kld.edu.ph
                                </p>
                                <p class="team-contact">
                                    <i class="fas fa-phone"></i> 09614553320
                                </p>
                            </div>
                        </div>
                        
                        <!-- Team Member 4 -->
                        <div class="team-card">
                            <div class="team-image">
                                <img src="jasper.png" alt="Jaspher Gayo">
                                <div class="team-overlay">
                                    <div class="social-icons">
                                        <a href="#"><i class="fab fa-linkedin"></i></a>
                                        <a href="#"><i class="fas fa-envelope"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="team-info">
                                <h3>Jaspher Gayo</h3>
                                <br>
                                <p class="team-role">Business Process Management</p>
                                <p class="team-contact">
                                    <i class="fas fa-envelope"></i> jgayo@kld.edu.ph
                                </p>
                                <p class="team-contact">
                                    <i class="fas fa-phone"></i> 09123456789
                                </p>
                            </div>
                        </div>
                        
                        <!-- Team Member 5 -->
                        <div class="team-card">
                            <div class="team-image">
                                <img src="aira.png" alt="Aira S. Galusong">
                                <div class="team-overlay">
                                    <div class="social-icons">
                                        <a href="#"><i class="fab fa-linkedin"></i></a>
                                        <a href="#"><i class="fas fa-envelope"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="team-info">
                                <h3>Aira S. Galusong</h3>
                                <br>
                                <p class="team-role">UX Designer</p>
                                <p class="team-contact">
                                    <i class="fas fa-envelope"></i> agalusong@kld.edu.ph
                                </p>
                                <p class="team-contact">
                                    <i class="fas fa-phone"></i> 09123456789
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="carousel-controls">
                        <button class="carousel-btn prev">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div class="carousel-dots">
                            <span class="dot active" data-index="0"></span>
                            <span class="dot" data-index="1"></span>
                            <span class="dot" data-index="2"></span>
                            <span class="dot" data-index="3"></span>
                            <span class="dot" data-index="4"></span>
                        </div>
                        <button class="carousel-btn next">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="faq-section">
        <div class="container">
            <div class="section-header">
                <h2>Frequently Asked <span class="highlight">Questions</span></h2>
                <p>Find answers to common questions about our system</p>
            </div>
            
            <div class="faq-container">
                <?php
             $faqs = [
    ["What is QR Violation Recorder?", 
     "QR Violation Recorder is a student-created system that uses QR codes to help track and manage student violations at Kolehiyo ng Lungsod ng Dasmariñas."],

    ["Is QR Violation Recorder an official school system?", 
     "No. QR Violation Recorder is a student project developed as part of an academic requirement. It is not officially operated by the school administration."],

    ["Who scans my QR code?", 
     "The school guard scans it during a violation incident, and authorized faculty scan it during clearance day to check your records."],

    ["Is my data safe?", 
     "Yes. Your data is stored securely and is only accessed by authorized personnel for clearance and record-checking purposes."],

    ["Can I see my violations?", 
     "Yes. You can view your record through logging into our website using your student credentials."],

    ["What if I have no violations?", 
     "When scanned during clearance, your QR will simply show no records, making the process faster."],

    ["Can my QR code be replaced if lost?", 
     "Yes. You may request a replacement from the project team or the designated faculty in charge of QR Violation Recorder."],

    ["Can parents view the violation records?", 
     "After logging in our website, parents can view their child's violation records for transparency."],

    ["What devices can scan the QR code?", 
     "Any smartphone or tablet with a camera and QR scanning capability can scan the QR code."],

    ["Who developed QR Violation Recorder?", 
     "QR Violation Recorder was developed by 2nd year students of the BS Information System program, Section BSIS 209 (Group 4), as part of their academic project to improve campus systems."]
];

                foreach ($faqs as $idx => $f) {
                ?>
                <div class="faq-item">
                    <div class="faq-question">
                        <span class="faq-number">Q<?php echo $idx + 1; ?></span>
                        <h3><?php echo $f[0]; ?></h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p><?php echo $f[1]; ?></p>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <!-- Contact Section -->
<section id="contact" class="contact-section">
    <div class="container">
        <div class="section-header">
            <h2>Get In <span class="highlight">Touch</span></h2>
            <p>We're here to help with any questions about our system</p>
        </div>
        
        <?php if (isset($_SESSION['feedback_sent']) && $_SESSION['feedback_sent']): ?>
            <div style="background: #d1fae5; padding: 20px; border-radius: 10px; margin-bottom: 30px; border-left: 4px solid #10B981;">
                <h3 style="color: #065f46; margin-bottom: 10px;"><i class="fas fa-check-circle"></i> Thank You!</h3>
                <p style="color: #065f46;">Your feedback has been sent successfully. We'll get back to you soon.</p>
            </div>
            <?php unset($_SESSION['feedback_sent']); ?>
        <?php endif; ?>
        
        <div class="contact-grid">
            <div class="contact-info">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-details">
                        <h3>Email Us</h3>
                        <p>qrviolationrecorder@gmail.com</p>
                        <small>Response within 24 hours</small>
                    </div>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-details">
                        <h3>Call Us</h3>
                        <p>+63 912 345 6789</p>
                        <small>Mon-Fri, 9AM-6PM</small>
                    </div>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-details">
                        <h3>Visit Us</h3>
                        <p>143, Tech Industry, Cavite, Philippines</p>
                        <small>By appointment only</small>
                    </div>
                </div>
            </div>
            
            <div class="contact-form">
                <form id="contactForm" method="POST" action="process_contact.php">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Your Name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Your Email" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="subject" placeholder="Subject" required>
                    </div>
                    <div class="form-group">
                        <textarea name="message" placeholder="Your Message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Transform Your Campus Management?</h2>
                <p>Join hundreds of institutions using QR Violation Recorder for efficient discipline management.</p>
                <div class="cta-buttons">
                    <a href="features.php" class="btn btn-light">Explore Features</a>
                    <a href="demo.php" class="btn btn-primary">Request Demo</a>
                </div>
            </div>
        </div>
    </section>

    <script>
    // Modal functionality
    const privacyBtn = document.getElementById('privacyBtn');
    const privacyModal = document.getElementById('privacyModal');
    const closeModalBtns = document.querySelectorAll('.close-modal');

    if (privacyBtn) {
        privacyBtn.addEventListener('click', () => {
            privacyModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    }

    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            privacyModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
    });

    window.addEventListener('click', (e) => {
        if (e.target === privacyModal) {
            privacyModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });

    // FAQ functionality
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            // Close all other items
            faqItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
            
            // Toggle current item
            item.classList.toggle('active');
        });
    });

    // FIXED TEAM CAROUSEL
    const carouselTrack = document.querySelector('.carousel-track');
    const teamCards = document.querySelectorAll('.team-card');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.querySelector('.carousel-btn.prev');
    const nextBtn = document.querySelector('.carousel-btn.next');
    
    let currentIndex = 0;
    let autoRotateInterval;
    const totalCards = teamCards.length;
    
    // Calculate how many cards to show based on screen size
    function getCardsToShow() {
        if (window.innerWidth <= 480) return 1;
        if (window.innerWidth <= 768) return 1;
        if (window.innerWidth <= 1024) return 2;
        return 3;
    }
    
    function updateCarousel() {
        const cardsToShow = getCardsToShow();
        const cardWidth = 100 / cardsToShow;
        
        // Calculate the correct position
        let translateXValue = 0;
        
        if (cardsToShow === 1) {
            translateXValue = currentIndex * 100;
        } else if (cardsToShow === 2) {
            translateXValue = currentIndex * 50;
            if (currentIndex > totalCards - 2) {
                translateXValue = (totalCards - 2) * 50;
            }
        } else {
            translateXValue = currentIndex * 33.333;
            if (currentIndex > totalCards - 3) {
                translateXValue = (totalCards - 3) * 33.333;
            }
        }
        
        carouselTrack.style.transform = `translateX(-${translateXValue}%)`;
        
        // Update active states for cards
        teamCards.forEach((card, index) => {
            card.classList.remove('active');
            if (index >= currentIndex && index < currentIndex + cardsToShow) {
                card.classList.add('active');
            }
        });
        
        // Update dots
        dots.forEach((dot, index) => {
            dot.classList.remove('active');
            if (index === currentIndex) {
                dot.classList.add('active');
            }
        });
        
        // Update button states
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex >= totalCards - cardsToShow;
    }
    
    function nextSlide() {
        const cardsToShow = getCardsToShow();
        if (currentIndex < totalCards - cardsToShow) {
            currentIndex++;
        } else {
            currentIndex = 0; // Loop back to start
        }
        updateCarousel();
    }
    
    function prevSlide() {
        if (currentIndex > 0) {
            currentIndex--;
        } else {
            const cardsToShow = getCardsToShow();
            currentIndex = totalCards - cardsToShow; // Loop to end
        }
        updateCarousel();
    }
    
    // Event Listeners
    if (prevBtn && nextBtn) {
        prevBtn.addEventListener('click', prevSlide);
        nextBtn.addEventListener('click', nextSlide);
    }
    
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            currentIndex = index;
            updateCarousel();
            resetAutoRotate();
        });
    });
    
    // Auto rotate
    function startAutoRotate() {
        autoRotateInterval = setInterval(nextSlide, 5000);
    }
    
    function resetAutoRotate() {
        clearInterval(autoRotateInterval);
        startAutoRotate();
    }
    
    // Pause auto-rotate on hover
    const carouselContainer = document.querySelector('.carousel-container');
    if (carouselContainer) {
        carouselContainer.addEventListener('mouseenter', () => {
            clearInterval(autoRotateInterval);
        });
        
        carouselContainer.addEventListener('mouseleave', () => {
            startAutoRotate();
        });
    }
    
    // Form submission

    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                window.scrollTo({
                    top: targetElement.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Initialize on load
    window.addEventListener('load', () => {
        updateCarousel();
        startAutoRotate();
    });
    
    // Update on resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            updateCarousel();
        }, 100);
    });
    </script>
    
    <?php include 'footer.php'; ?>
</body>
</html>