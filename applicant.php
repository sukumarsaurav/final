<?php
// Start output buffering to prevent 'headers already sent' errors
ob_start();

// Include session management
require_once "includes/session.php";

// Include config files
require_once "config/db_connect.php";

$page_title = "Visa Applicants";
require_once 'includes/header.php';
require_once 'includes/functions.php';
?>

<!-- Hero Section -->
<section class="hero applicant-hero">
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title">Your Immigration Journey Made Simple</h1>
            <p class="hero-subtitle">Connect with licensed immigration professionals, track your application progress, and get expert guidance throughout your visa process</p>
            <div class="hero-buttons">
                <a href="register.php?type=applicant" class="btn btn-primary">Join as Applicant</a>
                <a href="eligibility-test.php" class="btn btn-secondary">Check Eligibility</a>
            </div>
        </div>
    </div>
</section>

<div class="content">
    <!-- Platform Benefits Section -->
    <section class="section platform-benefits">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Why Choose Visafy as an Applicant</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Our platform is designed to simplify your immigration journey</p>
            
            <div class="benefits-container">
                <!-- Expert Access Section -->
                <div class="benefit-section" data-aos="fade-up">
                    <div class="benefit-content">
                        <div class="benefit-info">
                            <h3>Access to Verified Professionals</h3>
                            <p class="benefit-description">
                                Connect with licensed and verified immigration consultants specializing in your destination country.
                            </p>
                            <ul class="benefit-features">
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Verified Credentials</strong>
                                        <p>All consultants on our platform are verified for their credentials and licensing</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Transparent Reviews</strong>
                                        <p>Read genuine reviews from other applicants before choosing your consultant</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Specialized Expertise</strong>
                                        <p>Find consultants who specialize in your specific visa category or destination</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="benefit-image">
                            <div class="svg-background">
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-1">
                                    <path d="M42.7,-73.4C55.9,-67.1,67.7,-57.2,75.9,-44.6C84.1,-32,88.7,-16,88.1,-0.3C87.5,15.3,81.8,30.6,73.1,43.9C64.4,57.2,52.8,68.5,39.1,75.3C25.4,82.1,9.7,84.4,-5.9,83.1C-21.5,81.8,-37,76.9,-50.9,68.5C-64.8,60.1,-77.1,48.3,-83.3,33.8C-89.5,19.3,-89.6,2.2,-85.1,-13.2C-80.6,-28.6,-71.5,-42.3,-59.8,-51.6C-48.1,-60.9,-33.8,-65.8,-20.4,-70.3C-7,-74.8,5.5,-78.9,18.8,-79.1C32.1,-79.3,46.2,-75.6,42.7,-73.4Z" transform="translate(100 100)" />
                                </svg>
                            </div>
                            <div class="image-container">
                                <img src="assets/images/consultant-profile.jpg" alt="Expert Access">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Tracking Section -->
                <div class="benefit-section" data-aos="fade-up">
                    <div class="benefit-content reverse">
                        <div class="benefit-image">
                            <div class="svg-background">
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-3">
                                    <path d="M39.9,-68.1C52.6,-62.1,64.5,-53.1,72.7,-41C80.9,-28.8,85.4,-14.4,83.9,-0.9C82.3,12.7,74.8,25.4,66.4,37.8C58,50.3,48.7,62.5,36.5,70.1C24.2,77.7,9.1,80.7,-5.9,79.5C-20.9,78.3,-35.9,72.9,-47.5,64C-59.1,55,-67.3,42.5,-73.4,28.5C-79.5,14.5,-83.5,-1,-80.8,-15.2C-78.1,-29.4,-68.7,-42.3,-56.8,-48.9C-44.9,-55.5,-30.5,-55.8,-17.7,-61.8C-4.9,-67.8,6.3,-79.5,18.4,-80.5C30.5,-81.5,43.5,-71.8,39.9,-68.1Z" transform="translate(100 100)" />
                                </svg>
                            </div>
                            <div class="image-container">
                                <img src="assets/images/application-status.png" alt="Application Tracking">
                            </div>
                        </div>
                        <div class="benefit-info">
                            <h3>Real-Time Application Tracking</h3>
                            <p class="benefit-description">
                                Stay informed about your visa application status with our comprehensive tracking system.
                            </p>
                            <ul class="benefit-features">
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Progress Timeline</strong>
                                        <p>Visual timeline showing each stage of your application process</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Instant Updates</strong>
                                        <p>Receive notifications when your application status changes or requires action</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Milestone Tracking</strong>
                                        <p>Clear tracking of completed steps and upcoming requirements</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Document Management Section -->
                <div class="benefit-section" data-aos="fade-up">
                    <div class="benefit-content">
                        <div class="benefit-info">
                            <h3>Secure Document Management</h3>
                            <p class="benefit-description">
                                Manage all your important documents in one secure, centralized location.
                            </p>
                            <ul class="benefit-features">
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Cloud Storage</strong>
                                        <p>All your documents safely stored and accessible from anywhere</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Document Checklist</strong>
                                        <p>Customized checklists of required documents based on your visa type</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Secure Sharing</strong>
                                        <p>Share documents with your consultant securely with detailed access control</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="benefit-image">
                            <div class="svg-background">
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-5">
                                    <path d="M48.2,-76.1C63.3,-69.2,77.2,-58.4,84.6,-44.2C92,-30,92.8,-12.5,89.6,3.7C86.3,19.9,78.9,34.8,68.9,47.9C58.9,61,46.2,72.3,31.5,77.8C16.8,83.2,0.1,82.8,-16.4,79.7C-32.9,76.6,-49.2,70.8,-62.7,60.3C-76.2,49.8,-87,34.6,-90.9,17.8C-94.8,0.9,-91.9,-17.5,-84.2,-32.8C-76.5,-48.1,-64,-60.2,-49.5,-67.5C-35,-74.8,-18.5,-77.3,-1.2,-75.5C16.1,-73.7,33.1,-83,48.2,-76.1Z" transform="translate(100 100)" />
                                </svg>
                            </div>
                            <div class="image-container">
                                <img src="assets/images/documents.png" alt="Document Management">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Communication Section -->
                <div class="benefit-section" data-aos="fade-up">
                    <div class="benefit-content reverse">
                        <div class="benefit-image">
                            <div class="svg-background">
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-7">
                                    <path d="M39.9,-68.1C52.6,-62.1,64.5,-53.1,72.7,-41C80.9,-28.8,85.4,-14.4,83.9,-0.9C82.3,12.7,74.8,25.4,66.4,37.8C58,50.3,48.7,62.5,36.5,70.1C24.2,77.7,9.1,80.7,-5.9,79.5C-20.9,78.3,-35.9,72.9,-47.5,64C-59.1,55,-67.3,42.5,-73.4,28.5C-79.5,14.5,-83.5,-1,-80.8,-15.2C-78.1,-29.4,-68.7,-42.3,-56.8,-48.9C-44.9,-55.5,-30.5,-55.8,-17.7,-61.8C-4.9,-67.8,6.3,-79.5,18.4,-80.5C30.5,-81.5,43.5,-71.8,39.9,-68.1Z" transform="translate(100 100)" />
                                </svg>
                            </div>
                            <div class="image-container">
                                <img src="assets/images/messaging.png" alt="Direct Communication">
                            </div>
                        </div>
                        <div class="benefit-info">
                            <h3>Direct Communication Channels</h3>
                            <p class="benefit-description">
                                Stay connected with your immigration consultant through our integrated messaging system.
                            </p>
                            <ul class="benefit-features">
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Real-Time Messaging</strong>
                                        <p>Chat directly with your consultant about urgent questions and updates</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Message History</strong>
                                        <p>Full access to your complete conversation history for reference</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>File Sharing</strong>
                                        <p>Easily share documents and information within the messaging system</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- AI Assistant Section -->
    <section class="ai-assistant-section">
        <div class="container">
            <div class="ai-assistant-content">
                <div class="ai-assistant-info">
                    <h2>Get Help from Our AI Assistant</h2>
                    <p>Our AI-powered assistant can help with common questions and guide you through the application process.</p>
                    <ul class="ai-assistant-features">
                        <li><i class="fas fa-check-circle"></i> 24/7 availability for quick answers</li>
                        <li><i class="fas fa-check-circle"></i> Guidance on visa requirements and processes</li>
                        <li><i class="fas fa-check-circle"></i> Document checklists based on your visa type</li>
                        <li><i class="fas fa-check-circle"></i> Instant responses to common immigration questions</li>
                    </ul>
                    <a href="ai-assistant.php" class="btn btn-primary">Try AI Assistant</a>
                </div>
                <div class="ai-assistant-image">
                    <img src="assets/images/ai-chatbot.svg" alt="AI Assistant">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Feature Categories -->
    <section class="feature-categories">
        <div class="container">
            <h2 class="section-title">Key Features for Applicants</h2>
            <div class="categories-grid">
                <!-- Application Management -->
                <div class="category-card" data-category="application">
                    <div class="category-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Application Management</h3>
                    <ul class="feature-list">
                        <li>Real-time status tracking</li>
                        <li>Document checklists</li>
                        <li>Application history</li>
                        <li>Timeline visualization</li>
                    </ul>
                </div>

                <!-- Consultant Connection -->
                <div class="category-card" data-category="consultant">
                    <div class="category-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Consultant Connection</h3>
                    <ul class="feature-list">
                        <li>Browse verified consultants</li>
                        <li>View ratings and reviews</li>
                        <li>Direct messaging</li>
                        <li>Consultation scheduling</li>
                    </ul>
                </div>

                <!-- Resource Access -->
                <div class="category-card" data-category="resources">
                    <div class="category-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>Resource Center</h3>
                    <ul class="feature-list">
                        <li>Country-specific guides</li>
                        <li>Visa requirement updates</li>
                        <li>Process explainers</li>
                        <li>FAQ library</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Steps Section -->
