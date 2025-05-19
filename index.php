<?php
// Include session management
require_once "includes/session.php";

$page_title = "Visafy | Canadian Immigration Consultancy";
include('includes/header.php');
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title">Immigration Simplified For
                <div class="animated-text-wrapper"></div>
            </h1>
            <p class="hero-subtitle">Your trusted partner for immigration services</p>
            <div class="hero-buttons">
                <a href="eligibility-test.php" class="btn btn-primary">Check Eligibility</a>
                <a href="book-service.php" class="btn btn-secondary">Get Consultation</a>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="section services">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Our Platform</h2>
        <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">A comprehensive immigration platform
            connecting applicants with trusted consultants</p>

        <div class="services-grid">
            <!-- For Applicants -->
            <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                <div class="service-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <h3>For Applicants</h3>
                <div class="service-features">
                    <div class="feature-item">
                        <i class="fas fa-search"></i>
                        <span>Find & Compare Consultants</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-star"></i>
                        <span>Read Reviews & Ratings</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-calendar-check"></i>
                        <span>Book Consultations</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-tasks"></i>
                        <span>Track Application Progress</span>
                    </div>
                </div>
                <a href="register.php?type=applicant" class="btn btn-primary">Get Started</a>
            </div>

            <!-- For Consultants -->
            <div class="service-card" data-aos="fade-up" data-aos-delay="300">
                <div class="service-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h3>For Consultants</h3>
                <div class="service-features">
                    <div class="feature-item">
                        <i class="fas fa-users"></i>
                        <span>Manage Team & Tasks</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Handle Bookings</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-file-alt"></i>
                        <span>Track Applications</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-robot"></i>
                        <span>AI-Powered Assistance</span>
                    </div>
                </div>
                <a href="become-member.php" class="btn btn-primary">Join as Consultant</a>
            </div>

            <!-- Platform Features -->
            <div class="service-card" data-aos="fade-up" data-aos-delay="400">
                <div class="service-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Platform Features</h3>
                <div class="service-features">
                    <div class="feature-item">
                        <i class="fas fa-comments"></i>
                        <span>Real-time Communication</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-file-upload"></i>
                        <span>Secure Document Management</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-bell"></i>
                        <span>Instant Notifications</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics & Insights</span>
                    </div>
                </div>
                <a href="features.php" class="btn btn-primary">Learn More</a>
            </div>

            <!-- AI Assistant -->
            <div class="service-card" data-aos="fade-up" data-aos-delay="500">
                <div class="service-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <h3>AI Assistant</h3>
                <div class="service-features">
                    <div class="feature-item">
                        <i class="fas fa-question-circle"></i>
                        <span>24/7 Support</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-lightbulb"></i>
                        <span>Smart Recommendations</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-language"></i>
                        <span>Multi-language Support</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-brain"></i>
                        <span>Intelligent Responses</span>
                    </div>
                </div>
                <a href="ai-assistant.php" class="btn btn-primary">Try AI Assistant</a>
            </div>
        </div>
    </div>
</section>

