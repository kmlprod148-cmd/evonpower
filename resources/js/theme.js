/**
 * Theme Manager - Dark/Light Mode Toggle
 * Handles theme persistence, system preference detection, and smooth transitions
 */

class ThemeManager {
    constructor() {
        this.theme = null;
        this.listeners = [];
        this.init();
    }

    init() {
        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', () => {
            this.applyTheme();
        });

        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                const userPreference = this.getStoredTheme();
                if (userPreference === 'system') {
                    this.setTheme('system', true);
                }
            });
        }
    }

    /**
     * Get theme from localStorage
     */
    getStoredTheme() {
        return localStorage.getItem('theme');
    }

    /**
     * Store theme in localStorage
     */
    storeTheme(theme) {
        localStorage.setItem('theme', theme);
    }

    /**
     * Get effective theme (resolves 'system' to actual theme)
     */
    getEffectiveTheme(userTheme = null) {
        const storedTheme = userTheme || this.getStoredTheme() || 'system';
        
        if (storedTheme === 'system') {
            return this.getSystemTheme();
        }
        
        return storedTheme;
    }

    /**
     * Get system theme preference
     */
    getSystemTheme() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    /**
     * Set theme and apply it
     */
    setTheme(theme, isSystemUpdate = false) {
        this.theme = theme;
        
        if (!isSystemUpdate) {
            this.storeTheme(theme);
        }
        
        this.applyTheme();
        this.notifyListeners(theme);
    }

    /**
     * Apply theme to document
     */
    applyTheme() {
        const effectiveTheme = this.getEffectiveTheme();
        
        // Add or remove dark class from html element
        if (effectiveTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Store effective theme for quick access
        this.theme = effectiveTheme;
    }

    /**
     * Toggle between light and dark
     */
    toggle() {
        const currentTheme = this.getEffectiveTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
        return newTheme;
    }

    /**
     * Add theme change listener
     */
    addListener(callback) {
        this.listeners.push(callback);
    }

    /**
     * Remove theme change listener
     */
    removeListener(callback) {
        this.listeners = this.listeners.filter(l => l !== callback);
    }

    /**
     * Notify all listeners of theme change
     */
    notifyListeners(theme) {
        this.listeners.forEach(callback => callback(theme));
    }
}

// Create global instance
window.themeManager = new ThemeManager();

// Export for use in modules
export default window.themeManager;
