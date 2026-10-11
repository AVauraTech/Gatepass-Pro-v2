</div> <!-- Closing main-content div -->

<footer id="siteFooter" aria-label="Site footer" role="contentinfo" tabindex="0">
    <div class="footer-content">
        <span class="footer-title" title="Click me 6 times for a secret!">GatePass Pro Nexus © <?php echo date("Y"); ?></span>
        <small class="footer-subtitle">Admin Portal — Securing Your Gateways</small>
    </div>
</footer>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Print functionality already exists in your main page scripts
// Just in case you want it globally here, keep it or remove below duplicate
function printPass() {
    var printContent = document.getElementById('printableArea').innerHTML;
    var originalContent = document.body.innerHTML;
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

// Easter egg: Clicking footer title 6 times quickly triggers sparkles & alert
(function() {
    const footerTitle = document.querySelector('.footer-title');
    let clicks = 0, timeout;

    footerTitle.addEventListener('click', () => {
        clicks++;
        if (clicks === 6) {
            triggerFooterEasterEgg();
            clicks = 0;
            clearTimeout(timeout);
        }
        clearTimeout(timeout);
        timeout = setTimeout(() => clicks = 0, 1500);
    });

    function triggerFooterEasterEgg() {
        const colors = ['#00ffc3', '#00ffaa', '#00bfa1', '#24ffd3', '#fff', '#00ecff'];
        const rect = footerTitle.getBoundingClientRect();

        for (let i = 0; i < 25; i++) {
            const sparkle = document.createElement('div');
            sparkle.classList.add('sparkle-footer');
            sparkle.style.position = 'absolute';
            sparkle.style.width = '8px';
            sparkle.style.height = '8px';
            sparkle.style.borderRadius = '50%';
            sparkle.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            sparkle.style.left = `${rect.left + window.scrollX + Math.random() * rect.width}px`;
            sparkle.style.top = `${rect.top + window.scrollY + Math.random() * rect.height}px`;
            sparkle.style.boxShadow = `0 0 8px 3px ${sparkle.style.backgroundColor}`;

            sparkle.style.animation = `sparkleFadeFooter 1.2s ease forwards`;
            document.body.appendChild(sparkle);

            setTimeout(() => sparkle.remove(), 1300);
        }

        setTimeout(() => {
            alert("✨ You've unlocked the GatePass Pro Nexus secret! Keep guarding the gates! ✨");
        }, 600);
    }
})();
</script>

<style>
    /* Footer styling */
    #siteFooter {
        background: rgba(14, 23, 31, 0.94);
        backdrop-filter: blur(15px);
        border-top: 1.5px solid rgba(0, 255, 195, 0.22);
        padding: 18px 24px;
        text-align: center;
        color: #00ffc3;
        user-select: none;
        font-family: 'Poppins', sans-serif;
        position: relative;
        font-weight: 600;
        font-size: 0.95rem;
        letter-spacing: 0.12em;
        box-shadow: 0 -3px 16px rgba(0,255,195,0.15);
        margin-left: 250px;
        transition: margin-left 0.3s ease;
        z-index: 1000;
    }
    @media (max-width: 900px) {
        #siteFooter {
            margin-left: 70px;
        }
    }
    #siteFooter:hover {
        background-color: rgba(0, 255, 195, 0.07);
        color: #00ffaa;
    }

    .footer-content {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: center;
        justify-content: center;
    }
    .footer-title {
        font-size: 1.15rem;
        font-weight: 800;
        cursor: pointer;
        letter-spacing: 0.15em;
        user-select: text;
    }
    .footer-subtitle {
        font-size: 0.86rem;
        font-weight: 400;
        color: rgba(0, 255, 195, 0.7);
        font-style: italic;
        user-select: none;
    }

    /* Sparkle animation */
    @keyframes sparkleFadeFooter {
        0% {
            opacity: 1;
            transform: scale(1);
        }
        70% {
            opacity: 0.9;
            transform: scale(1.7);
        }
        100% {
            opacity: 0;
            transform: scale(2.4) translateY(-50px);
        }
    }

    /* Responsive font scaling */
    @media (max-width: 480px) {
        #siteFooter {
            font-size: 0.88rem;
            padding: 14px 14px;
            letter-spacing: 0.10em;
        }
        .footer-title {
            font-size: 1rem;
        }
        .footer-subtitle {
            font-size: 0.74rem;
        }
    }
</style>
</body>
</html>
