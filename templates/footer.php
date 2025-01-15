    <!-- Footer -->
    <footer class="bg-white border-t mt-auto">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="text-gray-500 text-sm">
                    &copy; <?php echo date('Y'); ?> Eye-Stock. Tous droits réservés.
                </div>
                <div class="flex items-center space-x-4">
                    <a href="#" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Politique de confidentialité</span>
                        <i class="fas fa-shield-alt"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Conditions d'utilisation</span>
                        <i class="fas fa-file-contract"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Contact</span>
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Core Scripts -->
    <script src="<?php echo SITE_URL; ?>/assets/js/core.js"></script>
    
    <!-- Custom Scripts for Current Page -->
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Page-specific Script -->
    <?php if (isset($pageScript)): ?>
        <script>
            <?php echo $pageScript; ?>
        </script>
    <?php endif; ?>
</body>
</html>
