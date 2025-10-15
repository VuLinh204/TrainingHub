/**
 * Tabs Module
 * Handles tab switching functionality
 */

const TabsModule = (function() {
    'use strict';

    /**
     * Initialize tabs
     */
    function init() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        if (tabButtons.length === 0) return;

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                if (!targetTab) return;

                // Remove active class from all buttons and panes
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));

                // Add active class to clicked button
                this.classList.add('active');

                // Show corresponding tab pane
                const targetPane = document.getElementById(targetTab);
                if (targetPane) {
                    targetPane.classList.add('active');
                }

                // Store active tab in session storage for persistence
                try {
                    const tabGroup = this.closest('.content-tabs, .history-tabs')?.id || 'default';
                    sessionStorage.setItem(`activeTab_${tabGroup}`, targetTab);
                } catch (e) {
                    // Session storage not available
                }
            });
        });

        // Restore previously active tab
        restoreActiveTab();
    }

    /**
     * Restore previously active tab from session storage
     */
    function restoreActiveTab() {
        try {
            const tabGroups = document.querySelectorAll('.content-tabs, .history-tabs');
            
            tabGroups.forEach(group => {
                const groupId = group.id || 'default';
                const savedTab = sessionStorage.getItem(`activeTab_${groupId}`);
                
                if (savedTab) {
                    const button = group.querySelector(`[data-tab="${savedTab}"]`);
                    if (button) {
                        button.click();
                    }
                }
            });
        } catch (e) {
            // Session storage not available
        }
    }

    /**
     * Programmatically switch to a tab
     */
    function switchTab(tabId) {
        const button = document.querySelector(`[data-tab="${tabId}"]`);
        if (button) {
            button.click();
        }
    }

    // Public API
    return {
        init: init,
        switchTab: switchTab
    };
})();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', TabsModule.init);
} else {
    TabsModule.init();
}