</main>

<footer class="site-footer" id="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a class="logo" href="/index.php">Atelier</a>
                <p style="margin-top:14px">Clothing for everyday wear. This store is a teaching lab used in a cybersecurity course.</p>
            </div>
            <div>
                <h4>Shop</h4>
                <ul>
                    <li><a href="/index.php#new-arrivals">New Arrivals</a></li>
                    <li><a href="/cart.php">Cart</a></li>
                    <li><a href="/upload.php">Upload image</a></li>
                </ul>
            </div>
            <div>
                <h4>Help</h4>
                <ul>
                    <li><a href="/login.php">Sign in</a></li>
                    <li><a href="/register.php">Create account</a></li>
                    <li><a href="/profile.php">My profile</a></li>
                </ul>
            </div>
            <div>
                <h4>About</h4>
                <ul>
                    <li><a href="/index.php">Our story</a></li>
                    <li><a href="/index.php#categories">Categories</a></li>
                </ul>
            </div>
        </div>
        <div class="copy">&copy; <?= date('Y') ?> Atelier. ShopLab is for classroom use only, inside an isolated local network.</div>
    </div>
</footer>

<script>
(function () {
    var header = document.querySelector('.site-header');

    // Menu on small screens
    var menuToggle = document.querySelector('[data-menu-toggle]');
    if (menuToggle && header) {
        menuToggle.addEventListener('click', function () {
            var open = header.classList.toggle('menu-open');
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Search bar
    var searchToggle = document.querySelector('[data-search-toggle]');
    var searchBar = document.querySelector('.search-bar');
    if (searchToggle && searchBar) {
        searchToggle.addEventListener('click', function () {
            var open = searchBar.classList.toggle('open');
            searchToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                var input = searchBar.querySelector('input');
                if (input) { input.focus(); }
            }
        });
    }

    // Close the account menu when clicking outside it
    document.addEventListener('click', function (e) {
        document.querySelectorAll('details.account[open]').forEach(function (d) {
            if (!d.contains(e.target)) { d.removeAttribute('open'); }
        });
    });

    // Plus and minus buttons for quantity
    document.querySelectorAll('.qty').forEach(function (box) {
        var input = box.querySelector('input');
        var form = box.closest('form');
        if (!input) { return; }

        box.querySelectorAll('[data-step]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var min = parseInt(box.getAttribute('data-min'), 10);
                var value = (parseInt(input.value, 10) || 0) + parseInt(btn.getAttribute('data-step'), 10);
                if (!isNaN(min) && value < min) { value = min; }
                input.value = value;
                if (box.hasAttribute('data-autosubmit') && form) { form.submit(); }
            });
        });

        if (box.hasAttribute('data-autosubmit') && form) {
            input.addEventListener('change', function () { form.submit(); });
        }
    });
})();
</script>
</body>
</html>
