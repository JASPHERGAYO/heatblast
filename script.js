// script.js
document.addEventListener('DOMContentLoaded', () => {
  // FAQ toggle
  document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
      const ans = btn.nextElementSibling;
      if (!ans) return;
      const showing = ans.style.display === 'block';
      
      document.querySelectorAll('.faq-answer').forEach(a => a.style.display = 'none');
      ans.style.display = showing ? 'none' : 'block';
      
      if (!showing) setTimeout(()=> btn.scrollIntoView({behavior:'smooth', block:'center'}), 120);
    });
  });

// Carousel
let index = 0;
const items = Array.from(document.querySelectorAll('.carousel-item'));
const nextBtn = document.querySelector('.carousel-btn.next');
const prevBtn = document.querySelector('.carousel-btn.prev');

function show(i) {
  items.forEach((it, idx) => it.classList.toggle('active', idx === i));
}

if (items.length) show(0);

if (nextBtn) nextBtn.addEventListener('click', () => {
  index = (index + 1) % items.length;
  show(index);
});

if (prevBtn) prevBtn.addEventListener('click', () => {
  index = (index - 1 + items.length) % items.length;
  show(index);
});

// Automatic slide every 6 seconds
setInterval(() => {
  index = (index + 1) % items.length;
  show(index);
}, 6000);



  const hamburger = document.querySelector('.hamburger');
  const navLinks = document.querySelector('.nav-links');
  if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
      if (navLinks.style.display === 'flex') navLinks.style.display = 'none';
      else {
        navLinks.style.display = 'flex';
        navLinks.style.flexDirection = 'column';
      }
    });
  }
});

function applyScrollAnimations() {
  const animatedEls = document.querySelectorAll('.scroll-animate');

  animatedEls.forEach(el => {
    const rect = el.getBoundingClientRect();
    const inView = rect.top < window.innerHeight - 100;

    if (inView) {
      el.classList.add('visible');
    }
  });
}

window.addEventListener('scroll', applyScrollAnimations);
window.addEventListener('load', applyScrollAnimations);