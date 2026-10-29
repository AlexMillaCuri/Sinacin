
document.addEventListener('DOMContentLoaded', () => {
    const carousel = document.querySelector('.sinacin-noticias-carrusel');
    if (!carousel) return;

    const track = carousel.querySelector('.sinacin-noticias-track');
    const prev = carousel.querySelector('.sinacin-noticias-anterior');
    const next = carousel.querySelector('.sinacin-noticias-siguiente');

    const originals = Array.from(
        track.querySelectorAll('.sinacin-noticia-card')
    );

    if (originals.length < 2) {
        prev?.setAttribute('disabled', '');
        next?.setAttribute('disabled', '');
        return;
    }

    const total = originals.length;
    const clonesCount = total;

    // Copias al principio y al final para el ciclo infinito.
    const before = originals.map(card => card.cloneNode(true));
    const after = originals.map(card => card.cloneNode(true));

    before.forEach(card => {
        card.setAttribute('aria-hidden', 'true');
        card.inert = true;
    });

    after.forEach(card => {
        card.setAttribute('aria-hidden', 'true');
        card.inert = true;
    });

    before.forEach(card => track.appendChild(card));
    after.forEach(card => track.appendChild(card));

    // Orden correcto: copias anteriores, originales, copias siguientes.
    before.forEach(card => track.insertBefore(card, originals[0]));

    let index = clonesCount;
    let timer = null;
    let animating = false;

    const allCards = Array.from(track.children);

    function getStep() {
        const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
        return allCards[0].getBoundingClientRect().width + gap;
    }

    function setPosition(animated = true) {
        track.style.transition = animated
            ? 'transform 800ms ease-in-out'
            : 'none';

        track.style.transform = `translateX(-${index * getStep()}px)`;
    }

    function move(direction) {
        if (animating) return;

        animating = true;
        index += direction;
        setPosition(true);
    }

    track.addEventListener('transitionend', event => {
        if (event.target !== track || event.propertyName !== 'transform') {
            return;
        }

        if (index >= clonesCount + total) {
            index -= total;
            setPosition(false);
        } else if (index < clonesCount) {
            index += total;
            setPosition(false);
        }

        animating = false;
    });

    function startTimer() {
        clearInterval(timer);

        timer = setInterval(() => {
            move(1);
        }, 5000);
    }

    prev?.addEventListener('click', () => {
        move(-1);
        startTimer();
    });

    next?.addEventListener('click', () => {
        move(1);
        startTimer();
    });

    window.addEventListener('resize', () => {
        setPosition(false);
    });

    setPosition(false);
    startTimer();
});
