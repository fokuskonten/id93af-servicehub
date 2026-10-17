</div> <!-- End main-content -->
    </div> <!-- End main-wrapper -->

    <!-- Footer (only for public pages) -->
    <?php 
    $is_logged_in = function_exists('isLoggedIn') && isLoggedIn();
    if (!$is_logged_in): 
    ?>
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-tools me-2"></i><?php echo getSiteName(); ?></h5>
                    <p class="mb-0"><?php echo getSiteDescription(); ?></p>
                    <p class="small text-muted">Melayani dengan profesional dan terpercaya</p>
                </div>
                <div class="col-md-3">
                    <h6>Layanan</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-light text-decoration-none">Servis Laptop</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Servis Komputer</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Servis Handphone</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Instalasi Software</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Kontak</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-2"></i><?php echo getContactPhone(); ?></li>
                        <li><i class="fas fa-envelope me-2"></i><?php echo getContactEmail(); ?></li>
                        <li><i class="fas fa-map-marker-alt me-2"></i><?php echo getContactAddress(); ?></li>
                    </ul>
                </div>
            </div>
            <hr class="my-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo CopyrightProtection::getCopyrightYear(); ?> <?php echo CopyrightProtection::getCopyrightLink(); ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-light me-3"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="text-light"><i class="fab fa-telegram"></i></a>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>
</body>
</html>