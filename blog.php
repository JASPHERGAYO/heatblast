<?php
// Start session at the VERY TOP
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog & Updates | QR Violation Recorder</title>
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

    /* Blog Hero */
    .blog-hero {
        padding: 100px 0;
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-dark) 100%);
        color: var(--white);
        text-align: center;
    }

    .blog-hero h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--white);
    }

    .blog-hero p {
        font-size: 1.25rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Blog Content */
    .blog-content {
        padding: 100px 0;
        background: var(--light-bg);
    }

    .blog-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
    }

    .blog-card {
        background: var(--white);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: var(--transition);
    }

    .blog-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-lg);
    }

    .blog-image {
        height: 200px;
        overflow: hidden;
    }

    .blog-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .blog-card:hover .blog-image img {
        transform: scale(1.1);
    }

    .blog-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: var(--primary-green);
        color: var(--white);
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .blog-info {
        padding: 25px;
    }

    .blog-meta {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
        color: var(--gray);
        font-size: 0.9rem;
    }

    .blog-meta i {
        color: var(--primary-green);
        margin-right: 5px;
    }

    .blog-title {
        font-size: 1.3rem;
        margin-bottom: 15px;
        color: var(--gray-dark);
        line-height: 1.4;
    }

    .blog-excerpt {
        color: var(--gray);
        margin-bottom: 20px;
        line-height: 1.6;
    }

    .read-more {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--primary-green);
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition);
    }

    .read-more:hover {
        gap: 12px;
    }

    /* Featured Article */
    .featured-article {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-bottom: 50px;
    }

    .featured-image {
        border-radius: 15px;
        overflow: hidden;
        height: 350px;
    }

    .featured-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .featured-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .featured-badge {
        display: inline-block;
        background: var(--accent-green);
        color: var(--primary-dark);
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .featured-title {
        font-size: 2rem;
        margin-bottom: 20px;
        color: var(--gray-dark);
        line-height: 1.3;
    }

    /* Newsletter */
    .newsletter-section {
        padding: 80px 0;
        background: var(--white);
        text-align: center;
    }

    .newsletter-box {
        max-width: 600px;
        margin: 0 auto;
        padding: 50px;
        background: var(--light-bg);
        border-radius: 15px;
        box-shadow: var(--shadow);
    }

    .newsletter-box h3 {
        font-size: 1.8rem;
        margin-bottom: 15px;
        color: var(--gray-dark);
    }

    .newsletter-box p {
        color: var(--gray);
        margin-bottom: 30px;
    }

    .newsletter-form {
        display: flex;
        gap: 10px;
        max-width: 400px;
        margin: 0 auto;
    }

    .newsletter-form input {
        flex: 1;
        padding: 15px;
        border: 2px solid var(--gray-light);
        border-radius: 8px;
        font-size: 1rem;
        transition: var(--transition);
    }

    .newsletter-form input:focus {
        outline: none;
        border-color: var(--primary-green);
    }

    .newsletter-form button {
        background: var(--primary-green);
        color: var(--white);
        border: none;
        border-radius: 8px;
        padding: 15px 30px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .newsletter-form button:hover {
        background: var(--primary-dark);
    }

    /* Responsive */
    @media (max-width: 992px) {
        .featured-article {
            grid-template-columns: 1fr;
        }
        
        .featured-image {
            height: 250px;
        }
    }

    @media (max-width: 768px) {
        .blog-hero h1 {
            font-size: 2.5rem;
        }
        
        .blog-grid {
            grid-template-columns: 1fr;
        }
        
        .newsletter-form {
            flex-direction: column;
        }
    }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <section class="blog-hero">
        <div class="container">
            <h1>Latest <span class="highlight">Updates</span> & Insights</h1>
            <p>Stay informed about campus discipline management and QR technology trends</p>
        </div>
    </section>

    <section class="blog-content">
        <div class="container">
            <!-- Featured Article -->
            <div class="featured-article">
                <div class="featured-image">
                    <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Digital Campus Management">
                    <span class="blog-badge">Featured</span>
                </div>
                <div class="featured-content">
                    <span class="featured-badge">Technology</span>
                    <h2 class="featured-title">How QR Technology is Revolutionizing Campus Discipline Management</h2>
                    <div class="blog-meta">
                        <span><i class="far fa-calendar"></i> March 15, 2024</span>
                        <span><i class="far fa-clock"></i> 5 min read</span>
                    </div>
                    <p class="blog-excerpt">Discover how educational institutions are leveraging QR codes to streamline violation recording, improve efficiency, and enhance campus security through digital transformation.</p>
                    <a href="#" class="read-more">Read Full Article <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            
            <!-- Blog Grid -->
            <div class="blog-grid">
                <!-- Article 1 -->
                <article class="blog-card">
                    <div class="blog-image">
                        <img src="https://images.unsplash.com/photo-1542744095-fcf48d80b0fd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Student Safety">
                        <span class="blog-badge">Safety</span>
                    </div>
                    <div class="blog-info">
                        <div class="blog-meta">
                            <span><i class="far fa-calendar"></i> March 12, 2024</span>
                            <span><i class="far fa-clock"></i> 4 min</span>
                        </div>
                        <h3 class="blog-title">Enhancing Student Safety with Digital Monitoring Systems</h3>
                        <p class="blog-excerpt">Learn how digital monitoring systems contribute to creating safer campus environments for students and staff through proactive measures.</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </article>
                
                <!-- Article 2 -->
                <article class="blog-card">
                    <div class="blog-image">
                        <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Data Analytics">
                        <span class="blog-badge">Analytics</span>
                    </div>
                    <div class="blog-info">
                        <div class="blog-meta">
                            <span><i class="far fa-calendar"></i> March 8, 2024</span>
                            <span><i class="far fa-clock"></i> 6 min</span>
                        </div>
                        <h3 class="blog-title">Using Data Analytics to Improve Campus Discipline Policies</h3>
                        <p class="blog-excerpt">How schools can leverage data from violation records to identify patterns and implement proactive measures for better discipline management.</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </article>
                
                <!-- Article 3 -->
                <article class="blog-card">
                    <div class="blog-image">
                        <img src="https://images.unsplash.com/photo-1559136555-9303baea8ebd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Mobile App">
                        <span class="blog-badge">Mobile</span>
                    </div>
                    <div class="blog-info">
                        <div class="blog-meta">
                            <span><i class="far fa-calendar"></i> March 5, 2024</span>
                            <span><i class="far fa-clock"></i> 3 min</span>
                        </div>
                        <h3 class="blog-title">Mobile App Features for Efficient Violation Recording</h3>
                        <p class="blog-excerpt">Explore the must-have features for mobile apps designed for campus discipline management and efficient violation recording.</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="newsletter-section">
        <div class="container">
            <div class="newsletter-box">
                <h3>Subscribe to Our Newsletter</h3>
                <p>Get the latest updates on campus discipline management and QR technology trends directly in your inbox.</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Enter your email address" required>
                    <button type="submit">Subscribe</button>
                </form>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
    
    <script>
    // Newsletter form submission
    document.querySelector('.newsletter-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = this.querySelector('input[type="email"]').value;
        
        // Show loading state
        const button = this.querySelector('button');
        const originalText = button.textContent;
        button.textContent = 'Subscribing...';
        button.disabled = true;
        
        // Simulate API call
        setTimeout(() => {
            alert('Thank you for subscribing to our newsletter!');
            this.reset();
            button.textContent = originalText;
            button.disabled = false;
        }, 1500);
    });
    </script>
</body>
</html>