
document.addEventListener('DOMContentLoaded', () => {
    const carousel = document.querySelector('.sinacin-slider');
    if (!carousel) return;

    const slides = Array.from(
        carousel.querySelectorAll('.sinacin-slide')
    );

    const dots = Array.from(
        carousel.querySelectorAll('.sinacin-slider-dot')
    );

    const prev = carousel.querySelector('.sinacin-slider-prev');
    const next = carousel.querySelector('.sinacin-slider-next');

    if (!slides.length) return;

    let current = 0;
    let timer = null;

    function showSlide(index) {
        current = (index + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            const active = i === current;

            slide.classList.toggle('active', active);
            slide.setAttribute('aria-hidden', String(!active));
            slide.inert = !active;
        });

        dots.forEach((dot, i) => {
            const active = i === current;

            dot.classList.toggle('active', active);
            dot.setAttribute('aria-current', String(active));
        });
    }

    function startTimer() {
        clearInterval(timer);

        if (slides.length <= 1) return;

        timer = setInterval(() => {
            showSlide(current + 1);
        }, 6000);
    }

    prev?.addEventListener('click', () => {
        showSlide(current - 1);
        startTimer();
    });

    next?.addEventListener('click', () => {
        showSlide(current + 1);
        startTimer();
    });

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            showSlide(index);
            startTimer();
        });
    });

    showSlide(0);
    startTimer();
});
