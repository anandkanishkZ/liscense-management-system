<?php
/**
 * Sidebar Navigation Include File
 * Uses the reusable LMSSidebarComponent class
 */

// Load the sidebar component if not already loaded
if (!class_exists('LMSSidebarComponent')) {
    require_once dirname(dirname(__DIR__)) . '/classes/LMSSidebarComponent.php';
}

// Render the sidebar using the reusable component
renderSidebar($auth);
?>