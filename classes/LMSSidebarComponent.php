<?php
/**
 * Zwicky Technology License Management System
 * Sidebar Navigation Component
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 * 
 * Reusable Sidebar Component with Dynamic Menu Generation
 */

class LMSSidebarComponent {
    private $auth;
    private $current_page;
    private $menu_items;
    
    /**
     * Constructor
     * @param LMSAdminAuth $auth Authentication object
     */
    public function __construct($auth) {
        $this->auth = $auth;
        $this->current_page = basename($_SERVER['PHP_SELF']);
        $this->initializeMenuItems();
    }
    
    /**
     * Initialize menu items with permissions and icons
     */
    private function initializeMenuItems() {
        $this->menu_items = [
            // Main Navigation
            'main' => [
                [
                    'id' => 'dashboard',
                    'label' => 'Dashboard',
                    'url' => 'dashboard.php',
                    'icon' => 'fa-tachometer-alt',
                    'permission' => null, // Available to all
                    'badge' => null
                ],
                [
                    'id' => 'license-manager',
                    'label' => 'License Manager',
                    'url' => 'license-manager.php',
                    'icon' => 'fa-key',
                    'permission' => null,
                    'badge' => null
                ],
                [
                    'id' => 'licenses',
                    'label' => 'License List',
                    'url' => 'licenses.php',
                    'icon' => 'fa-list',
                    'permission' => null,
                    'badge' => null
                ],
                [
                    'id' => 'activations',
                    'label' => 'Activations',
                    'url' => 'activations.php',
                    'icon' => 'fa-globe',
                    'permission' => null,
                    'badge' => null
                ],
                [
                    'id' => 'customers',
                    'label' => 'Customers',
                    'url' => 'customers.php',
                    'icon' => 'fa-users',
                    'permission' => null,
                    'badge' => null
                ],
                [
                    'id' => 'logs',
                    'label' => 'Activity Logs',
                    'url' => 'logs.php',
                    'icon' => 'fa-history',
                    'permission' => null,
                    'badge' => null
                ],
                [
                    'id' => 'reports',
                    'label' => 'Reports',
                    'url' => 'reports.php',
                    'icon' => 'fa-chart-bar',
                    'permission' => null,
                    'badge' => null
                ]
            ],
            
            // Admin Only
            'admin' => [
                [
                    'id' => 'admin-users',
                    'label' => 'Admin Users',
                    'url' => 'admin-users.php',
                    'icon' => 'fa-user-shield',
                    'permission' => 'admin',
                    'badge' => null
                ],
                [
                    'id' => 'settings',
                    'label' => 'Settings',
                    'url' => 'settings.php',
                    'icon' => 'fa-cog',
                    'permission' => 'admin',
                    'badge' => null
                ]
            ],
            
            // Tools & Documentation
            'tools' => [
                [
                    'id' => 'api-docs',
                    'label' => 'API Documentation',
                    'url' => 'api-docs.php',
                    'icon' => 'fa-code',
                    'permission' => null,
                    'badge' => null
                ]
            ],
            
            // User Menu
            'user' => [
                [
                    'id' => 'profile',
                    'label' => 'My Profile',
                    'url' => 'profile.php',
                    'icon' => 'fa-user',
                    'permission' => null,
                    'badge' => null
                ],
                [
                    'id' => 'logout',
                    'label' => 'Logout',
                    'url' => 'logout.php',
                    'icon' => 'fa-sign-out-alt',
                    'permission' => null,
                    'badge' => null,
                    'confirm' => 'Are you sure you want to logout?'
                ]
            ]
        ];
    }
    
    /**
     * Check if menu item should be visible
     * @param array $item Menu item
     * @return bool
     */
    private function isVisible($item) {
        // If no permission required, show to all
        if ($item['permission'] === null) {
            return true;
        }
        
        // Check if user has required permission
        return $this->auth->hasPermission($item['permission']);
    }
    