<section class="section steps">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Start Your Visa Journey in 4 Simple Steps</h2>
        <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Our streamlined process gets you from application to approval with expert guidance every step of the way</p>

        <div class="steps-container">
            <!-- Step 1 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="200">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Create Your Profile</h3>
                    <p>Sign up and complete your applicant profile with personal details, education history, work experience, and your immigration goals. The more complete your profile, the better guidance we can provide.</p>
                    <a href="register.php?type=applicant" class="btn btn-outline">Sign Up Now</a>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="300">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Take Eligibility Assessment</h3>
                    <p>Complete our comprehensive eligibility assessment to determine which immigration programs you may qualify for. Receive a detailed report with your options and recommended next steps.</p>
                    <a href="eligibility-test.php" class="btn btn-outline">Check Eligibility</a>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="400">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Connect with a Consultant</h3>
                    <p>Browse our network of verified immigration consultants. Filter by specialization, language, location, and client ratings to find the perfect match for your needs. Book a consultation with your chosen expert.</p>
                    <a href="book-consultation.php" class="btn btn-outline">Find Consultants</a>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="500">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3>Start Your Application</h3>
                    <p>With your consultant's guidance, begin the application process. Upload required documents, complete forms, and track your progress through our intuitive dashboard. Receive updates at every stage of your journey.</p>
                    <a href="book-service.php" class="btn btn-outline">Get Started</a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