<!-- Platform Benefits Section -->
<section class="section platform-benefits">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Your Immigration Journey, Simplified</h2>
        <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Experience a seamless immigration process
            with our all-in-one digital platform</p>

        <div class="benefits-container">
            <!-- Dashboard Section -->
            <div class="benefit-section" data-aos="fade-up">
                <div class="benefit-content">
                    <div class="benefit-info">
                        <h3>

                            Real-Time Application Tracking
                        </h3>
                        <p class="benefit-description">
                            Monitor your applications in real-time and stay updated on every milestone of your
                            immigration journey.
                        </p>
                        <ul class="benefit-features">
                            <li>
                                <div class="check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Live Status Updates</strong>
                                    <p>Monitor your application progress in real-time with instant notifications on
                                        status changes</p>
                                </div>
                            </li>
                            <li>
                                <div class="check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Timeline Visualization</strong>
                                    <p>View your entire application journey with clear milestones and expected
                                        completion dates</p>
                                </div>
                            </li>
                            <li>
                                <div class="check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Personalized To-Do Lists</strong>
                                    <p>Stay on track with custom checklists and timely reminders for required actions
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="benefit-image">
                        <!-- SVG Shape Background -->
                        <div class="svg-background">
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-1">
                                <path
                                    d="M42.7,-73.4C55.9,-67.1,67.7,-57.2,75.9,-44.6C84.1,-32,88.7,-16,88.1,-0.3C87.5,15.3,81.8,30.6,73.1,43.9C64.4,57.2,52.8,68.5,39.1,75.3C25.4,82.1,9.7,84.4,-5.9,83.1C-21.5,81.8,-37,76.9,-50.9,68.5C-64.8,60.1,-77.1,48.3,-83.3,33.8C-89.5,19.3,-89.6,2.2,-85.1,-13.2C-80.6,-28.6,-71.5,-42.3,-59.8,-51.6C-48.1,-60.9,-33.8,-65.8,-20.4,-70.3C-7,-74.8,5.5,-78.9,18.8,-79.1C32.1,-79.3,46.2,-75.6,42.7,-73.4Z"
                                    transform="translate(100 100)" />
                            </svg>
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-2">
                                <path
                                    d="M47.7,-80.4C59.3,-71.3,64.8,-54.7,71.9,-39.4C79,-24.2,87.7,-10.3,87.5,3.4C87.3,17.1,78.1,30.6,68.3,42.8C58.5,55,48,65.9,35.1,73.4C22.2,80.9,6.9,85,-8.9,84.5C-24.8,84,-41.2,78.9,-54.3,69.5C-67.4,60.1,-77.2,46.4,-83.1,30.8C-89,15.3,-91,-1.1,-87.4,-16.2C-83.8,-31.3,-74.6,-45.2,-62.3,-54.8C-50,-64.4,-34.6,-69.8,-19.9,-74.9C-5.2,-80,9.7,-84.8,24.4,-84.1C39.2,-83.4,53.8,-77.2,47.7,-80.4Z"
                                    transform="translate(100 100)" />
                            </svg>
                        </div>

                        <div class="image-container">
                            <img src="assets/images/visafy-dashboard.png" alt="Application Dashboard">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Section -->
            <div class="benefit-section" data-aos="fade-up">
                <div class="benefit-content reverse">
                    <div class="benefit-image">
                        <!-- SVG Shape Background -->
                        <div class="svg-background">
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-3">
                                <path
                                    d="M39.9,-68.1C52.6,-62.1,64.5,-53.1,72.7,-41C80.9,-28.8,85.4,-14.4,83.9,-0.9C82.3,12.7,74.8,25.4,66.4,37.8C58,50.3,48.7,62.5,36.5,70.1C24.2,77.7,9.1,80.7,-5.9,79.5C-20.9,78.3,-35.9,72.9,-47.5,64C-59.1,55,-67.3,42.5,-73.4,28.5C-79.5,14.5,-83.5,-1,-80.8,-15.2C-78.1,-29.4,-68.7,-42.3,-56.8,-48.9C-44.9,-55.5,-30.5,-55.8,-17.7,-61.8C-4.9,-67.8,6.3,-79.5,18.4,-80.5C30.5,-81.5,43.5,-71.8,39.9,-68.1Z"
                                    transform="translate(100 100)" />
                            </svg>
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-4">
                                <path
                                    d="M47.3,-79.7C62.9,-71.9,78.5,-62.3,86.4,-48.3C94.3,-34.3,94.5,-15.7,90.3,0.9C86.1,17.4,77.5,31.8,67.2,44.7C56.9,57.6,44.9,69,30.7,76.2C16.5,83.4,0.1,86.4,-16.4,83.3C-32.9,80.2,-45.5,71,-57.8,59C-70.1,47,-80.1,32.2,-84.6,15.6C-89.1,-1,-88.1,-19.4,-81.5,-35.1C-74.9,-50.8,-62.7,-63.8,-48.1,-72.1C-33.5,-80.4,-16.7,-84,0.2,-84.4C17.2,-84.8,34.3,-82,47.3,-79.7Z"
                                    transform="translate(100 100)" />
                            </svg>
                        </div>

                        <div class="image-container">
                            <img src="assets/images/documents.png" alt="Document Management">
                        </div>
                    </div>
                    <div class="benefit-info">
                        <h3>
                            Streamlined Document Handling
                        </h3>
                        <p class="benefit-description">
                            Securely manage all your important documents with our specialized document handling system.
                        </p>
                        <ul class="benefit-features">
                            <li>
                                <div class="check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Secure Document Hub</strong>
                                    <p>Upload, store, and share your important documents in a highly secured environment
                                    </p>
                                </div>
                            </li>
                            <li>
                                <div class="check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Document Checklists</strong>
                                    <p>Access personalized checklists of required documents based on your visa category
                                    </p>
                                </div>
                            </li>
                            <li>
                                <div class="check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Document Status Tracking</strong>
                                    <p>Monitor which documents are approved, pending or require revision with visual
                                        indicators</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Messaging Section -->
            <div class="benefit-section" data-aos="fade-up">
                <div class="benefit-content">
                    <div class="benefit-info">
                        <h3>

                            Direct Expert Communication
                        </h3>
                        <p class="benefit-description">
                            Get instant support and guidance from immigration experts through our integrated messaging
                            system.
                        </p>
                        <ul class="benefit-features">
                            <li>
                                <div class="check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Instant Messaging</strong>
                                    <p>Connect directly with your assigned immigration consultant for quick questions
                                        and updates</p>
                                </div>
                            </li>
                            <li>
                                <div class="check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Conversation History</strong>
                                    <p>Access your complete communication history for reference and documentation</p>
                                </div>
                            </li>
                            <li>
                                <div class="check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <strong>Multilingual Support</strong>
                                    <p>Communicate with team members in multiple languages with real-time message
                                        notifications</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="benefit-image">
                        <!-- SVG Shape Background -->
                        <div class="svg-background">
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-5">
                                <path
                                    d="M48.2,-76.1C63.3,-69.2,77.2,-58.4,84.6,-44.2C92,-30,92.8,-12.5,89.6,3.7C86.3,19.9,78.9,34.8,68.9,47.9C58.9,61,46.2,72.3,31.5,77.8C16.8,83.2,0.1,82.8,-16.4,79.7C-32.9,76.6,-49.2,70.8,-62.7,60.3C-76.2,49.8,-87,34.6,-90.9,17.8C-94.8,0.9,-91.9,-17.5,-84.2,-32.8C-76.5,-48.1,-64,-60.2,-49.5,-67.5C-35,-74.8,-18.5,-77.3,-1.2,-75.5C16.1,-73.7,33.1,-83,48.2,-76.1Z"
                                    transform="translate(100 100)" />
                            </svg>
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-6">
                                <path
                                    d="M45.3,-76.3C59.9,-69.1,73.8,-59.3,82.1,-45.9C90.4,-32.5,93.2,-15.3,90.6,0.8C88,16.8,80,31.7,70.1,45.1C60.2,58.6,48.4,70.7,34.4,77.4C20.4,84.1,4.3,85.4,-12.4,83.5C-29.1,81.6,-46.5,76.4,-59.8,66.1C-73.1,55.7,-82.3,40.1,-86.9,23.4C-91.5,6.7,-91.5,-11.2,-85.8,-26.5C-80.1,-41.8,-68.7,-54.5,-54.9,-61.9C-41.1,-69.3,-24.9,-71.3,-8.9,-70.1C7.1,-68.9,14.1,-64.5,26.3,-67.2C38.5,-69.9,55.8,-79.7,45.3,-76.3Z"
                                    transform="translate(100 100)" />
                            </svg>
                        </div>

                        <div class="image-container">
                            <img src="assets/images/messaging.png" alt="Messaging System">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->

        </div>
    </div>