    /**
     * Check if menu item is active
     * @param array $item Menu item
     * @return bool
     */
    private function isActive($item) {
        return $this->current_page === $item['url'];
    }
    
    /**
     * Render a single menu item
     * @param array $item Menu item
     * @return string HTML
     */
    private function renderMenuItem($item) {
        if (!$this->isVisible($item)) {
            return '';
        }
        
        $active_class = $this->isActive($item) ? 'active' : '';
        $confirm_attr = isset($item['confirm']) ? 'onclick="return confirm(\'' . htmlspecialchars($item['confirm'], ENT_QUOTES) . '\')"' : '';
        $badge_html = isset($item['badge']) ? '<span class="nav-badge">' . htmlspecialchars($item['badge']) . '</span>' : '';
        
        $html = '<div class="nav-item">';
        $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="nav-link ' . $active_class . '" ' . $confirm_attr . '>';
        $html .= '<i class="fas ' . htmlspecialchars($item['icon']) . '"></i>';
        $html .= htmlspecialchars($item['label']);
        $html .= $badge_html;
        $html .= '</a>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render menu section
     * @param string $section Section name
     * @param array $items Menu items
     * @param bool $separator Add separator before section
     * @return string HTML
     */
    private function renderSection($section, $items, $separator = false) {
        $html = '';
        
        // Add separator if needed
        if ($separator) {
            $html .= '<div class="nav-separator"></div>';
        }
        
        // Render each item in section
        foreach ($items as $item) {
            $html .= $this->renderMenuItem($item);
        }
        
        return $html;
    }
    
    /**
     * Render the complete sidebar
     * @return string HTML
     */
    public function render() {
        ob_start();
        ?>
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>
                    <i class="fas fa-shield-alt"></i>
                    Zwicky License Manager
                </h3>
                <div class="version">Version <?php echo LMS_VERSION; ?></div>
            </div>
            
            <div class="sidebar-nav">
                <?php
                // Render main menu
                echo $this->renderSection('main', $this->menu_items['main']);
                
                // Render admin menu
                $admin_visible = false;
                foreach ($this->menu_items['admin'] as $item) {
                    if ($this->isVisible($item)) {
                        $admin_visible = true;
                        break;
                    }
                }
                if ($admin_visible) {
                    echo $this->renderSection('admin', $this->menu_items['admin'], true);
                }
                
                // Render tools menu
                echo $this->renderSection('tools', $this->menu_items['tools'], false);
                
                // Render user menu
                echo $this->renderSection('user', $this->menu_items['user'], true);
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add custom menu item
     * @param string $section Section name
     * @param array $item Menu item configuration
     */
    public function addMenuItem($section, $item) {
        if (!isset($this->menu_items[$section])) {
            $this->menu_items[$section] = [];
        }
        
        // Set defaults
        $item = array_merge([
            'id' => uniqid(),
            'label' => 'Menu Item',
            'url' => '#',
            'icon' => 'fa-circle',
            'permission' => null,
            'badge' => null
        ], $item);
        
        $this->menu_items[$section][] = $item;
    }
    
    /**
     * Remove menu item by ID
     * @param string $id Menu item ID
     * @return bool Success
     */
    public function removeMenuItem($id) {
        foreach ($this->menu_items as $section => &$items) {
            foreach ($items as $index => $item) {
                if ($item['id'] === $id) {
                    unset($items[$index]);
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Update menu item badge
     * @param string $id Menu item ID
     * @param string|null $badge Badge text
     * @return bool Success
     */
    public function updateBadge($id, $badge) {
        foreach ($this->menu_items as $section => &$items) {
            foreach ($items as &$item) {
                if ($item['id'] === $id) {
                    $item['badge'] = $badge;
                    return true;
                }
            }
        }
        return false;
    }
}

/**
 * Helper function to render sidebar
 * Usage: renderSidebar($auth);
 */
function renderSidebar($auth) {
    $sidebar = new LMSSidebarComponent($auth);
    echo $sidebar->render();
}