:root {
    --primary-color: #eaaa34;
    --primary-light: rgba(234, 170, 52, 0.1);
    --primary-medium: rgba(234, 170, 52, 0.2);
    --dark-blue: #042167;
    --text-color: #333;
    --text-light: #666;
    --background-light: #f8f9fa;
    --white: #fff;
    --border-color: #e5e7eb;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --border-radius: 0.5rem;
    --border-radius-lg: 1rem;
    --transition: all 0.3s ease;
    --secondary-color: #858796;
    --success-color: #1cc88a;
    --danger-color: #e74a3b;
    --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--dark-blue));
    --shadow-soft: 0 10px 30px rgba(0,0,0,0.1);
    --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hero Section Styles */
.hero {
    padding: 60px 0;
    background-color: var(--background-light);
    overflow: hidden;
    position: relative;
    min-height: 300px;
    display: flex;
    align-items: center;
}

.hero.applicant-hero {
    background-color: rgba(4, 33, 103, 0.05);
}

.hero-content {
    max-width: 800px;
    margin: 0 auto;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark-blue);
    margin-bottom: 20px;
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 1.2rem;
    color: var(--text-light);
    margin-bottom: 30px;
    line-height: 1.5;
}

.hero-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn {
    padding: 12px 25px;
    border-radius: var(--border-radius);
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    display: inline-block;
    cursor: pointer;
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--white);
    border: none;
}

.btn-primary:hover {
    background-color: var(--dark-blue);
    transform: translateY(-2px);
}

.btn-secondary {
    background-color: var(--white);
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background-color: var(--background-light);
    transform: translateY(-2px);
}

/* Content Container */
.content {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

/* Platform Benefits Styles */
.section {
    padding: 60px 0;
}

.section-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark-blue);
    text-align: center;
    margin-bottom: 15px;
}

