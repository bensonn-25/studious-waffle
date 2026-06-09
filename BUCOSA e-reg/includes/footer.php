<?php
$isLoggedIn = isset($_SESSION['user_id']);
?>
<?php if ($isLoggedIn): ?>
            </div> <!-- End main-content-area -->
            
            <!-- Footer -->
            <footer>
                <div class="container-fluid">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> BUCOSA System. All Rights Reserved.</p>
                </div>
            </footer>
        </div> <!-- End content -->
    </div> <!-- End wrapper -->
<?php else: ?>
    </div> <!-- End auth-wrapper -->
<?php endif; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="/BUCOSA e-reg/assets/js/main.js"></script>
</body>
</html>
