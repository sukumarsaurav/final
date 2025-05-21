<?php
// Start output buffering to prevent 'headers already sent' errors
ob_start();

// Include session management
require_once "includes/session.php";

// Include config files
require_once "config/db_connect.php";

$page_title = "Frequently Asked Questions";
require_once 'includes/header.php';
require_once 'includes/functions.php';
?>

<!-- Hero Section -->
<section class="hero privacy-hero">
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title">Frequently Asked Questions</h1>
            <p class="hero-subtitle">Find answers to common questions about our services</p>
        </div>
    </div>
</section>

<div class="content">
    <div class="container">
        <div class="privacy-content">
            <!-- FAQ Categories -->
            <div class="faq-categories">
                <button class="category-btn active" data-category="general">General</button>
                <button class="category-btn" data-category="visa">Visa Services</button>
                <button class="category-btn" data-category="consultation">Consultations</button>
                <button class="category-btn" data-category="payment">Payment & Fees</button>
            </div>

            <!-- General FAQs -->
            <div class="faq-section active" id="general">
                <div class="accordion">
                    <div class="accordion-item">
                        <button class="accordion-header">
                            What is Visafy?
                            <span class="icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Visafy is a platform that connects visa applicants with professional immigration consultants. We provide a secure and efficient way to get expert guidance for your visa application process.</p>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <button class="accordion-header">
                            How do I get started with Visafy?
                            <span class="icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Getting started is easy:</p>
                            <ol>
                                <li>Create a free account</li>
                                <li>Browse our verified consultants</li>
                                <li>Book a consultation</li>
                                <li>Get expert guidance for your visa process</li>
                            </ol>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <button class="accordion-header">
                            Are your consultants verified?
                            <span class="icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Yes, all consultants on our platform go through a rigorous verification process. We check their credentials, licenses, and professional experience to ensure they meet our quality standards.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visa Services FAQs -->
            <div class="faq-section" id="visa">
                <div class="accordion">
                    <div class="accordion-item">
                        <button class="accordion-header">
                            What types of visas do you handle?
                            <span class="icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Our consultants handle various visa types including:</p>
                            <ul>
                                <li>Student Visas</li>
                                <li>Work Visas</li>
                                <li>Tourist Visas</li>
                                <li>Business Visas</li>
                                <li>Permanent Residency</li>
                                <li>Family Sponsorship</li>
                            </ul>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <button class="accordion-header">
                            How long does the visa process take?
                            <span class="icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Processing times vary depending on the type of visa and destination country. Your consultant will provide specific timelines during your consultation.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Consultation FAQs -->
            <div class="faq-section" id="consultation">
                <div class="accordion">
                    <div class="accordion-item">
                        <button class="accordion-header">
                            How do consultations work?
                            <span class="icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Consultations can be conducted online or in-person, depending on your preference and the consultant's availability. During the consultation, you'll discuss your visa requirements, eligibility, and get expert guidance on the application process.</p>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <button class="accordion-header">
                            Can I change my consultant?
                            <span class="icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Yes, you can change your consultant if you're not satisfied with the service. Contact our support team, and we'll help you find a better match for your needs.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment FAQs -->
            <div class="faq-section" id="payment">
                <div class="accordion">
                    <div class="accordion-item">
                        <button class="accordion-header">
                            What are your fees?
                            <span class="icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Consultation fees vary by consultant and service type. Each consultant sets their own rates, which are clearly displayed on their profile. Additional services and fees will be discussed during your consultation.</p>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <button class="accordion-header">
                            What payment methods do you accept?
                            <span class="icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>We accept various payment methods including:</p>
                            <ul>
                                <li>Credit/Debit Cards</li>
                                <li>Bank Transfers</li>
                                <li>Digital Wallets</li>
                            </ul>
                            <p>All payments are processed securely through our platform.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #eaaa34;
    --primary-light: rgba(234, 170, 52, 0.1);
    --dark-blue: #042167;
    --text-color: #333;
    --text-light: #666;
    --background-light: #f8f9fa;
    --white: #fff;
    --border-color: #e5e7eb;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --border-radius: 0.5rem;
}

/* Inherit existing styles */
.privacy-hero {
    background-color: rgba(234, 170, 52, 0.05);
    padding: 60px 0;
    text-align: center;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark-blue);
    margin-bottom: 15px;
}

.hero-subtitle {
    font-size: 1.2rem;
    color: var(--text-light);
}

.content {
    padding: 50px 0;
}

.privacy-content {
    max-width: 900px;
    margin: 0 auto;
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: 40px;
}

/* FAQ specific styles */
.faq-categories {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.category-btn {
    padding: 10px 20px;
    border: 2px solid var(--primary-color);
    border-radius: 25px;
    background: none;
    color: var(--primary-color);
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
}

.category-btn.active {
    background-color: var(--primary-color);
    color: white;
}

.faq-section {
    display: none;
}

.faq-section.active {
    display: block;
}

.accordion {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.accordion-item {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.accordion-header {
    width: 100%;
    padding: 20px;
    background-color: var(--white);
    border: none;
    text-align: left;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    color: var(--dark-blue);
    transition: all 0.3s ease;
}

.accordion-header:hover {
    background-color: var(--primary-light);
}

.accordion-header .icon {
    font-size: 20px;
    transition: transform 0.3s ease;
}

.accordion-header.active .icon {
    transform: rotate(45deg);
}

.accordion-content {
    padding: 0;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
    background-color: var(--background-light);
}

.accordion-content.active {
    padding: 20px;
    max-height: 1000px;
}

.accordion-content p {
    margin-bottom: 15px;
    line-height: 1.6;
    color: var(--text-color);
}

.accordion-content ul,
.accordion-content ol {
    margin-bottom: 15px;
    padding-left: 20px;
}

.accordion-content li {
    margin-bottom: 8px;
    line-height: 1.6;
    color: var(--text-color);
}

@media (max-width: 768px) {
    .privacy-content {
        padding: 20px;
    }
    
    .faq-categories {
        flex-direction: column;
    }
    
    .category-btn {
        width: 100%;
    }
    
    .accordion-header {
        padding: 15px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Category switching
    const categoryBtns = document.querySelectorAll('.category-btn');
    const faqSections = document.querySelectorAll('.faq-section');
    
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all buttons and sections
            categoryBtns.forEach(b => b.classList.remove('active'));
            faqSections.forEach(s => s.classList.remove('active'));
            
            // Add active class to clicked button and corresponding section
            btn.classList.add('active');
            document.getElementById(btn.dataset.category).classList.add('active');
        });
    });
    
    // Accordion functionality
    const accordionHeaders = document.querySelectorAll('.accordion-header');
    
    accordionHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const content = header.nextElementSibling;
            const isActive = header.classList.contains('active');
            
            // Close all accordions
            accordionHeaders.forEach(h => {
                h.classList.remove('active');
                h.nextElementSibling.classList.remove('active');
                h.nextElementSibling.style.maxHeight = '0';
            });
            
            // If the clicked accordion wasn't active, open it
            if (!isActive) {
                header.classList.add('active');
                content.classList.add('active');
                content.style.maxHeight = content.scrollHeight + 'px';
            }
        });
    });
});
</script>

<?php
// End output buffering and send content to browser
ob_end_flush();
?>

<?php require_once 'includes/footer.php'; ?>
