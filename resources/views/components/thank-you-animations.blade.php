{{-- Thank You Page Animations Component --}}
<style>
    @keyframes confetti-fall {
        0% {
            transform: translateY(-100vh) rotate(0deg);
            opacity: 1;
        }
        100% {
            transform: translateY(100vh) rotate(720deg);
            opacity: 0;
        }
    }
    
    @keyframes fade-in-up {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slide-in-left {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slide-in-right {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes pulse-glow {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
        }
    }
    
    @keyframes progress-fill {
        from {
            width: 0%;
        }
        to {
            width: var(--progress-width, 100%);
        }
    }
    
    @keyframes bounce-in {
        0% {
            transform: scale(0.3);
            opacity: 0;
        }
        50% {
            transform: scale(1.05);
        }
        70% {
            transform: scale(0.9);
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }
    
    @keyframes shimmer {
        0% {
            background-position: -200px 0;
        }
        100% {
            background-position: calc(200px + 100%) 0;
        }
    }
    
    .animate-confetti {
        animation: confetti-fall 3s linear forwards;
    }
    
    .animate-fade-in-up {
        animation: fade-in-up 0.8s ease-out forwards;
    }
    
    .animate-slide-in-left {
        animation: slide-in-left 0.6s ease-out forwards;
    }
    
    .animate-slide-in-right {
        animation: slide-in-right 0.6s ease-out forwards;
    }
    
    .animate-pulse-glow {
        animation: pulse-glow 2s infinite;
    }
    
    .animate-progress-fill {
        animation: progress-fill 2s ease-out forwards;
    }
    
    .animate-bounce-in {
        animation: bounce-in 0.8s ease-out forwards;
    }
    
    .animate-shimmer {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200px 100%;
        animation: shimmer 1.5s infinite;
    }
    
    .animate-delay-100 {
        animation-delay: 0.1s;
    }
    
    .animate-delay-200 {
        animation-delay: 0.2s;
    }
    
    .animate-delay-300 {
        animation-delay: 0.3s;
    }
    
    .animate-delay-400 {
        animation-delay: 0.4s;
    }
    
    .animate-delay-500 {
        animation-delay: 0.5s;
    }
    
    .animate-duration-300 {
        animation-duration: 0.3s;
    }
    
    .animate-duration-500 {
        animation-duration: 0.5s;
    }
    
    .animate-duration-700 {
        animation-duration: 0.7s;
    }
    
    .animate-duration-1000 {
        animation-duration: 1s;
    }
    
    .animate-duration-2000 {
        animation-duration: 2s;
    }
    
    .animate-duration-3000 {
        animation-duration: 3s;
    }
    
    .animate-iteration-infinite {
        animation-iteration-count: infinite;
    }
    
    .animate-iteration-2 {
        animation-iteration-count: 2;
    }
    
    .animate-iteration-3 {
        animation-iteration-count: 3;
    }
    
    .animate-fill-forwards {
        animation-fill-mode: forwards;
    }
    
    .animate-fill-backwards {
        animation-fill-mode: backwards;
    }
    
    .animate-fill-both {
        animation-fill-mode: both;
    }
    
    .animate-ease-linear {
        animation-timing-function: linear;
    }
    
    .animate-ease-in {
        animation-timing-function: ease-in;
    }
    
    .animate-ease-out {
        animation-timing-function: ease-out;
    }
    
    .animate-ease-in-out {
        animation-timing-function: ease-in-out;
    }
    
    .animate-ease-bounce {
        animation-timing-function: cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
    
    .animate-ease-elastic {
        animation-timing-function: cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    /* Hover animations */
    .hover-lift:hover {
        transform: translateY(-5px);
        transition: transform 0.3s ease;
    }
    
    .hover-glow:hover {
        box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        transition: box-shadow 0.3s ease;
    }
    
    .hover-scale:hover {
        transform: scale(1.05);
        transition: transform 0.3s ease;
    }
    
    .hover-rotate:hover {
        transform: rotate(5deg);
        transition: transform 0.3s ease;
    }
    
    .hover-bounce:hover {
        animation: bounce 0.6s ease;
    }
    
    @keyframes bounce {
        0%, 20%, 53%, 80%, 100% {
            transform: translateY(0);
        }
        40%, 43% {
            transform: translateY(-10px);
        }
        70% {
            transform: translateY(-5px);
        }
        90% {
            transform: translateY(-2px);
        }
    }
    
    /* Loading animations */
    .loading-dots {
        display: inline-block;
    }
    
    .loading-dots::after {
        content: '';
        animation: loading-dots 1.5s infinite;
    }
    
    @keyframes loading-dots {
        0%, 20% {
            content: '';
        }
        40% {
            content: '.';
        }
        60% {
            content: '..';
        }
        80%, 100% {
            content: '...';
        }
    }
    
    .loading-spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Success checkmark animation */
    .checkmark {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: block;
        stroke-width: 2;
        stroke: #4CAF50;
        stroke-miterlimit: 10;
        margin: 10% auto;
        box-shadow: inset 0px 0px 0px #4CAF50;
        animation: checkmark-fill 0.4s ease-in-out 0.4s forwards, checkmark-scale 0.3s ease-in-out 0.9s both;
    }
    
    .checkmark-circle {
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #4CAF50;
        fill: none;
        animation: checkmark-stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }
    
    .checkmark-check {
        transform-origin: 50% 50%;
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        animation: checkmark-stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }
    
    @keyframes checkmark-stroke {
        100% {
            stroke-dashoffset: 0;
        }
    }
    
    @keyframes checkmark-scale {
        0%, 100% {
            transform: none;
        }
        50% {
            transform: scale3d(1.1, 1.1, 1);
        }
    }
    
    @keyframes checkmark-fill {
        100% {
            box-shadow: inset 0px 0px 0px 30px #4CAF50;
        }
    }
    
    /* Progress bar animation */
    .progress-bar {
        width: 100%;
        height: 8px;
        background-color: #e0e0e0;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #4CAF50, #8BC34A);
        border-radius: 4px;
        transition: width 0.3s ease;
    }
    
    .progress-fill.animate {
        animation: progress-fill 2s ease-out forwards;
    }
    
    /* Floating elements */
    .float {
        animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-10px);
        }
    }
    
    /* Gradient animation */
    .gradient-animate {
        background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
        background-size: 400% 400%;
        animation: gradient 15s ease infinite;
    }
    
    @keyframes gradient {
        0% {
            background-position: 0% 50%;
        }
        50% {
            background-position: 100% 50%;
        }
        100% {
            background-position: 0% 50%;
        }
    }
</style>

{{-- Confetti Animation Script --}}
<script>
    function createConfetti() {
        const confettiContainer = document.getElementById('confetti');
        if (!confettiContainer) return;
        
        const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'];
        const shapes = ['circle', 'square', 'triangle'];
        
        for (let i = 0; i < 50; i++) {
            setTimeout(() => {
                const particle = document.createElement('div');
                const color = colors[Math.floor(Math.random() * colors.length)];
                const shape = shapes[Math.floor(Math.random() * shapes.length)];
                
                particle.style.position = 'absolute';
                particle.style.width = Math.random() * 10 + 5 + 'px';
                particle.style.height = particle.style.width;
                particle.style.backgroundColor = color;
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = '-10px';
                particle.style.zIndex = '1000';
                particle.style.pointerEvents = 'none';
                
                // Apply shape
                if (shape === 'circle') {
                    particle.style.borderRadius = '50%';
                } else if (shape === 'triangle') {
                    particle.style.clipPath = 'polygon(50% 0%, 0% 100%, 100% 100%)';
                }
                
                particle.classList.add('animate-confetti');
                particle.style.animationDuration = Math.random() * 3 + 2 + 's';
                
                confettiContainer.appendChild(particle);
                
                // Remove particle after animation
                setTimeout(() => {
                    if (particle.parentNode) {
                        particle.parentNode.removeChild(particle);
                    }
                }, 5000);
            }, i * 50);
        }
    }
    
    function createSuccessAnimation() {
        // Create success checkmark
        const checkmark = document.createElement('div');
        checkmark.innerHTML = `
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="checkmark-check" fill="none" d="m14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
        `;
        checkmark.style.position = 'fixed';
        checkmark.style.top = '50%';
        checkmark.style.left = '50%';
        checkmark.style.transform = 'translate(-50%, -50%)';
        checkmark.style.zIndex = '9999';
        checkmark.style.pointerEvents = 'none';
        
        document.body.appendChild(checkmark);
        
        // Remove after animation
        setTimeout(() => {
            if (checkmark.parentNode) {
                checkmark.parentNode.removeChild(checkmark);
            }
        }, 2000);
    }
    
    function animateProgressBar(progressBar, targetProgress) {
        if (!progressBar) return;
        
        const progressFill = progressBar.querySelector('.progress-fill');
        if (!progressFill) return;
        
        progressFill.style.width = '0%';
        
        setTimeout(() => {
            progressFill.style.width = targetProgress + '%';
        }, 100);
    }
    
    function animateCounter(element, start, end, duration) {
        if (!element) return;
        
        const startTime = performance.now();
        const startValue = start;
        const endValue = end;
        
        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const currentValue = startValue + (endValue - startValue) * progress;
            element.textContent = Math.floor(currentValue);
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        }
        
        requestAnimationFrame(updateCounter);
    }
    
    function animateElementsOnScroll() {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                }
            });
        });
        
        elements.forEach(element => {
            observer.observe(element);
        });
    }
    
    // Initialize animations when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Trigger confetti animation
        setTimeout(createConfetti, 500);
        
        // Create success animation
        setTimeout(createSuccessAnimation, 1000);
        
        // Animate progress bars
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(bar => {
            const targetProgress = bar.dataset.progress || 75;
            animateProgressBar(bar, targetProgress);
        });
        
        // Animate counters
        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            const endValue = parseInt(counter.dataset.end) || 100;
            animateCounter(counter, 0, endValue, 2000);
        });
        
        // Initialize scroll animations
        animateElementsOnScroll();
    });
</script>