</section>
<div class="benefits-cta" data-aos="fade-up">
    <p>Ready to experience our innovative platform?</p>
    <div class="cta-buttons">
        <a href="register.php" class="btn-primary">
            <i class="fas fa-user-plus"></i> Sign up for Free
        </a>
        <a href="how-it-works.php" class="btn-secondary">
            <i class="fas fa-play-circle"></i> Watch Demo
        </a>
    </div>
</div>
<!-- Key Benefits Section -->
<section class="section key-benefits">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Why Choose Visafy?</h2>
        <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Experience a seamless immigration process
            with our comprehensive platform</p>

        <div class="benefits-grid">
            <!-- Expert Guidance -->
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="200">
                <div class="svg-background">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M42.7,-73.4C55.9,-67.1,67.7,-57.2,75.9,-44.6C84.1,-32,88.7,-16,88.1,-0.3C87.5,15.3,81.8,30.6,73.1,43.9C64.4,57.2,52.8,68.5,39.1,75.3C25.4,82.1,9.7,84.4,-5.9,83.1C-21.5,81.8,-37,76.9,-50.9,68.5C-64.8,60.1,-77.1,48.3,-83.3,33.8C-89.5,19.3,-89.6,2.2,-85.1,-13.2C-80.6,-28.6,-71.5,-42.3,-59.8,-51.6C-48.1,-60.9,-33.8,-65.8,-20.4,-70.3C-7,-74.8,5.5,-78.9,18.8,-79.1C32.1,-79.3,46.2,-75.6,42.7,-73.4Z"
                            transform="translate(100 100)" />
                    </svg>
                </div>
                <div class="benefit-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h3>Licensed Consultants</h3>
                <p>Get expert guidance from ICCRC licensed consultants with proven track records in successful
                    applications.</p>
            </div>

            <!-- Digital Platform -->
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="300">
                <div class="svg-background">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M47.7,-80.4C59.3,-71.3,64.8,-54.7,71.9,-39.4C79,-24.2,87.7,-10.3,87.5,3.4C87.3,17.1,78.1,30.6,68.3,42.8C58.5,55,48,65.9,35.1,73.4C22.2,80.9,6.9,85,-8.9,84.5C-24.8,84,-41.2,78.9,-54.3,69.5C-67.4,60.1,-77.2,46.4,-83.1,30.8C-89,15.3,-91,-1.1,-87.4,-16.2C-83.8,-31.3,-74.6,-45.2,-62.3,-54.8C-50,-64.4,-34.6,-69.8,-19.9,-74.9C-5.2,-80,9.7,-84.8,24.4,-84.1C39.2,-83.4,53.8,-77.2,47.7,-80.4Z"
                            transform="translate(100 100)" />
                    </svg>
                </div>
                <div class="benefit-icon">
                    <i class="fas fa-laptop"></i>
                </div>
                <h3>Digital-First Platform</h3>
                <p>Manage your entire immigration journey through our user-friendly digital platform, accessible
                    anytime, anywhere.</p>
            </div>

            <!-- Document Management -->
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="400">
                <div class="svg-background">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M39.9,-68.1C52.6,-62.1,64.5,-53.1,72.7,-41C80.9,-28.8,85.4,-14.4,83.9,-0.9C82.3,12.7,74.8,25.4,66.4,37.8C58,50.3,48.7,62.5,36.5,70.1C24.2,77.7,9.1,80.7,-5.9,79.5C-20.9,78.3,-35.9,72.9,-47.5,64C-59.1,55,-67.3,42.5,-73.4,28.5C-79.5,14.5,-83.5,-1,-80.8,-15.2C-78.1,-29.4,-68.7,-42.3,-56.8,-48.9C-44.9,-55.5,-30.5,-55.8,-17.7,-61.8C-4.9,-67.8,6.3,-79.5,18.4,-80.5C30.5,-81.5,43.5,-71.8,39.9,-68.1Z"
                            transform="translate(100 100)" />
                    </svg>
                </div>
                <div class="benefit-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>Smart Document Management</h3>
                <p>Securely store and manage all your documents with our advanced document handling system.</p>
            </div>

            <!-- Real-time Updates -->
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="500">
                <div class="svg-background">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M47.3,-79.7C62.9,-71.9,78.5,-62.3,86.4,-48.3C94.3,-34.3,94.5,-15.7,90.3,0.9C86.1,17.4,77.5,31.8,67.2,44.7C56.9,57.6,44.9,69,30.7,76.2C16.5,83.4,0.1,86.4,-16.4,83.3C-32.9,80.2,-45.5,71,-57.8,59C-70.1,47,-80.1,32.2,-84.6,15.6C-89.1,-1,-88.1,-19.4,-81.5,-35.1C-74.9,-50.8,-62.7,-63.8,-48.1,-72.1C-33.5,-80.4,-16.7,-84,0.2,-84.4C17.2,-84.8,34.3,-82,47.3,-79.7Z"
                            transform="translate(100 100)" />
                    </svg>
                </div>
                <div class="benefit-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <h3>Real-time Updates</h3>
                <p>Stay informed with instant notifications and real-time updates on your application status.</p>
            </div>

            <!-- Success Rate -->
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="600">
                <div class="svg-background">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M48.2,-76.1C63.3,-69.2,77.2,-58.4,84.6,-44.2C92,-30,92.8,-12.5,89.6,3.7C86.3,19.9,78.9,34.8,68.9,47.9C58.9,61,46.2,72.3,31.5,77.8C16.8,83.2,0.1,82.8,-16.4,79.7C-32.9,76.6,-49.2,70.8,-62.7,60.3C-76.2,49.8,-87,34.6,-90.9,17.8C-94.8,0.9,-91.9,-17.5,-84.2,-32.8C-76.5,-48.1,-64,-60.2,-49.5,-67.5C-35,-74.8,-18.5,-77.3,-1.2,-75.5C16.1,-73.7,33.1,-83,48.2,-76.1Z"
                            transform="translate(100 100)" />
                    </svg>
                </div>
                <div class="benefit-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>High Success Rate</h3>
                <p>Benefit from our proven track record of successful applications and satisfied clients.</p>
            </div>

            <!-- Support -->
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="700">
                <div class="svg-background">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M45.3,-76.3C59.9,-69.1,73.8,-59.3,82.1,-45.9C90.4,-32.5,93.2,-15.3,90.6,0.8C88,16.8,80,31.7,70.1,45.1C60.2,58.6,48.4,70.7,34.4,77.4C20.4,84.1,4.3,85.4,-12.4,83.5C-29.1,81.6,-46.5,76.4,-59.8,66.1C-73.1,55.7,-82.3,40.1,-86.9,23.4C-91.5,6.7,-91.5,-11.2,-85.8,-26.5C-80.1,-41.8,-68.7,-54.5,-54.9,-61.9C-41.1,-69.3,-24.9,-71.3,-8.9,-70.1C7.1,-68.9,14.1,-64.5,26.3,-67.2C38.5,-69.9,55.8,-79.7,45.3,-76.3Z"
                            transform="translate(100 100)" />
                    </svg>
                </div>
                <div class="benefit-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>24/7 Support</h3>
                <p>Get assistance whenever you need it with our round-the-clock customer support team.</p>
            </div>
        </div>
    </div>
