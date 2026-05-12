<?php
/** UIU Nest — Common Footer */
?>
        </main>
    </div>

    <!-- Toast container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
<?php if (isset($includeMapJS) && $includeMapJS): ?>
    <script src="<?= APP_URL ?>/assets/js/map.js"></script>
<?php endif; ?>
<?php if (isset($includeListingsJS) && $includeListingsJS): ?>
    <script src="<?= APP_URL ?>/assets/js/listings.js"></script>
<?php endif; ?>
</body>
</html>
