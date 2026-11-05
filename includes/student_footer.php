<?php
/**
 * CBT System - Student Footer Template
 */
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}
?>
    </div> <!-- Close container -->
    </div> <!-- Close student-page-wrap -->
    
    <!-- Footer -->
    <footer class="site-footer bg-white border-top py-3">
        <div class="container-fluid text-center">
            <small class="text-muted d-block">&copy; <?php echo date('Y'); ?> <?php echo e(institution_name()); ?> - Student Portal. All rights reserved.</small>
            <?php echo powered_by_credit('mt-1'); ?>
        </div>
    </footer>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>