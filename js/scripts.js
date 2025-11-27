/**
 * SaS Website - Modern JavaScript
 * Modernized and responsive version
 */

// ===== GLOBAL VARIABLES =====
let redeData = [];
let dataTable = null;

// ===== DOM CONTENT LOADED =====
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// ===== INITIALIZE APPLICATION =====
function initializeApp() {
    initializeNavigation();
    initializeScrollEffects();
    initializeForms();
    initializeDataTable();
    initializeModals();
    initializeAnimations();
    loadRedeConveniadaData();
}

// ===== NAVIGATION =====
function initializeNavigation() {
    const navbar = document.getElementById('mainNavbar');
    const navLinks = document.querySelectorAll('.nav-link[data-section]');
    
    // Smooth scrolling for navigation links
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-section');
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                // Update active nav link
                navLinks.forEach(nl => nl.classList.remove('active'));
                this.classList.add('active');
                
                // Smooth scroll to section
                const offsetTop = targetSection.offsetTop - navbar.offsetHeight - 20;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
                
                // Close mobile menu if open
                const navbarCollapse = document.getElementById('navbarNav');
                if (navbarCollapse.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                    bsCollapse.hide();
                }
            }
        });
    });
    
    // Update active nav link on scroll
    window.addEventListener('scroll', updateActiveNavLink);
}

// ===== SCROLL EFFECTS =====
function initializeScrollEffects() {
    const navbar = document.getElementById('mainNavbar');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
}

// ===== UPDATE ACTIVE NAV LINK =====
function updateActiveNavLink() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link[data-section]');
    const navbar = document.getElementById('mainNavbar');
    const scrollPos = window.scrollY + navbar.offsetHeight + 50;
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.offsetHeight;
        const sectionId = section.getAttribute('id');
        
        if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('data-section') === sectionId) {
                    link.classList.add('active');
                }
            });
        }
    });
}

// ===== FORMS =====
function initializeForms() {
    // Contact form
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactForm);
    }
    
    // Login forms
    const loginConvenioForm = document.getElementById('loginConvenioForm');
    const loginAssociadoForm = document.getElementById('loginAssociadoForm');
    const loginAdminForm = document.getElementById('loginAdminForm');
    
    if (loginConvenioForm) {
        loginConvenioForm.addEventListener('submit', handleLoginConvenio);
    }
    
    if (loginAssociadoForm) {
        loginAssociadoForm.addEventListener('submit', handleLoginAssociado);
    }
    
    if (loginAdminForm) {
        loginAdminForm.addEventListener('submit', handleLoginAdmin);
    }
}

// ===== FORM HANDLERS =====
function handleContactForm(e) {
    e.preventDefault();
    showLoading();
    
    // Simulate form submission
    setTimeout(() => {
        hideLoading();
        showAlert('Mensagem enviada com sucesso! Entraremos em contato em breve.', 'success');
        e.target.reset();
    }, 2000);
}

function handleLoginConvenio(e) {
    e.preventDefault();
    
    // Redirecionar diretamente para a URL externa em nova guia
    window.open('https://sasapp.tec.br/estab', '_blank');
    
    // Fechar o modal
    bootstrap.Modal.getInstance(document.getElementById('loginConvenioModal')).hide();
}

function handleLoginAssociado(e) {
    e.preventDefault();
    showLoading();
    
    const userData = {
        cartao: document.getElementById('cartaoAssociado').value,
        senha: document.getElementById('senhaAssociado').value
    };
    
    // Simulate login process
    setTimeout(() => {
        hideLoading();
        // Here you would typically make an API call
        showAlert('Login realizado com sucesso!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('loginAssociadoModal')).hide();
    }, 1500);
}

function handleLoginAdmin(e) {
    e.preventDefault();
    showLoading();
    
    const userData = {
        user: document.getElementById('userAdmin').value,
        password: document.getElementById('passwordAdmin').value
    };
    
    // Simulate login process
    setTimeout(() => {
        hideLoading();
        // Here you would typically make an API call
        showAlert('Acesso administrativo autorizado!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('loginAdminModal')).hide();
    }, 1500);
}

// ===== DATA TABLE =====
function initializeDataTable() {
    // Initialize DataTable when the rede conveniada section is visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !dataTable) {
                setupDataTable();
            }
        });
    });
    
    const redeSection = document.getElementById('rede-conveniada');
    if (redeSection) {
        observer.observe(redeSection);
    }
}

function setupDataTable() {
    const table = document.getElementById('redeTable');
    if (table && typeof $.fn.DataTable !== 'undefined') {
        dataTable = $(table).DataTable({
            responsive: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
            },
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            order: [[0, 'asc']],
            columnDefs: [
                {
                    targets: -1,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary" onclick="viewProfessional('${row[0]}')" title="Ver detalhes">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-outline-success" onclick="contactProfessional('${row[3]}')" title="Entrar em contato">
                                    <i class="bi bi-telephone"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            drawCallback: function() {
                updateTableInfo();
            }
        });
        
        // Custom search
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                dataTable.search(this.value).draw();
            });
        }
    }
}