</section>

<!-- Steps Section -->
<section class="section steps">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Your Global Immigration Journey</h2>
        <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Follow these simple steps to start your
            immigration process anywhere in the world</p>

        <div class="steps-container">
            <!-- Step 1 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="200">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Create Your Account</h3>
                    <p>Begin your immigration journey by creating a free account. Complete your profile with essential
                        information to help us understand your goals and requirements. Our system will guide you through
                        the initial setup process.</p>
                    <a href="register.php" class="btn btn-outline">Sign Up Now</a>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="300">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Check Eligibility</h3>
                    <p>Take our comprehensive eligibility assessment to determine your immigration options. Our advanced
                        algorithm analyzes your profile against various immigration programs to find the best pathway
                        for your situation.</p>
                    <a href="eligibility-test.php" class="btn btn-outline">Start Assessment</a>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="400">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Book Consultation</h3>
                    <p>Schedule a free consultation with our immigration experts. Discuss your options, get personalized
                        advice, and learn about the next steps in your immigration journey. Choose a time that works
                        best for you.</p>
                    <a href="book-consultation.php" class="btn btn-outline">Book Now</a>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="500">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3>Begin Your Application</h3>
                    <p>Start your immigration process with expert guidance. Our consultants will help you prepare and
                        submit your application, ensuring all requirements are met. Track your progress through our
                        user-friendly dashboard.</p>
                    <a href="services.php" class="btn btn-outline">View Services</a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Key Benefits Section */
