// Help Widget JavaScript
class HelpWidget {
    constructor() {
        this.widget = null;
        this.dropdown = null;
        this.isOpen = false;
        this.init();
    }
    
    init() {
        this.createWidget();
        this.attachEvents();
    }
    
    createWidget() {
        // Create widget container
        this.widget = document.createElement('div');
        this.widget.className = 'help-widget';
        
        // Create help button with dropdown
        this.widget.innerHTML = `
            <div class="quick-help-dropdown" id="quickHelpDropdown">
                <h4><i class="fas fa-question-circle"></i> Quick Help</h4>
                <ul class="quick-help-links">
                    <li><a href="../help.php"><i class="fas fa-book"></i> Help Center</a></li>
                    <li><a href="../help.php#faq"><i class="fas fa-question"></i> FAQs</a></li>
                    <li><a href="../help.php#guides"><i class="fas fa-guide"></i> User Guides</a></li>
                    <li><a href="../help.php#support"><i class="fas fa-ticket-alt"></i> Support Tickets</a></li>
                    <li><a href="tel:emergency" onclick="this.href='tel:' + getEmergencyNumber()"><i class="fas fa-phone"></i> Emergency Contact</a></li>
                </ul>
            </div>
            <button class="help-button" id="helpButton" onclick="helpWidget.toggle()">
                <i class="fas fa-question"></i>
                <span class="help-tooltip">Need Help?</span>
            </button>
        `;
        
        // Add to page
        document.body.appendChild(this.widget);
        this.dropdown = document.getElementById('quickHelpDropdown');
    }
    
    attachEvents() {
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.widget.contains(e.target) && this.isOpen) {
                this.close();
            }
        });
        
        // Handle keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + H to open help
            if ((e.ctrlKey || e.metaKey) && e.key === 'h') {
                e.preventDefault();
                window.location.href = '../help.php';
            }
            
            // Escape to close dropdown
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        this.dropdown.classList.add('active');
        this.isOpen = true;
    }
    
    close() {
        this.dropdown.classList.remove('active');
        this.isOpen = false;
    }
    
    // Get emergency number (placeholder - would be loaded from system settings)
    getEmergencyNumber() {
        return '911'; // Default emergency number
    }
}

// Initialize help widget when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on a dashboard page
    if (document.querySelector('.dashboard') || document.querySelector('.main-content')) {
        window.helpWidget = new HelpWidget();
    }
});

// Add some helpful utility functions
function openHelpCenter() {
    window.open('../help.php', '_blank');
}

function createSupportTicket() {
    window.location.href = '../help.php#support';
}

function searchHelp(query) {
    window.location.href = `../help.php?search=${encodeURIComponent(query)}`;
}

// Add contextual help based on current page
function addContextualHelp() {
    const currentPage = window.location.pathname.split('/').pop().replace('.php', '');
    const contextualHints = {
        'dashboard': 'Welcome to your dashboard! Use the navigation menu to access different features.',
        'visitors': 'Here you can register new visitors and view your visitor history.',
        'profile': 'Update your personal information and account settings here.',
        'users': 'Manage user accounts, approve new registrations, and edit user details.',
        'reports': 'Generate and view various system reports and analytics.',
        'check_in': 'Use this page to check in visitors at the gate.',
        'check_out': 'Record visitor departures and update exit times.',
        'all_visitors': 'View and manage all visitor records in the system.'
    };
    
    if (contextualHints[currentPage]) {
        // You could show this as a tooltip or notification
        console.log('Help hint:', contextualHints[currentPage]);
    }
}

// Call contextual help on page load
document.addEventListener('DOMContentLoaded', addContextualHelp);

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HelpWidget;
}