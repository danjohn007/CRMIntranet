(function () {
    const nav = document.querySelector('.landing-nav');
    const toggle = document.querySelector('[data-landing-toggle]');

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            nav.classList.toggle('is-open');
        });

        document.querySelectorAll('.landing-nav a').forEach(function (link) {
            link.addEventListener('click', function () {
                nav.classList.remove('is-open');
            });
        });
    }

    const revealItems = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) {
        revealItems.forEach(function (item) { item.classList.add('is-visible'); });
        return;
    }

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    revealItems.forEach(function (item) { observer.observe(item); });
})();
