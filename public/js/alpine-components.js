/**
 * Alpine.js Component Functions for EVON
 */

window.languageSwitchFixed = function() {
    return {
        isOpen: false,
        switching: false,
        currentLocale: document.documentElement.lang || 'fr',
        currentFlag: '🌐',
        
        languages: {
            'fr': 'Français', 
            'en': 'English', 
            'ar': 'العربية', 
            'es': 'Español'
        },
        flags: {
            'fr': '🇫🇷', 
            'en': '🇬🇧', 
            'ar': '🇲🇦', 
            'es': '🇪🇸'
        },

        init() {
            this.currentFlag = this.flags[this.currentLocale.substring(0, 2)] || '🌐';
        },

        toggle() {
            if (!this.switching) {
                this.isOpen = !this.isOpen;
            }
        },

        async switchLanguage(locale) {
            if (this.switching || locale === this.currentLocale) return;

            this.switching = true;
            this.isOpen = false;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (!csrfToken) throw new Error('Token CSRF manquant');

                const response = await fetch('/language/set', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ locale: locale })
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                window.location.reload();
            } catch (error) {
                console.error('[Language Switch] ❌ Error:', error);
                this.switching = false;
            }
        }
    }
};

window.themeSwitcher = function() {
    return {
        theme: localStorage.getItem('theme') || 'auto',
        isOpen: false,
        systemPrefersDark: window.matchMedia('(prefers-color-scheme: dark)').matches,

        init() {
            this.applyTheme();
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                this.systemPrefersDark = e.matches;
                if (this.theme === 'auto') this.applyTheme();
            });
        },

        setTheme(newTheme) {
            this.theme = newTheme;
            localStorage.setItem('theme', newTheme);
            this.applyTheme();
            this.isOpen = false;
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: newTheme } }));
        },

        cycleTheme() {
            const themes = ['light', 'dark', 'auto'];
            const nextIndex = (themes.indexOf(this.theme) + 1) % themes.length;
            this.setTheme(themes[nextIndex]);
        },

        applyTheme() {
            const shouldBeDark = this.theme === 'dark' || (this.theme === 'auto' && this.systemPrefersDark);
            document.documentElement.classList.toggle('dark', shouldBeDark);
            const meta = document.querySelector('meta[name="theme-color"]');
            if (meta) meta.setAttribute('content', shouldBeDark ? '#1f2937' : '#ffffff');
        },

        themeTitle() {
            const titles = { 'light': 'Mode Clair', 'dark': 'Mode Sombre', 'auto': 'Automatique' };
            return titles[this.theme] || 'Changer le thème';
        }
    }
};

window.userMenu = function() {
    return {
        isOpen: false,
        isClosing: false,
        
        init() {
            // Close on escape key
            this.$watch('isOpen', value => {
                if (value) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });
            
            // Close on clicking outside (handled by @click.away)
            // Add keyboard navigation
            this.$el.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        },
        
        toggle() {
            if (this.isClosing) return;
            
            this.isOpen = !this.isOpen;
            
            // Announce to screen readers
            const button = this.$el.querySelector('#userMenuButton');
            if (button) {
                button.setAttribute('aria-expanded', this.isOpen);
            }
            
            // Focus trap when open
            if (this.isOpen) {
                this.$nextTick(() => {
                    const menu = this.$el.querySelector('#userDropdownMenu');
                    if (menu) {
                        const firstFocusable = menu.querySelector('a, button');
                        if (firstFocusable) {
                            firstFocusable.focus();
                        }
                    }
                });
            }
        },
        
        close() {
            if (!this.isOpen || this.isClosing) return;
            
            this.isClosing = true;
            this.isOpen = false;
            
            // Reset closing flag after animation
            setTimeout(() => {
                this.isClosing = false;
            }, 200);
            
            // Return focus to trigger button
            const button = this.$el.querySelector('#userMenuButton');
            if (button) {
                button.focus();
                button.setAttribute('aria-expanded', 'false');
            }
        }
    }
};

window.appState = function() {
    return {
        sidebarOpen: false,
        searchOpen: false,
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        
        init() {
            // Initialize theme
            const theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
            
            // Apply sidebar collapsed state on init
            if (this.sidebarCollapsed) {
                document.body.classList.add('sidebar-collapsed');
            }
            
            // Watch sidebar state
            this.$watch('sidebarOpen', value => {
                if (value) {
                    document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
                } else {
                    document.body.classList.remove('overflow-hidden');
                }
            });
            
            // Watch sidebar collapsed state
            this.$watch('sidebarCollapsed', value => {
                if (value) {
                    document.body.classList.add('sidebar-collapsed');
                } else {
                    document.body.classList.remove('sidebar-collapsed');
                }
                localStorage.setItem('sidebarCollapsed', value);
            });
            
            // Keyboard shortcut: Ctrl/Cmd + B to toggle sidebar (desktop only)
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'b' && window.innerWidth >= 1024) {
                    e.preventDefault();
                    this.toggleDesktopSidebar();
                }
            });
        },
        
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },
        
        toggleDesktopSidebar() {
            const sidebar = document.querySelector('.evon-sidebar');
            if (sidebar) {
                sidebar.classList.add('toggling');
                setTimeout(() => {
                    sidebar.classList.remove('toggling');
                }, 300);
            }
            this.sidebarCollapsed = !this.sidebarCollapsed;
            window.dispatchEvent(new CustomEvent('sidebar-toggled', {
                detail: { collapsed: this.sidebarCollapsed }
            }));
        }
    }
};
