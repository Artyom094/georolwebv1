    <!-- Theme toggle -->
    <button id="theme-toggle" class="theme-toggle" title="Cambiar tema claro/oscuro">
        <i class="bi bi-moon-fill"></i>
    </button>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container-fluid px-3 px-lg-4">
            <div class="row align-items-center g-2">
                <!-- Brand -->
                <div class="col-12 col-md-4">
                    <div class="footer-brand">
                        <span style="width:22px;height:22px;background:linear-gradient(135deg,#0d6efd,#6610f2);border-radius:5px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-globe2 text-white" style="font-size:.65rem"></i>
                        </span>
                        Georol
                        <small class="text-muted fw-normal" style="font-size:.75rem">Sistema de Gestión Geopolítica</small>
                    </div>
                </div>
                <!-- Version -->
                <div class="col-12 col-md-4 text-md-center">
                    <span class="footer-version-pill">
                        <i class="bi bi-code-slash"></i>
                        <?= GEOROL_VERSION ?> &nbsp;·&nbsp; Build <?= GEOROL_BUILD ?>
                    </span>
                </div>
                <!-- Copyright -->
                <div class="col-12 col-md-4 text-md-end">
                    <small>&copy; <?= date('Y') ?> Georol &mdash; Desarrollado para Discord</small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