// ===== LOAD REDE CONVENIADA DATA =====
function loadRedeConveniadaData() {
    // Simulate loading data from API
    showLoading();
    
    setTimeout(() => {
        // Sample data - replace with actual API call
        redeData = generateSampleData();
        populateDataTable();
        hideLoading();
    }, 1000);
}

function generateSampleData() {
    const profissionais = [
        'Dr. João Silva', 'Dra. Maria Santos', 'Dr. Pedro Oliveira', 'Dra. Ana Costa',
        'Dr. Carlos Ferreira', 'Dra. Lucia Almeida', 'Dr. Roberto Lima', 'Dra. Patricia Rocha'
    ];
    
    const especialidades = [
        'Cardiologia', 'Dermatologia', 'Neurologia', 'Pediatria',
        'Ortopedia', 'Ginecologia', 'Oftalmologia', 'Psiquiatria'
    ];
    
    const enderecos = [
        'Rua das Flores, 123 - Centro',
        'Av. Paulista, 456 - Bela Vista',
        'Rua Augusta, 789 - Consolação',
        'Av. Faria Lima, 321 - Itaim Bibi'
    ];
    
    const telefones = [
        '(11) 3333-1111', '(11) 3333-2222', '(11) 3333-3333', '(11) 3333-4444'
    ];
    
    const data = [];
    for (let i = 0; i < 50; i++) {
        data.push([
            profissionais[Math.floor(Math.random() * profissionais.length)],
            especialidades[Math.floor(Math.random() * especialidades.length)],
            enderecos[Math.floor(Math.random() * enderecos.length)],
            telefones[Math.floor(Math.random() * telefones.length)]
        ]);
    }
    
    return data;
}

function populateDataTable() {
    if (dataTable) {
        dataTable.clear();
        dataTable.rows.add(redeData);
        dataTable.draw();
    }
}

function updateTableInfo() {
    const tableInfo = document.getElementById('tableInfo');
    if (tableInfo && dataTable) {
        const info = dataTable.page.info();
        tableInfo.textContent = `Mostrando ${info.start + 1} a ${info.end} de ${info.recordsTotal} registros`;
    }
}

// ===== PROFESSIONAL ACTIONS =====
function viewProfessional(name) {
    showAlert(`Visualizando detalhes de: ${name}`, 'info');
}

function contactProfessional(phone) {
    if (confirm(`Deseja ligar para ${phone}?`)) {
        window.location.href = `tel:${phone.replace(/\D/g, '')}`;
    }
}

// ===== MODALS =====
function initializeModals() {
    // Reset forms when modals are hidden
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            const forms = this.querySelectorAll('form');
            forms.forEach(form => form.reset());
        });
    });
}

// ===== ANIMATIONS =====
function initializeAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    const animateElements = document.querySelectorAll('.card, .contact-item, .feature-item');
    animateElements.forEach(el => observer.observe(el));
}

// ===== UTILITY FUNCTIONS =====
function showLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.add('show');
    }
}

function hideLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.remove('show');
    }
}

function showAlert(message, type = 'info') {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// ===== ERROR HANDLING =====
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    hideLoading();
});

// ===== PERFORMANCE OPTIMIZATION =====
// Debounce function for scroll events
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Apply debounce to scroll events
const debouncedScrollHandler = debounce(updateActiveNavLink, 100);
window.removeEventListener('scroll', updateActiveNavLink);
window.addEventListener('scroll', debouncedScrollHandler);

// ===== ACCESSIBILITY IMPROVEMENTS =====
// Keyboard navigation for modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        });
    }
});

// Focus management for better accessibility
document.addEventListener('shown.bs.modal', function(e) {
    const firstInput = e.target.querySelector('input, textarea, select');
    if (firstInput) {
        firstInput.focus();
    }
});

// ===== RESPONSIVE UTILITIES =====
function isMobile() {
    return window.innerWidth <= 768;
}

function isTablet() {
    return window.innerWidth > 768 && window.innerWidth <= 1024;
}

function isDesktop() {
    return window.innerWidth > 1024;
}

// ===== THEME UTILITIES (Optional) =====
function toggleTheme() {
    const body = document.body;
    const isDark = body.classList.contains('dark-theme');
    
    if (isDark) {
        body.classList.remove('dark-theme');
        localStorage.setItem('theme', 'light');
    } else {
        body.classList.add('dark-theme');
        localStorage.setItem('theme', 'dark');
    }
}

function initializeTheme() {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.body.classList.add('dark-theme');
    }
}

// ===== GOOGLE ANALYTICS (if needed) =====
function trackEvent(action, category, label) {
    if (typeof gtag !== 'undefined') {
        gtag('event', action, {
            event_category: category,
            event_label: label
        });
    }
}

// ===== SERVICE WORKER (for PWA capabilities) =====
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('ServiceWorker registration successful');
            })
            .catch(function(err) {
                console.log('ServiceWorker registration failed');
            });
    });
}

// ===== EXPORT FOR TESTING =====
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeApp,
        showAlert,
        debounce,
        isMobile,
        isTablet,
        isDesktop
    };
}