.section-subtitle {
    font-size: 1.1rem;
    color: var(--text-light);
    text-align: center;
    margin-bottom: 50px;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

.platform-benefits {
    background-color: var(--white);
}

.benefits-container {
    max-width: 1100px;
    margin: 0 auto;
}

.benefit-section {
    margin-bottom: 70px;
}

.benefit-content {
    display: flex;
    align-items: center;
    gap: 40px;
}

.benefit-content.reverse {
    flex-direction: row-reverse;
}

.benefit-info {
    flex: 1;
}

.benefit-info h3 {
    color: var(--dark-blue);
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.benefit-description {
    color: var(--text-light);
    margin-bottom: 20px;
}

.benefit-features {
    list-style: none;
    padding: 0;
}

.benefit-features li {
    display: flex;
    margin-bottom: 20px;
}

.check-icon {
    color: var(--primary-color);
    background-color: var(--primary-light);
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
    margin-top: 4px;
}

.benefit-features li strong {
    display: block;
    margin-bottom: 5px;
    color: var(--text-color);
}

.benefit-features li p {
    color: var(--text-light);
    margin: 0;
    font-size: 0.95rem;
}

.benefit-image {
    flex: 1;
    position: relative;
}

.svg-background {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: -1;
}

.shape {
    position: absolute;
    width: 100%;
    height: 100%;
    fill: var(--primary-light);
    opacity: 0.3;
}

.shape-1, .shape-3, .shape-5 {
    top: -10%;
    left: -10%;
    animation: morph 8s ease-in-out infinite;
}

.shape-2, .shape-4, .shape-6 {
    top: -5%;
    left: -5%;
    animation: morph 8s ease-in-out infinite reverse;
}

@keyframes morph {
    0% {
        transform: scale(1) translate(0, 0);
    }
    50% {
        transform: scale(1.05) translate(1%, 1%);
    }
    100% {
        transform: scale(1) translate(0, 0);
    }
}

.image-container {
    padding: 10px;
}

.image-container img {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
    box-shadow: var(--shadow-md);
}

/* AI Assistant Section */
.ai-assistant-section {
    background-color: var(--primary-light);
    padding: 60px 0;
    margin: 40px 0;
    border-radius: var(--border-radius-lg);
}

.ai-assistant-content {
    display: flex;
    align-items: center;
    gap: 40px;
}

.ai-assistant-info {
    flex: 1;
}

.ai-assistant-info h2 {
    color: var(--dark-blue);
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.ai-assistant-info p {
    color: var(--text-light);
    margin-bottom: 20px;
}

.ai-assistant-features {
    list-style: none;
    padding: 0;
    margin-bottom: 30px;
}

.ai-assistant-features li {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    color: var(--text-color);
}

.ai-assistant-features li i {
    color: var(--primary-color);
    margin-right: 15px;
}

.ai-assistant-image {
    flex: 1;
    display: flex;
    justify-content: center;
}

.ai-assistant-image img {
    max-width: 80%;
    height: auto;
}

/* Feature Categories */
.feature-categories {
    padding: 60px 0;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.category-card {
    background: var(--white);
    border-radius: 20px;
    padding: 30px;
    box-shadow: var(--shadow-soft);
    transition: var(--transition-smooth);
    position: relative;
    overflow: hidden;
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: var(--gradient-primary);
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.category-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-light);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}

.category-icon i {
    font-size: 1.5rem;
    color: var(--primary-color);
}

.category-card h3 {
    margin-bottom: 15px;
    color: var(--dark-blue);
    font-weight: 600;
}

.feature-list {
    list-style: none;
    padding: 0;
}

.feature-list li {
    padding: 8px 0;
    display: flex;
    align-items: center;
    color: var(--text-light);
}

.feature-list li::before {
    content: '✓';
    color: var(--primary-color);
    margin-right: 10px;
    font-weight: bold;
}

/* Steps Section */
.steps {
    padding: 80px 0;
    position: relative;
    background-color: var(--background-light);
}

.steps-container {
    display: flex;
    flex-direction: column;
    gap: 40px;
    margin-top: 50px;
    position: relative;
}

.steps-container::before {
    content: '';
    position: absolute;
    left: 30px;
    top: 60px;
    bottom: 60px;
    width: 2px;
    background: var(--primary-color);
    z-index: 1;
}

.step-card {
    background: var(--white);
    padding: 40px;
    padding-left: 80px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    position: relative;
    margin-left: 30px;
}

.step-number {
    position: absolute;
    left: -30px;
    top: 50%;
    transform: translateY(-50%);
    width: 60px;
    height: 60px;
    background: var(--primary-color);
    color: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
    z-index: 2;
    border: 4px solid var(--white);
}

.step-content {
    position: relative;
}

.step-content h3 {
    margin-bottom: 15px;
    color: var(--primary-color);
    font-size: 1.5rem;
}

.step-content p {
    margin-bottom: 20px;
    color: var(--text-color);
    line-height: 1.6;
}

.btn-outline {
    display: inline-block;
    padding: 12px 25px;
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
    border-radius: 5px;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn-outline:hover {
    background: var(--primary-color);
    color: var(--white);
}

/* Responsive styles */
@media (max-width: 991px) {
    .benefit-content {
        flex-direction: column;
    }
    
    .benefit-content.reverse {
        flex-direction: column;
    }
    
    .ai-assistant-content {
        flex-direction: column;
    }
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .section-title {
        font-size: 1.7rem;
    }
    
    .steps-container::before {
        left: 20px;
    }
    
    .step-card {
        padding: 30px;
        padding-left: 70px;
        margin-left: 20px;
    }
    
    .step-number {
        left: -20px;
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
}
</style>

<?php
// End output buffering and send content to browser
ob_end_flush();
?>

<?php require_once 'includes/footer.php'; ?>
