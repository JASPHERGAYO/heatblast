// Modal functionality
const privacyBtn = document.getElementById('privacyBtn');
const privacyModal = document.getElementById('privacyModal');
const closeModalBtns = document.querySelectorAll('.close-modal');

privacyBtn.addEventListener('click', () => {
    privacyModal.style.display = 'block';
    document.body.style.overflow = 'hidden';
});

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

// Team carousel
const carouselTrack = document.querySelector('.carousel-track');
const teamCards = document.querySelectorAll('.team-card');
const dots = document.querySelectorAll('.dot');
const prevBtn = document.querySelector('.carousel-btn.prev');
const nextBtn = document.querySelector('.carousel-btn.next');
let currentIndex = 0;
let cardWidth = teamCards[0].offsetWidth + 40; // Including margin

function updateCarousel() {
    carouselTrack.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
    
    // Update active states
    teamCards.forEach((card, index) => {
        card.classList.toggle('active', index === currentIndex);
    });
    
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentIndex);
    });
}

prevBtn.addEventListener('click', () => {
    currentIndex = currentIndex > 0 ? currentIndex - 1 : teamCards.length - 1;
    updateCarousel();
});

nextBtn.addEventListener('click', () => {
    currentIndex = currentIndex < teamCards.length - 1 ? currentIndex + 1 : 0;
    updateCarousel();
});

dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
        currentIndex = index;
        updateCarousel();
    });
});

// Update card width on resize
window.addEventListener('resize', () => {
    cardWidth = teamCards[0].offsetWidth + 40;
    updateCarousel();
});

// Form submission
const contactForm = document.getElementById('contactForm');

contactForm.addEventListener('submit', (e) => {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(contactForm);
    const data = Object.fromEntries(formData);
    
    // Here you would typically send the data to a server
    console.log('Form submitted:', data);
    
    // Show success message
    alert('Thank you for your message! We will get back to you soon.');
    contactForm.reset();
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
        }
    });
});

// Scroll animations
const observerOptions = {
    root: null,
    rootMargin: '0px',
    threshold: 0.1
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
        }
    });
}, observerOptions);

// Observe elements for animation
document.querySelectorAll('.scroll-animate').forEach(el => {
    observer.observe(el);
});

// Initialize carousel
updateCarousel();

// Auto-rotate carousel
let autoRotate = setInterval(() => {
    currentIndex = currentIndex < teamCards.length - 1 ? currentIndex + 1 : 0;
    updateCarousel();
}, 5000);

// Pause auto-rotate on hover
const carouselContainer = document.querySelector('.carousel-container');
carouselContainer.addEventListener('mouseenter', () => {
    clearInterval(autoRotate);
});

carouselContainer.addEventListener('mouseleave', () => {
    autoRotate = setInterval(() => {
        currentIndex = currentIndex < teamCards.length - 1 ? currentIndex + 1 : 0;
        updateCarousel();
    }, 5000);
});