.section {
    padding: 4rem 0;
}

.section-title {
    font-size: 2rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 0.5rem;
}

.section-subtitle {
    font-size: 1rem;
    color: var(--text-muted);
    text-align: center;
    max-width: 600px;
    margin: 0 auto 3rem;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 1.5rem;
}

@media (min-width: 640px) {
    .services-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .services-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.service-card {
    background-color: var(--white);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.service-card:hover {
    box-shadow: var(--shadow-hover);
    transform: translateY(-2px);
}

.service-icon {
    width: 3rem;
    height: 3rem;
    background-color: var(--primary-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.service-icon i {
    font-size: 1.5rem;
    color: var(--primary-color);
}

.service-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.service-features {
    margin-bottom: 1.5rem;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.feature-item i {
    font-size: 1rem;
    color: var(--primary-color);
}

.feature-item span {
    font-size: 0.875rem;
}

.btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    font-weight: 500;
    text-align: center;
    text-decoration: none;
    border-radius: var(--radius);
    transition: var(--transition);
    cursor: pointer;
    width: 100%;
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--white);
    border: 1px solid var(--primary-color);
}

.btn-primary:hover {
    background-color: transparent;
    color: var(--primary-color);
}
.benefits-cta {
    padding: 80px;
    background-color: rgba(254, 249, 225, 0.5);
}

.key-benefits {
    padding: 80px 0;
    background-color: var(--light-bg);
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    margin-top: 50px;
}

.benefit-card {
    background: var(--white);
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: transform 0.3s ease;
    position: relative;
    overflow: hidden;
}

.benefit-card:hover {
    transform: translateY(-5px);
}

.svg-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    opacity: 0.1;
}

.svg-background svg {
    width: 100%;
    height: 100%;
    fill: var(--primary-color);
}

.benefit-icon {
    position: relative;
    z-index: 1;
    font-size: 3.5rem;
    color: var(--primary-color);
    margin-bottom: 25px;
}

.benefit-card h3 {
    position: relative;
    z-index: 1;
    margin-bottom: 15px;
    color: var(--dark-color);
}

.benefit-card p {
    position: relative;
    z-index: 1;
    color: var(--text-color);
}

/* Steps Section */
.steps {
    padding: 80px 0;
    position: relative;
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

@media (max-width: 768px) {
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const visaTypes = [
        'Study Permits',
        'Work Permits',
        'Express Entry',
        'Provincial Nominee',
        'Family Sponsorship',
        'Super Visa',
        'Visitor Visa'
    ];

    const wrapper = document.querySelector('.animated-text-wrapper');
    let currentIndex = 0;
    let nextIndex = 1;

    // Create text elements
    const currentText = document.createElement('div');
    const nextText = document.createElement('div');
    currentText.className = 'animated-text current';
    nextText.className = 'animated-text next';
    wrapper.appendChild(currentText);
    wrapper.appendChild(nextText);

    function updateText() {
        // Set text content
        currentText.textContent = visaTypes[currentIndex];
        nextText.textContent = visaTypes[nextIndex];

        // Start animation
        currentText.classList.add('exiting');
        currentText.classList.remove('current');

        nextText.classList.add('current');
        nextText.classList.remove('next');

        // After animation completes
        setTimeout(() => {
            // Reset the exiting text for next animation
            currentText.classList.remove('exiting');
            currentText.classList.add('next');

            // Update indices
            currentIndex = nextIndex;
            nextIndex = (nextIndex + 1) % visaTypes.length;

            // Prepare elements for next animation
            currentText.style.transition = 'none';
            currentText.style.transform = 'translateY(100%)';

            // Force reflow
            currentText.offsetHeight;

            // Re-enable transitions
            currentText.style.transition = '';

            // Swap elements
            [currentText, nextText] = [nextText, currentText];
        }, 500);
    }

    // Initial text setup
    currentText.textContent = visaTypes[0];
    nextText.textContent = visaTypes[1];

    // Start the animation loop
    setInterval(updateText, 3000);

    // Add AOS animation initialization if you're using AOS library
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    }
});
</script>

<?php include('includes/footer.php'); ?>