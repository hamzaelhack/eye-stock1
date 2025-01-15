<div class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-4">
            <!-- Left Section -->
            <div class="flex items-center">
                <!-- Mobile Menu Button -->
                <button type="button" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" id="mobile-menu-button">
                    <span class="sr-only">Ouvrir le menu</span>
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- Page Title -->
                <h1 class="text-2xl font-bold text-gray-800 ml-2 lg:ml-0">
                    <?php echo $pageTitle ?? 'Eye-Stock'; ?>
                </h1>
            </div>
            
            <!-- Right Section -->
            <div class="flex items-center space-x-4">
                <!-- Search -->
                <div class="hidden md:block">
                    <div class="relative">
                        <input type="text" 
                               class="search-bar" 
                               placeholder="Rechercher..."
                               id="global-search">
                        <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                
                <!-- Notifications -->
                <div class="relative">
                    <button type="button" class="p-2 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <span class="sr-only">Voir les notifications</span>
                        <i class="fas fa-bell"></i>
                        <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-white"></span>
                    </button>
                </div>
                
                <!-- Profile Dropdown -->
                <div class="relative" id="profile-menu">
                    <button type="button" class="flex items-center space-x-3 focus:outline-none" id="profile-menu-button">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-user-circle text-gray-500"></i>
                            </div>
                            <span class="hidden md:block ml-2 text-sm font-medium text-gray-700">
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                            <i class="fas fa-chevron-down ml-2 text-gray-400"></i>
                        </div>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5" id="profile-dropdown">
                        <div class="py-1" role="menu">
                            <a href="<?php echo SITE_URL; ?>/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                <i class="fas fa-user-circle mr-2"></i>
                                Profil
                            </a>
                            <a href="<?php echo SITE_URL; ?>/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                <i class="fas fa-cog mr-2"></i>
                                Paramètres
                            </a>
                            <div class="border-t border-gray-100"></div>
                            <a href="<?php echo SITE_URL; ?>/api/auth/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50" role="menuitem">
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                <?php echo $LANG['logout']; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Search (Visible on small screens) -->
<div class="lg:hidden p-4 bg-white border-t">
    <div class="relative">
        <input type="text" 
               class="search-bar" 
               placeholder="Rechercher..."
               id="mobile-search">
        <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
    </div>
</div>
